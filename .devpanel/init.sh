#!/bin/bash
# ---------------------------------------------------------------------
# Copyright (C) 2024 DevPanel
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation version 3 of the
# License.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# For GNU Affero General Public License see <https://www.gnu.org/licenses/>.
# ----------------------------------------------------------------------

DDEV_DOCROOT=${WEB_ROOT##*/}
SETTINGS_FILE_PATH=$WEB_ROOT/sites/default/settings.php

#== Clone source code.
if [ -z "$(ls -A $APP_ROOT/repos/drupal/drupal_cms)" ]; then
  git submodule update --init --remote --recursive
  cd $APP_ROOT/repos/drupal/drupal_cms
  git checkout $(git branch -r | grep "origin/HEAD" | cut -f 3 -d '/')
fi

#== Remove root-owned files.
cd $APP_ROOT
sudo rm -rf lost+found

#== Generate packages.json.
.devpanel/generate-packages-json > packages.json

#== Composer install.
if [ ! -d vendor ]; then
  .devpanel/generate-composer-json > composer.json
  composer install
fi

#== Symlink the installer into the web root.
ln -s -f $(realpath -s --relative-to=$DDEV_DOCROOT/profiles repos/drupal/drupal_cms/project_template/$DDEV_DOCROOT/profiles/drupal_cms_installer) $DDEV_DOCROOT/profiles

#== Install JavaScript dependencies if needed.
cd $APP_ROOT/repos/drupal/drupal_cms
if [ ! -d node_modules ]; then
  npm clean-install --foreground-scripts
fi

#== Build Experience Builder's JavaScript bundle, if needed.
XB_UI_PATH=$DDEV_DOCROOT/modules/contrib/experience_builder/ui
if [ -d $XB_UI_PATH/dist ]; then
  npm --prefix $XB_UI_PATH install
  npm --prefix $XB_UI_PATH run build
fi

#== Copy recipes cache.
cd $APP_ROOT
if ! grep -qxF '/project_template/web/profiles/drupal_cms_installer/cache/*' .git/modules/repos/drupal/drupal_cms/info/exclude; then
  echo '/project_template/web/profiles/drupal_cms_installer/cache/*' >> .git/modules/repos/drupal/drupal_cms/info/exclude
fi
if [ -d web/profiles/drupal_cms_installer/cache ] && [ -z "$(git status --porcelain repos/drupal/drupal_cms)" ]; then
  cp -n .devpanel/drupal_cms_cache/* web/profiles/drupal_cms_installer/cache
  #== Patch for issue #3497485. We do this now so the changes don't prevent
  #== the recipes cache from being copied.
  cd $APP_ROOT/repos/drupal/drupal_cms
  if ! git merge-base --is-ancestor 4a30663d422184a459164049987a6b455ccf5bf5 HEAD; then
    git apply $APP_ROOT/patches/drupal/drupal_cms/373.patch
  fi
fi

#== Create the private files directory.
cd $APP_ROOT
if [ ! -d private ]; then
  mkdir private
fi

#== Set up settings.php file.
if [ ! -f $SETTINGS_FILE_PATH ]; then
  echo "Set up settings.php file."
  cp $APP_ROOT/.devpanel/drupal-settings.php $SETTINGS_FILE_PATH
fi

#== Pre-install starter recipe.
cd $APP_ROOT
if [ -d recipes/drupal_cms_starter ] && [ -z "$(mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASSWORD $DB_NAME -e 'show tables')" ]; then
  while [ -z "$(drush status --fields=bootstrap)" ]; do
    curl -Is "localhost/core/install.php?profile=drupal_cms_installer&langcode=en&recipes%5B0%5D=drupal_cms_starter&site_name=Drupal%20CMS" > /dev/null
  done
  drush ev "require_once 'core/includes/install.core.inc'; install_core_entity_type_definitions();"
  until drush recipe $APP_ROOT/recipes/drupal_cms_starter; do
    :
  done
  drush -n pmu drupal_cms_installer
  drush cr
fi
