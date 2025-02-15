=== Checkout Fields and File Upload for WooCommerce ===
Contributors: brandonxlf
Tags: woocommerce,file upload,checkout,order details
Donate link: https://www.brandonfowler.me/donate/
Tested up to: 6.7
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily add general or item-specific detail inputs and file uploads to the WooCommerce checkout page's additional information section.

== Description ==

Checkout Fields and File Upload for WooCommerce allows you to easily add custom fields to the WooCommerce checkout.

= Product-Specific Fields =

These fields can be configured only to appear when specific items or categories of items are in the cart or to appear for all items. When enabled, fields are added to the to the WooCommerce checkout field above the order notes field.

= Multiple Input Types =

You can add custom input fields of a multitude of different types include text, multiline, date, password, number, file, and more!

= Secure File Uploads =

Files are uploaded to a secure directory and are given a randomly generated name to prevent unauthorized access.

== Contribute ==

Check out [the GitHub repository](https://github.com/BrandonXLF/fields-and-file-upload) to learn more about how you can contribute to the plugin's development.

== Installation ==

= Requirements =

* WordPress 4.6 or newer
* PHP 7.0 or greater is required (PHP 8.0 or greater is recommended)

= Steps =

1. Navigate to the "Add New Plugin" menu item
2. Click "Upload Plugin" and upload the zip file
3. Activate the plugin through the "Plugins" menu in WordPress

== Screenshots ==

1. Custom fields in the WooCommerce checkout.
2. Custom field responses on the order confirmation page.
3. The settings page to manage custom fields.

== Changelog ==

= 1.2.2 =

Updated plugin information

= 1.2.1 =

- Mark woocommerce as a required plugin
- Change default response table title to "Additional details"

= 1.2.0 =

- Require value when adding to list input on the settings page
- Show message when there are no fields on the settings page
- Wrap output in a section tag
- Improve order received page styling
- Show meta box for HPOS orders
- Stop adding extra dot to generated file names
- Add a clear button to the file input
- Add "cffu-table-title" class to response table titles
- Add option to customize response table title
- Don't save empty response meta data and file submissions
- Add prefix to checkout page inputs

= 1.1.7 =

- Allow fields to be displayed when the WooCommerce cart is undefined.
- Add spacing borders to the WooCommerce details page.

= 1.1.6 =

Made plugin labels on the admin dashboard more concise.

= 1.1.5 =

Only calculate allowed MIME types once.

= 1.1.4 =

Fixed an error on the settings page.

= 1.1.3 =

- Changed name to "Checkout Fields and File Upload for WooCommerce".
- Implemented changes recommended by the WordPress plugin review team.

= 1.1.2 =

- Permit custom file types.
- Fixed an issue with adding an item to an empty list on the settings page.

= 1.1.1 =

Default list settings to use an array.

= 1.1.0 =

Added file type filtering for uploads.

= 1.0.2 =

Corrected version numbers.

= 1.0.1 =

Cleanup plugin data during uninstall.

= 1.0.0 =

Initial release.
