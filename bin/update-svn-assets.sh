#!/usr/bin/env bash

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
TOP_DIR=$( dirname -- SCRIPT_DIR )
VERSION=$( grep -oP "^version: '\K[^']+" $TOP_DIR/readme-template.yml )

cd $TOP_DIR

# Clone SVN repository
mkdir svn-repo
svn co https://plugins.svn.wordpress.org/fields-and-file-upload svn-repo

cd svn-repo

# Move assets into SVN
rsync -rc "../assets/" assets/ --delete

# Add all files to SVN
svn add . --force

# Remove deleted files from SVN
svn status | grep '^\!' | sed 's/!M* *//' | xargs -I% svn rm %@

svn update
svn commit -m "Updating assets" $@