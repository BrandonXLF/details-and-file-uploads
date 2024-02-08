<?php
/**
 * Uninstall hook for Checkout Fields and File Upload.
 *
 * @package Checkout Fields and File Upload
 **/

namespace CFFU_Plugin;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once 'src/includes/class-autoloader.php';

Uploads::uninstall();
Tracked_Files::uninstall();

$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'cffu_%'" );

wp_cache_flush();
