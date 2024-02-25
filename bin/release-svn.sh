#!/usr/bin/env bash

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
TOP_DIR=$( dirname -- SCRIPT_DIR )
VERSION=$( grep -oP "^version: '\K[^']+" $TOP_DIR/readme-template.yml )

cd $TOP_DIR

# Clone SVN repository
mkdir svn-repo
svn co https://plugins.svn.wordpress.org/fields-and-file-upload svn-repo

# Archive main branch to SVN trunk
mkdir build
git archive --format tar HEAD | tar x --directory="build"
rsync -rc build/ svn-repo/trunk/ --delete
rm -r build

cd svn-repo

# Make sure version doesn't exist already
if [ -d "tags/$VERSION" ]; then
	echo "Tag $VERSION already exists!";
	exit
fi

# Move assets into SVN
rsync -rc "../assets/" assets/ --delete

# Add all files to SVN
svn add . --force

# Remove deleted files from SVN
svn status | grep '^\!' | sed 's/!M* *//' | xargs -I% svn rm %@

# Create SVN version tag
svn cp trunk "tags/$VERSION"

svn update
svn commit -m "Version $VERSION" $@

if [ $? != 0 ]; then
    echo "SVN commit failed! Deleting tag"
    svn rm tags/$VERSION --force
fi