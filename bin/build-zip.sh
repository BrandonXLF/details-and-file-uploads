#!/usr/bin/env bash

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
VERSION=$( grep -oP "^version: '\K[^']+" $SCRIPT_DIR/readme-template.yml )

cd $SCRIPT_DIR/..
git archive --format zip --output $SCRIPT_DIR/../details-and-file-upload.$VERSION.zip --prefix details-and-file-upload/ HEAD