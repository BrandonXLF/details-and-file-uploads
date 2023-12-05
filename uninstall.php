<?php
/**
 * Uninstall hook for Details and File Upload.
 *
 * @package Details and File Upload
 **/

namespace DetailsAndFileUploadPlugin;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once 'src/includes/class-autoloader.php';

Uploads::uninstall();
Tracked_Files::uninstall();

$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'details_and_file_uploads_%'" );

wp_cache_flush();
