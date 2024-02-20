<?php
/**
 * Migrate Checkout Fields and File Upload from v1.1.2 to v1.1.3.
 *
 * @package Checkout Fields and File Upload
 */

namespace CFFU_Plugin;

require_once 'wp-load.php';
require_once 'wp-admin/includes/file.php';

/**
 * Rename an option.
 *
 * @param string $old_name The old name of the plugin.
 * @param string $new_name The new name of the plugin.
 */
function migrate_option( $old_name, $new_name ) {
	$value = get_option( $old_name, null );

	if ( null === $value ) {
		return;
	}

	add_option( $new_name, $value );
	delete_option( $old_name );
}

/**
 * Migrate from v1.1.2 to v1.1.3.
 */
function migrate_v112_v113() {
	global $wp_filesystem, $wpdb;

	// Rename plugin options.
	migrate_option( 'details_and_file_uploads_fields', 'cffu_fields' );
	migrate_option( 'details_and_file_uploads_hide_notes', 'cffu_hide_notes' );

	// Move uploads directory.
	$old_uploads = wp_upload_dir()['basedir'] . '/dfu_file_uploads';
	$new_uploads = wp_upload_dir()['basedir'] . '/cffu_file_uploads';

	WP_Filesystem();

	if ( $wp_filesystem->exists( $old_uploads ) ) {
		move_dir( $old_uploads, $new_uploads );
	}

	// Rename file uploads table.
	$wpdb->query(
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		"ALTER TABLE {$wpdb->prefix}dfu_tracked_file_uploads RENAME TO {$wpdb->prefix}cffu_tracked_file_uploads"
	);

	// Update file uploads table paths.
	$wpdb->query(
		"UPDATE {$wpdb->prefix}cffu_tracked_file_uploads
        SET file_path = REPLACE(file_path, \"dfu_file_uploads\", \"cffu_file_uploads\")"
	);

	// Update sessions.
	$wpdb->query(
		"UPDATE {$wpdb->prefix}woocommerce_sessions
        SET session_value = REPLACE(session_value, \"dfu_file_uploads\", \"cffu_file_uploads\")
        WHERE session_value LIKE \"%dfu_file_uploads%\""
	);

	// Update meta data name and paths for all orders.
	$orders = wc_get_orders( [ 'limit' => -1 ] );

	foreach ( $orders as &$order ) {
		$meta_data = $order->get_meta( 'details_and_file_uploads' );

		if ( ! $meta_data ) {
			return;
		}

		foreach ( $meta_data as &$item ) {
			if ( 'file' !== $item['type'] ) {
				continue;
			}

			foreach ( $item['data'] as &$file ) {
				$file['path'] = str_replace( 'dfu_file_uploads', 'cffu_file_uploads', $file['path'] );
				$file['url']  = str_replace( 'dfu_file_uploads', 'cffu_file_uploads', $file['url'] );
			}
		}

		$order->add_meta_data( 'cffu_responses', $meta_data, true );
		$order->delete_meta_data( 'details_and_file_uploads' );

		$order->save_meta_data();
	}
}

migrate_v112_v113();
