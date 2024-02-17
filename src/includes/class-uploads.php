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
 * Manager for the plugin's upload directory.
 */
class Uploads {
	const UPLOAD_DIR = '/cffu_file_uploads';

	/**
	 * Get the full upload path for plugin uploads.
	 *
	 * @return string The upload path.
	 */
	public static function get_upload_path() {
		return wp_upload_dir()['basedir'] . self::UPLOAD_DIR;
	}

	/**
	 * Ensure the upload directory exists and is configured properly.
	 */
	public static function ensure_directory() {
		WP_Filesystem();
		global $wp_filesystem;

		if ( ! $wp_filesystem->exists( self::get_upload_path() ) ) {
			$wp_filesystem->mkdir( self::get_upload_path() );
		}

		// Make sure directory listing is inaccessible.
		$wp_filesystem->put_contents( self::get_upload_path() . '/index.html', '' );
	}

	/**
	 * Delete an uploaded file.
	 *
	 * @param string $path The file's path.
	 */
	public static function delete_file( $path ) {
		wp_delete_file( $path );
	}

	/**
	 * Remove the plugin upload directory and all files in it.
	 */
	public static function uninstall() {
		if ( ! is_dir( self::get_upload_path() ) ) {
			return;
		}

		WP_Filesystem();
		global $wp_filesystem;

		$it = new \RecursiveDirectoryIterator( self::get_upload_path(), \FilesystemIterator::SKIP_DOTS );
		$it = new \RecursiveIteratorIterator( $it, \RecursiveIteratorIterator::CHILD_FIRST );

		foreach ( $it as $file ) {
			if ( $file->isDir() ) {
				$wp_filesystem->rmdir( $file->getPathname() );
			} else {
				wp_delete_file( $file->getPathname() );
			}
		}

		$wp_filesystem->rmdir( self::get_upload_path() );
	}
}
