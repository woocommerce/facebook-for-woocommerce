#!/bin/sh

PLUGIN_SLUG="facebook-for-woocommerce"
PROJECT_PATH=$(pwd)
BUILD_PATH="${PROJECT_PATH}/build"
FINAL_ZIP="${PROJECT_PATH}/${PLUGIN_SLUG}.zip"

echo $PROJECT_PATH

echo "Removing last zip..."
rm $FINAL_ZIP

echo "Installing PHP and JS dependencies..."
npm ci || exit

echo "Building @wordpress/scripts assets..."
npm run build:assets

# Sake installs composer and makes a ZIP
echo "Building zip with Sake..."
npx sake zip || exit

echo "Copying zip to project folder..."
find $BUILD_PATH -name "facebook-for-woocommerce.*.zip" -exec cp {} $FINAL_ZIP \; || exit;

echo "Build done!"
