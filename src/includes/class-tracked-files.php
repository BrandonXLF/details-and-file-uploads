<?php
/**
 * Tracker for files not attached to an order.
 *
 * @package Checkout Fields and File Upload
 */

namespace CFFU_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracker for files not attached to an order.
 */
class Tracked_Files {
	/**
	 * Set up tracked files database.
	 */
	public static function setup() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta(
			"CREATE TABLE {$wpdb->prefix}cffu_tracked_file_uploads (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				session_id char(32) NOT NULL,
				file_path varchar(255) NOT NULL,
				PRIMARY KEY (id)
			) {$wpdb->get_charset_collate()}"
		);
	}

	/**
	 * Track a file.
	 *
	 * @param int | string $session The session ID.
	 * @param string       $path The path of the file.
	 */
	public static function track_file( $session, $path ) {
		global $wpdb;

		$wpdb->insert(
			"{$wpdb->prefix}cffu_tracked_file_uploads",
			[
				'session_id' => $session,
				'file_path'  => $path,
			]
		);
	}

	/**
	 * Untrack and delete a file.
	 *
	 * @param int | string $session The session ID.
	 * @param string       $path The path of the file.
	 */
	public static function delete_file( $session, $path ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}cffu_tracked_file_uploads",
			[
				'session_id' => $session,
				'file_path'  => $path,
			]
		);

		Uploads::delete_file( $path );
	}

	/**
	 * Untrack all files for a session.
	 *
	 * @param int | string $session The session ID.
	 */
	public static function untrack_session( $session ) {
		global $wpdb;

		$wpdb->delete( "{$wpdb->prefix}cffu_tracked_file_uploads", [ 'session_id' => $session ] );
	}

	/**
	 * Delete files attached to non-existent sessions.
	 */
	public static function cleanup() {
		global $wpdb;

		$results = $wpdb->get_results(
			"SELECT file_path FROM {$wpdb->prefix}cffu_tracked_file_uploads files
            LEFT JOIN {$wpdb->prefix}woocommerce_sessions wc ON wc.session_key = files.session_id
            WHERE wc.session_key IS NULL"
		);

		foreach ( $results as $result ) {
			Uploads::delete_file( $result->file_path );
		}

		$wpdb->query(
			"DELETE files FROM {$wpdb->prefix}cffu_tracked_file_uploads files
            LEFT JOIN {$wpdb->prefix}woocommerce_sessions wc ON wc.session_key = files.session_id
            WHERE wc.session_key IS NULL"
		);
	}

	/**
	 * Remove the database and remove any tracked files.
	 */
	public static function uninstall() {
		global $wpdb;
		$results = $wpdb->get_results( "SELECT file_path FROM {$wpdb->prefix}cffu_tracked_file_uploads files" );

		foreach ( $results as $result ) {
			Uploads::delete_file( $result->file_path );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE {$wpdb->prefix}cffu_tracked_file_uploads" );
	}
}
