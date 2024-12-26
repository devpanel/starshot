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
SETTINGS_FILES_PATH=$WEB_ROOT/sites/default/settings.php

#== Clone source code.
if [ -z "$(ls -A $APP_ROOT/repos/drupal/drupal_cms)" ]; then
  git submodule update --init --remote --recursive
  cd $APP_ROOT/repos/drupal/drupal_cms
  git checkout $(git branch -r | grep "origin/HEAD" | cut -f 3 -d '/')
fi

#== Composer install.
cd $APP_ROOT
sudo rm -rf lost+found
.devpanel/generate-composer-json > composer.json
composer install
ln -s -f $(realpath -s --relative-to=$DDEV_DOCROOT/profiles repos/drupal/drupal_cms/project_template/$DDEV_DOCROOT/profiles/drupal_cms_installer) $DDEV_DOCROOT/profiles
cd repos/drupal/drupal_cms && test -d node_modules || npm clean-install --foreground-scripts

#== Copy recipes cache.
cd $APP_ROOT
if [ ! grep -qxF "/project_template/web/profiles/drupal_cms_installer/cache/*" .git/modules/repos/drupal/drupal_cms/info/exclude ]; then
  echo "/project_template/web/profiles/drupal_cms_installer/cache/*" >> .git/modules/repos/drupal/drupal_cms/info/exclude
fi
if [ -d web/profiles/drupal_cms_installer/cache && -z "$(git status --porcelain repos/drupal/drupal_cms)" ]; then
  cp -n .devpanel/drupal_cms_cache/* web/profiles/drupal_cms_installer/cache
fi

#== Site install.
if [[ $(mysql -h$DB_HOST -P$DB_PORT -u$DB_USER -p$DB_PASSWORD $DB_NAME -e "show tables;") == '' ]]; then
  #== Setup settings.php file
  echo "Setup settings.php file"
  sudo cp $APP_ROOT/.devpanel/drupal-settings.php $SETTINGS_FILES_PATH
  sudo chown $USER:$GROUP $SETTINGS_FILES_PATH
  #== Pre-install starter recipe.
  curl "$DP_HOSTNAME/core/install.php?profile=drupal_cms_installer&langcode=en&recipes%5B0%5D=drupal_cms_starter&site_name=Drupal%20CMS"
  until drush recipe $APP_ROOT/recipes/drupal_cms_starter; do :; done
fi
