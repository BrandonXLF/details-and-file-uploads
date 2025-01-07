#!/usr/bin/env bash

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
TOP_DIR=$( dirname -- SCRIPT_DIR )
VERSION=$( grep -oP " \* Version: *\K.+" $TOP_DIR/fields-and-file-upload.php )

cd $TOP_DIR
git archive --format zip --output $TOP_DIR/fields-and-file-upload.$VERSION.zip --prefix fields-and-file-upload/ HEAD