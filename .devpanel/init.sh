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
if [ -z "$(ls -A $APP_ROOT/repos/drupal/drupal_cms)" ]; then
  git submodule update --init --remote --recursive
fi

#== Composer install.
cd $APP_ROOT
sudo rm -rf lost+found
.devpanel/generate-composer-json > composer.json
composer install
ln -s -f $(realpath -s --relative-to=$WEB_ROOT/profiles repos/drupal/drupal_cms/project_template/$WEB_ROOT/profiles/drupal_cms_installer) $WEB_ROOT/profiles
test -d node_modules || npm clean-install --foreground-scripts

#== Site install.
if [[ $(mysql -h$DB_HOST -P$DB_PORT -u$DB_USER -p$DB_PASSWORD $DB_NAME -e "show tables;") == '' ]]; then
  #== Setup settings.php file
  echo "Setup settings.php file"
  sudo cp $APP_ROOT/.devpanel/drupal-settings.php $SETTINGS_FILES_PATH
fi
