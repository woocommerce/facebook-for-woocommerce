#!/bin/sh

# This script can only be run from the project directory

PLUGIN_SLUG="facebook-for-woocommerce"
PROJECT_PATH=$(pwd)
BUILD_PATH="${PROJECT_PATH}/build"
SAKE_ZIP="${BUILD_PATH}/facebook-for-woocommerce.*.zip"
FINAL_ZIP="${PROJECT_PATH}/${PLUGIN_SLUG}.zip"

echo "Removing last zip..."
rm $FINAL_ZIP
rm $SAKE_ZIP

echo "Installing JS dependencies..."
npm ci || exit

echo "Building @wordpress/scripts assets..."
npm run build:assets || exit

# Sake installs composer and makes a ZIP
echo "Building zip with Sake..."
npx sake zip || exit

echo "Copying zip to project folder..."
cp $SAKE_ZIP $FINAL_ZIP || exit

echo "Build done!"
