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

LOG_FILE="$APP_ROOT/logs/init-$(date +%F-%T).log"
exec > >(tee $LOG_FILE) 2>&1

TIMEFORMAT=%lR
DDEV_DOCROOT=${WEB_ROOT##*/}
SETTINGS_FILE_PATH=$WEB_ROOT/sites/default/settings.php

#== Remove root-owned files.
cd $APP_ROOT
echo
echo Remove root-owned files.
time sudo rm -rf lost+found
RETURN_CODE=$?
if [ $RETURN_CODE != 0 ]; then
  exit $RETURN_CODE
fi

#== Clone source code.
if [ -z "$(ls -A $APP_ROOT/repos/drupal/drupal_cms)" ]; then
  echo "Clone source code."
  echo
  time git submodule update --init --remote --recursive
  RETURN_CODE=$?
  if [ $RETURN_CODE != 0 ]; then
    exit $RETURN_CODE
  fi

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
  RETURN_CODE=$?
  if [ $RETURN_CODE != 0 ]; then
    exit $RETURN_CODE
  fi
fi

#== Composer install.
cd $APP_ROOT
if [ ! -f composer.lock ]; then
  echo
  echo 'Generate composer.json.'
  time .devpanel/generate-composer-json > composer.json
  RETURN_CODE=$?
  if [ $RETURN_CODE != 0 ]; then
    exit $RETURN_CODE
  fi

  echo
  time composer -n install --no-progress
  RETURN_CODE=$?
  if [ $RETURN_CODE != 0 ]; then
    exit $RETURN_CODE
  fi
fi

#== Symlink Drupal CMS installer into web root.
echo
echo 'Symlink Drupal CMS installer into web root.'
time ln -s -f $(realpath -s --relative-to=$DDEV_DOCROOT/profiles repos/drupal/drupal_cms/project_template/$DDEV_DOCROOT/profiles/drupal_cms_installer) $DDEV_DOCROOT/profiles
RETURN_CODE=$?
if [ $RETURN_CODE != 0 ]; then
  exit $RETURN_CODE
fi

#== Install JavaScript dependencies if needed.
cd $APP_ROOT/repos/drupal/drupal_cms
if [ ! -d node_modules ]; then
  echo
  echo 'Install JavaScript dependencies.'
  time npm -q clean-install --foreground-scripts
  RETURN_CODE=$?
  if [ $RETURN_CODE != 0 ]; then
    exit $RETURN_CODE
  fi
fi

#== Create the private files directory.
cd $APP_ROOT
if [ ! -d private ]; then
  echo
  echo 'Create the private files directory.'
  time mkdir private
  RETURN_CODE=$?
  if [ $RETURN_CODE != 0 ]; then
    exit $RETURN_CODE
  fi
fi

#== Set up settings.php file.
if [ ! -f $SETTINGS_FILE_PATH ]; then
  echo
  echo 'Set up settings.php file.'
  time cp $APP_ROOT/.devpanel/drupal-settings.php $SETTINGS_FILE_PATH
  RETURN_CODE=$?
  if [ $RETURN_CODE != 0 ]; then
    exit $RETURN_CODE
  fi
fi

#== Pre-install starter recipe.
cd $APP_ROOT
if [ -d recipes/drupal_cms_starter ] && [ -z "$(mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASSWORD $DB_NAME -e 'show tables')" ]; then
  echo
  echo 'Install Drupal base system.'
  while [ -z "$(drush sget drupal_cms_profile.profile_modules_installed 2> /dev/null)" ]; do
    time .devpanel/install > /dev/null
  done
  drush sdel drupal_cms_profile.profile_modules_installed
  RETURN_CODE=$?
  if [ $RETURN_CODE != 0 ]; then
    exit $RETURN_CODE
  fi

  echo
  echo 'Apply the Drupal CMS starter recipe.'
  until time drush -q recipe $APP_ROOT/recipes/drupal_cms_starter; do
    :
  done

  echo
  echo 'Tell Automatic Updates about patches.'
  time drush -n cset --input-format=yaml package_manager.settings additional_known_files_in_project_root '["patches.json", "patches.lock.json"]'
  RETURN_CODE=$?
  if [ $RETURN_CODE != 0 ]; then
    exit $RETURN_CODE
  fi

  echo
  time drush -n pmu drupal_cms_installer
  RETURN_CODE=$?
  if [ $RETURN_CODE != 0 ]; then
    exit $RETURN_CODE
  fi

  echo
  time drush cr
  RETURN_CODE=$?
  if [ $RETURN_CODE != 0 ]; then
    exit $RETURN_CODE
  fi
fi

INIT_DURATION=$SECONDS
INIT_HOURS=$(($INIT_DURATION / 3600))
INIT_MINUTES=$(($INIT_DURATION % 3600 / 60))
INIT_SECONDS=$(($INIT_DURATION % 60))
printf "\nTotal elapsed time: %d:%02d:%02d\n" $INIT_HOURS $INIT_MINUTES $INIT_SECONDS
