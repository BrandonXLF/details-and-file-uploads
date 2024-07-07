<?php
/**
 * This comment is generated by bin/build-readme.sh
 *
 * Checkout Fields and File Upload for WooCommerce
 *
 * @package     Checkout Fields and File Upload
 * @author      Brandon Fowler
 * @copyright   Brandon Fowler
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Checkout Fields and File Upload
 * Plugin URI: https://www.brandonfowler.me/fields-and-file-upload/
 * Description: Easily add general or item-specific detail inputs and file uploads to the WooCommerce checkout page's additional information section.
 * Version: 1.2.0
 * Requires at least: 4.6
 * Requires PHP: 7.0
 * Author: Brandon Fowler
 * Author URI: https://www.brandonfowler.me/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins: woocommerce
 **/

namespace CFFU_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CFFU_PLUGIN_FILE', __FILE__ );
// Generated by bin/build-readme.sh.
define( 'CFFU_PLUGIN_VERSION', '1.2.0' );

require_once 'src/includes/class-autoloader.php';

Autoloader::init();
Display::init();
Data_Hooks::init();
Upload_API::init();
Settings::init();
