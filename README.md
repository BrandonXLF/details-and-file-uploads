# Checkout Fields and File Upload for WooCommerce

**Easily add general or item-specific detail inputs and file uploads to the WooCommerce checkout page's additional information section.**

<a href="https://wordpress.org/plugins/fields-and-file-upload/">WordPress.org listing</a>

<img src="assets/screenshot-1.png" width="500px" alt="Custom fields in the WooCommerce checkout.">

## Description

Checkout Fields and File Upload for WooCommerce allows you to easily add custom fields to the WooCommerce checkout.

### Product-Specific Fields

These fields can be configured only to appear when specific items or categories of items are in the cart or to appear for all items. When enabled, fields are added to the to the WooCommerce checkout field above the order notes field.

### Multiple Input Types

You can add custom input fields of a multitude of different types include text, multiline, date, password, number, file, and more!

### Secure File Uploads

Files are uploaded to a secure directory and are given a randomly generated name to prevent unauthorized access.

## Installation

### Requirements

* WordPress 4.6 or newer
* PHP 7.0 or greater is required (PHP 8.0 or greater is recommended)

### Steps

1. Upload this folder to your plugin directory (`wp-content/plugins`)
2. Activate the plugin through the "Plugins" menu in WordPress

## Developing

### Installing dependencies

Composer is used to install dependencies. Once it is installed run `composer install` to install them.

### Running tests

Tests are run with a installation of WordPress, WooCommerce, and the WordPress test tools. These can be installed with `bin/install-wp-tests.sh [DATABASE NAME] [DATABASE USER] [DATABASE PASSWORD]`. This script requires Subversion to be installed. Once installed, run `composer run tests` to run tests.

### Code sniffing

Run `composer run phpcs` to run the code sniffer. Run `composer run phpcs:full` for a full breakdown of what errors occurred and `composer run phpcs:fix` to fix any auto-fixable errors.

### Creating ZIP archive

Run `bin/build-zip.sh` to create a ZIP archive of the plugin that is suitable to be uploaded to a WordPress site.

### Publishing

Run `bin/release-svn.sh` to create a release version and add it as a tag to SVN. Run `bin/update-svn-assets.sh` to update wordpress.org assets only. You can specify your SVN username with `--username USERNAME`.
