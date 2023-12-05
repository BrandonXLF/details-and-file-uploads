<?php
/**
 * Tracker for files not attached to an order.
 *
 * @package Details and File Upload
 */

namespace DetailsAndFileUploadPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracker for files not attached to an order.
 */
class Tracked_Files {
	/**
	 * Get the table name.
	 *
	 * @return string The table name.
	 */
	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'dfu_tracked_file_uploads';
	}

	/**
	 * Set up tracked files database.
	 */
	public static function setup() {
		global $wpdb;

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta(
			"CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				session_id char(32) NOT NULL,
				file_path varchar(255) NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate"
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
			self::table_name(),
			[
				'session_id' => $session,
				'file_path'  => $path,
			]
		);
	}

	/**
	 * Untrack all files for a session.
	 *
	 * @param int | string $session The session ID.
	 */
	public static function untrack_session( $session ) {
		global $wpdb;

		$wpdb->delete( self::table_name(), [ 'session_id' => $session ] );
	}

	/**
	 * Delete files attached to non-existent sessions.
	 */
	public static function cleanup() {
		global $wpdb;

		$table_name = self::table_name();

		$results = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT file_path FROM $table_name files
            LEFT JOIN {$wpdb->prefix}woocommerce_sessions wc ON wc.session_key = files.session_id
            WHERE wc.session_key IS NULL"
		);

		foreach ( $results as $result ) {
			Uploads::delete_file( $result->file_path );
		}

		$wpdb->query(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"DELETE files FROM $table_name files
            LEFT JOIN {$wpdb->prefix}woocommerce_sessions wc ON wc.session_key = files.session_id
            WHERE wc.session_key IS NULL"
		);
	}


	/**
	 * Remove the database and remove any tracked files.
	 */
	public static function uninstall() {
		global $wpdb;

		$table_name = self::table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results( "SELECT file_path FROM $table_name files" );

		foreach ( $results as $result ) {
			Uploads::delete_file( $result->file_path );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE $table_name" );
	}
}
