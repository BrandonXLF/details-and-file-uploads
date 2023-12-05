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
 * Manager for the plugin's upload directory.
 */
class Uploads {
	const UPLOAD_DIR = '/dfu_file_uploads';

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
	 * Override upload directory.
	 *
	 * @param array $param Array of information about the upload directory.
	 * @return array The same array with values overridden.
	 */
	public static function override_upload_dir( $param ) {
		$param['path'] = $param['basedir'] . '/dfu_file_uploads';
		$param['url']  = $param['baseurl'] . '/dfu_file_uploads';

		return $param;
	}

	/**
	 * Add an uploaded file.
	 *
	 * @param array $file Reference to a single element of $_FILES.
	 * @return array See wp_handle_upload's for return value.
	 */
	public static function add_file( &$file ) {
		self::ensure_directory();

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		add_filter( 'upload_dir', [ self::class, 'override_upload_dir' ] );

		$ret = wp_handle_upload(
			$file,
			[
				'test_form' => false,
				'action'    => defined( 'DFU_TESTSUITE' ) ? 'test' : null,
			]
		);

		remove_filter( 'upload_dir', [ self::class, 'override_upload_dir' ] );

		return $ret;
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

		$it = new \RecursiveDirectoryIterator( self::get_upload_path(), \FilesystemIterator::SKIP_DOTS );
		$it = new \RecursiveIteratorIterator( $it, \RecursiveIteratorIterator::CHILD_FIRST );

		foreach ( $it as $file ) {
			if ( $file->isDir() ) {
				rmdir( $file->getPathname() );
			} else {
				unlink( $file->getPathname() );
			}
		}

		rmdir( self::get_upload_path() );
	}
}
