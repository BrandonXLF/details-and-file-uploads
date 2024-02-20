<?php
/**
 * Plugin AJAX APIs.
 *
 * @package Checkout Fields and File Upload
 */

namespace CFFU_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	return self::die();
}

/**
 * Plugin AJAX APIs.
 */
class Upload_API {
	/**
	 * Initialize plugin AJAX APIs.
	 */
	public static function init() {
		add_action( 'wp_ajax_cffu_file_upload', [ __CLASS__, 'process' ] );
		add_action( 'wp_ajax_nopriv_cffu_file_upload', [ __CLASS__, 'process' ] );
	}

	/**
	 * Handle end of request.
	 */
	private static function die() {
		if ( ! defined( 'CFFU_TESTSUITE' ) ) {
			exit;
		}
	}

	/**
	 * Get the permitted MIMES for a field.
	 *
	 * @param array $field The field.
	 * @return array MIME map with types permitted for the current field.
	 */
	public static function get_permitted_mimes( &$field ) {
		$base_mimes = get_allowed_mime_types();

		if ( ! count( $field['types'] ?? [] ) ) {
			return $base_mimes;
		}

		$permitted_mimes = [];

		foreach ( $field['types'] as &$allowed_type ) {
			// Add permitted extension to the list.
			if ( substr( $allowed_type, 0, 1 ) === '.' ) {
				$ext  = substr( $allowed_type, 1 );
				$type = false;

				// Get the type of the permitted extension.
				foreach ( $base_mimes as $ext_preg => $mime_match ) {
					$ext_preg = '!^(' . $ext_preg . ')$!i';

					if ( preg_match( $ext_preg, $ext, $ext_matches ) ) {
						$type = $mime_match;
						break;
					}
				}

				// Custom type not known to WordPress.
				if ( ! $type ) {
					$type = 'application/cffu-custom';
				}

				$permitted_mimes[ $ext ] = $type;

				continue;
			}

			// Add mimes starting with the allowed type to the list.
			$permitted_mimes = array_merge(
				$permitted_mimes,
				array_filter(
					$base_mimes,
					function ( $type ) use ( &$allowed_type ) {
						return substr( $type, 0, strlen( $allowed_type ) ) === $allowed_type;
					}
				)
			);
		}

		return $permitted_mimes;
	}

	/**
	 * Override upload directory.
	 *
	 * @param array $param Array of information about the upload directory.
	 * @return array The same array with values overridden.
	 */
	public static function override_upload_dir( $param ) {
		$param['path'] = $param['basedir'] . Uploads::UPLOAD_DIR;
		$param['url']  = $param['baseurl'] . Uploads::UPLOAD_DIR;

		return $param;
	}

	/**
	 * Override wp_check_filetype_and_ext to permit custom file extensions.
	 *
	 * @param array         $ret The value returned by wp_check_filetype_and_ext.
	 * @param array         $file_ Full path to the file.
	 * @param string        $filename The name of the file.
	 * @param string[]|null $mimes Array of mime types keyed by their file extension regex, or null if none were provided.
	 */
	public static function override_wp_check_filetype_and_ext( $ret, $file_, $filename, $mimes ) {
		$wp_filetype = wp_check_filetype( $filename, $mimes );
		$ext         = $wp_filetype['ext'];
		$type        = $wp_filetype['type'];

		if ( 'application/cffu-custom' === $wp_filetype['type'] ) {
			return [
				'ext'             => $ext,
				'type'            => $type,
				'proper_filename' => false,
			];
		}

		return $ret;
	}

	/**
	 * Process the API call.
	 *
	 * @return array Array containing elements with info about the uploaded files.
	 */
	public static function process() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'cffu-file-upload' ) ) {
			http_response_code( 401 );
			echo 'Failed to verify nonce.';
			return self::die();
		}

		if ( empty( $_POST['name'] ) ) {
			http_response_code( 400 );
			echo 'Missing field name.';
			return self::die();
		}

		$fields   = get_option( 'cffu_fields', [] );
		$field_id = sanitize_text_field( wp_unslash( $_POST['name'] ) );

		$field = array_values(
			array_filter(
				$fields,
				function ( $field ) use ( &$field_id ) {
					return $field_id === $field['id'] && 'file' === $field['type'];
				}
			)
		)[0] ?? false;

		if ( ! $field ) {
			http_response_code( 400 );
			echo 'Invalid field "' . esc_html( $field_id ) . '" for file upload.';
			return self::die();
		}

		$mimes = self::get_permitted_mimes( $field );

		Uploads::ensure_directory();

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$original_names = [];
		$data           = [];

		add_filter( 'upload_dir', [ __CLASS__, 'override_upload_dir' ], PHP_INT_MAX );
		add_filter( 'wp_check_filetype_and_ext', [ __CLASS__, 'override_wp_check_filetype_and_ext' ], PHP_INT_MAX, 4 );

		// Unsanitized file array has to be converted into individual files before being passed to wp_handle_upload.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$files_unsanitized = $_FILES['files'] ?? [];

		foreach ( $files_unsanitized['name'] ?? [] as $key => $_ ) {
			$file_unsanitized = [
				'name'     => $files_unsanitized['name'][ $key ],
				'type'     => $files_unsanitized['type'][ $key ],
				'size'     => $files_unsanitized['size'][ $key ],
				'tmp_name' => $files_unsanitized['tmp_name'][ $key ],
				'error'    => $files_unsanitized['error'][ $key ],
			];

			$processed_file = wp_handle_upload(
				$file_unsanitized,
				[
					'test_form'                => false,
					// Tests fail the is_uploaded_file test.
					'action'                   => defined( 'CFFU_TESTSUITE' ) ? 'test' : null,
					'unique_filename_callback' => function ( $_, $original_name, $ext ) use ( &$original_names ) {
						$name                    = bin2hex( random_bytes( 15 ) ) . '.' . $ext;
						$original_names[ $name ] = sanitize_file_name( $original_name );
						return $name;
					},
					'mimes'                    => $mimes,
				]
			);

			if ( array_key_exists( 'error', $processed_file ) ) {
				http_response_code( 400 );
				echo esc_html( $processed_file['error'] );
				return self::die();
			}

			Tracked_Files::track_file( WC()->session->get_customer_id(), $processed_file['file'] );

			array_push(
				$data,
				[
					'name' => wp_slash( $original_names[ basename( $processed_file['file'] ) ] ),
					'path' => wp_slash( $processed_file['file'] ),
					'url'  => wp_slash( $processed_file['url'] ),
				]
			);
		}

		remove_filter( 'upload_dir', [ __CLASS__, 'override_upload_dir' ], PHP_INT_MAX );
		remove_filter( 'wp_check_filetype_and_ext', [ __CLASS__, 'override_wp_check_filetype_and_ext' ], PHP_INT_MAX );

		$uploads = WC()->session->cffu_file_uploads ?? [];

		if ( isset( $uploads[ $field_id ] ) ) {
			foreach ( $uploads[ $field_id ] as &$file ) {
				Tracked_Files::delete_file( WC()->session->get_customer_id(), wp_unslash( $file['path'] ) );
			}
		}

		$uploads[ $field_id ]            = $data;
		WC()->session->cffu_file_uploads = $uploads;

		return self::die();
	}
}
