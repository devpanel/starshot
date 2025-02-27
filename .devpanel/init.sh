#!/usr/bin/env bash
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
set -eu -o pipefail

LOG_FILE="$APP_ROOT/logs/init-$(date +%F-%T).log"
exec > >(tee $LOG_FILE) 2>&1

TIMEFORMAT=%lR
DDEV_DOCROOT=${WEB_ROOT##*/}
SETTINGS_FILE_PATH=$WEB_ROOT/sites/default/settings.php
COMPOSER_NO_AUDIT=1

#== Remove root-owned files.
cd $APP_ROOT
echo
echo Remove root-owned files.
time sudo rm -rf lost+found

#== Clone source code.
if [ -z "$(ls -A $APP_ROOT/repos/drupal/drupal_cms)" ]; then
  echo "Clone source code."
  echo
  time git submodule update --init --remote --recursive

  # Check git submodule init successfully
  # See https://stackoverflow.com/questions/3336995/git-will-not-init-sync-update-new-submodules
  if [ -z "$(git submodule status)" ]; then
    echo "Force init submodule"
    echo  
    time git submodule add -f  https://git.drupalcode.org/project/drupal_cms.git repos/drupal/drupal_cms
    echo
    time git submodule update --init --remote --recursive
  fi

  cd $APP_ROOT/repos/drupal/drupal_cms
  echo
  time git checkout $(git branch -r | grep "origin/HEAD" | cut -f 3 -d '/')
fi

#== Composer install.
cd $APP_ROOT
if [ ! -f composer.lock ]; then
  echo
  echo 'Generate composer.json.'
  time .devpanel/generate-composer-json > composer.json

  echo
  time composer -n install --no-progress
fi

#== Symlink Drupal CMS installer into web root.
echo
echo 'Symlink Drupal CMS installer into web root.'
time ln -s -f $(realpath -s --relative-to=$DDEV_DOCROOT/profiles repos/drupal/drupal_cms/project_template/$DDEV_DOCROOT/profiles/drupal_cms_installer) $DDEV_DOCROOT/profiles

#== Install JavaScript dependencies if needed.
cd $APP_ROOT/repos/drupal/drupal_cms
if [ ! -d node_modules ]; then
  echo
  echo 'Install JavaScript dependencies.'
  time npm -q clean-install --foreground-scripts
fi

#== Create the private files directory.
cd $APP_ROOT
if [ ! -d private ]; then
  echo
  echo 'Create the private files directory.'
  time mkdir private
fi

#== Pre-install starter recipe.
cd $APP_ROOT
if [ -z "$(mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASSWORD $DB_NAME -e 'show tables')" ]; then
  echo
  echo 'Generate hash salt.'
  time openssl rand -hex 32 > $APP_ROOT/.devpanel/salt.txt
 
  echo
  echo 'Install Drupal base system.'
  while [ -z "$(drush sget recipe_installer_kit.profile_modules_installed 2> /dev/null)" ]; do
    time .devpanel/install > /dev/null
  done
  drush sdel recipe_installer_kit.profile_modules_installed

  echo
  echo 'Apply required recipes.'
  RECIPES=$(drush sget --format=yaml recipe_installer_kit.required_recipes | grep '^  - .\+/.\+' | cut -f 4 -d ' ')
  RECIPES_PATH=$(drush --include=.devpanel/drush crp)
  RECIPES_APPLIED=''
  for RECIPE in $RECIPES; do
    RECIPE_PATH=$RECIPES_PATH/$(echo $RECIPE | cut -f 2 -d '/')
    if [ -d $RECIPE_PATH ]; then
      until time drush --include=.devpanel/drush -q recipe $RECIPE_PATH; do
        time drush cr
      done

      if [ -n "$RECIPES_APPLIED" ]; then
        RECIPES_APPLIED="$RECIPES_APPLIED,\"$RECIPE\""
      else
        RECIPES_APPLIED="\"$RECIPE\""
      fi
    fi
  done
  drush sdel recipe_installer_kit.required_recipes
  drush sset --input-format=yaml installer.applied_recipes "[$RECIPES_APPLIED]"

  echo
  echo 'Tell Automatic Updates about patches.'
  time drush -n cset --input-format=yaml package_manager.settings additional_known_files_in_project_root '["patches.json", "patches.lock.json"]'

  echo
  time drush -n pmu drupal_cms_installer

  echo
  time drush cr
fi

INIT_DURATION=$SECONDS
INIT_HOURS=$(($INIT_DURATION / 3600))
INIT_MINUTES=$(($INIT_DURATION % 3600 / 60))
INIT_SECONDS=$(($INIT_DURATION % 60))
printf "\nTotal elapsed time: %d:%02d:%02d\n" $INIT_HOURS $INIT_MINUTES $INIT_SECONDS
