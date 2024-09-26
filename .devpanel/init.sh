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

#== Clone source code
SETTINGS_FILES_PATH=$WEB_ROOT/sites/default/settings.php
if [ -z "$(ls -A $APP_ROOT/drupal_cms)" ]; then
  git submodule update --init --remote --recursive
  cd $APP_ROOT/drupal_cms
  git checkout main
fi

#== Composer install.
cd $APP_ROOT/drupal_cms
cp -f ./project_template/composer.json .
find . -maxdepth 1 -type d -name 'drupal_cms*' -exec composer config --global repositories.{} path {} ';'
composer require --dev drupal/default_content

#== Site install.
if [[ $(mysql -h$DB_HOST -P$DB_PORT -u$DB_USER -p$DB_PASSWORD $DB_NAME -e "show tables;") == '' ]]; then
  #== Setup settings.php file
  echo "Setup settings.php file"
  sudo cp $APP_ROOT/.devpanel/drupal-settings.php $SETTINGS_FILES_PATH
  chmod u-w $SETTINGS_FILES_PATH
  drush cr

  #== Webform library install.
  drush webform-libraries-download
fi
