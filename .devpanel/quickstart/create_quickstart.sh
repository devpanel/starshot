#!/bin/bash
# ---------------------------------------------------------------------
# Copyright (C) 2021 DevPanel
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

echo -e "-------------------------------"
echo -e "| DevPanel Quickstart Creator |"
echo -e "-------------------------------\n"


# Preparing
WORK_DIR=$APP_ROOT
TMP_DIR=/tmp/devpanel/quickstart
DUMPS_DIR=$TMP_DIR/dumps
STATIC_FILES_DIR=$WEB_ROOT/$(drush php-eval "echo \Drupal\Core\StreamWrapper\PublicStream::basePath();") # Drupal 8 ways

mkdir -p $DUMPS_DIR
#== Install needed tool
if [[ ! -n $(which yq) ]]; then
    echo -e "> Install needed tool"
    wget https://github.com/mikefarah/yq/releases/download/v4.2.0/yq_linux_amd64.tar.gz -O - -q | \
    tar xz && sudo mv yq_linux_amd64 /usr/bin/yq
fi

# Step 1 - Compress drupal database
cd $WORK_DIR
echo -e "> Export database using drupal/drush"
drush cr --quiet
drush sql-dump > $TMP_DIR/$DB_NAME.sql --extra=--no-tablespaces

echo -e "> Compress database"
tar czf $DUMPS_DIR/db.sql.tgz -C $TMP_DIR $DB_NAME.sql

echo -e "> Store database to $APP_ROOT/.devpanel/dumps"
mkdir -p $APP_ROOT/.devpanel/dumps
mv $DUMPS_DIR/db.sql.tgz $APP_ROOT/.devpanel/dumps/db.sql.tgz

# Step 2 - Compress static files
cd $WORK_DIR
echo -e "> Compress static files"
tar czf $DUMPS_DIR/files.tgz -C $STATIC_FILES_DIR .

echo -e "> Store files.tgz to $APP_ROOT/.devpanel/dumps"
mkdir -p $APP_ROOT/.devpanel/dumps
mv $DUMPS_DIR/files.tgz $APP_ROOT/.devpanel/dumps/files.tgz

# Step 3 - Commit dumps files to git
if [[ -f "$APP_ROOT/.devpanel/dumps/files.tgz" ]]; then
    git add $APP_ROOT/.devpanel/dumps/files.tgz &> /dev/null
fi

if [[ -f "$APP_ROOT/.devpanel/dumps/db.sql.tgz" ]]; then
    git add $APP_ROOT/.devpanel/dumps/db.sql.tgz &> /dev/null
fi

if [[ -f "$APP_ROOT/.devpanel/quickstart/quickstart.url" ]]; then
    git add $APP_ROOT/.devpanel/quickstart/quickstart.url &> /dev/null
fi

export GIT_AUTHOR_NAME="DevPanel Bot"
export GIT_AUTHOR_EMAIL="admin@devpanel.com"
export GIT_COMMITTER_NAME="DevPanel Bot"
export GIT_COMMITTER_EMAIL="admin@devpanel.com"

git commit -m "Generate quickstart" &> /dev/null

git push origin $GIT_BRANCH &> /dev/null
