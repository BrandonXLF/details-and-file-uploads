<?php
/**
 * Plugin AJAX APIs.
 *
 * @package Details and File Upload
 */

namespace DetailsAndFileUploadPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	return self::die();
}

/**
 * Plugin AJAX APIs.
 */
class API {
	/**
	 * Initialize plugin AJAX APIs.
	 */
	public static function init() {
		add_action( 'wp_ajax_dfu_file_upload', [ __CLASS__, 'upload_ajax' ] );
		add_action( 'wp_ajax_nopriv_dfu_file_upload', [ __CLASS__, 'upload_ajax' ] );
	}

	/**
	 * Handle end of request.
	 */
	private static function die() {
		if ( ! defined( 'DFU_TESTSUITE' ) ) {
			exit;
		}
	}

	/**
	 * Handle file upload.
	 */
	public static function upload_ajax() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'dfu-file-upload' ) ) {
			http_response_code( 401 );
			echo 'Failed to verify nonce.';
			return self::die();
		}

		if ( empty( $_POST['name'] ) ) {
			http_response_code( 400 );
			echo 'Missing field name.';
			return self::die();
		}

		$fields   = get_option( 'details_and_file_uploads_fields', [] );
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

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$files     = $_FILES['files'] ?? [];
		$to_upload = [];

		foreach ( $files['name'] ?? [] as $key => $name ) {
			$ext  = pathinfo( $name, PATHINFO_EXTENSION );
			$type = 'unknown';
			$info = wp_check_filetype_and_ext( $files['tmp_name'][ $key ], $files['name'][ $key ] );

			if ( $info['ext'] ) {
				$ext = $info['ext'];
			}

			if ( $info['type'] ) {
				$type = $info['type'];
			}

			if (
				count( $field['types'] ?? [] ) && 0 === count(
					array_filter(
						$field['types'],
						function ( $allowed_type ) use ( &$ext, &$type ) {
							return '.' . $ext === $allowed_type || substr( $type, 0, strlen( $allowed_type ) ) === $allowed_type;
						}
					)
				)
			) {
				http_response_code( 400 );
				echo 'File type/extension is not allowed.';
				return self::die();
			}

			array_push(
				$to_upload,
				[
					'old_name' => $name,
					'name'     => bin2hex( random_bytes( 15 ) ) . '.' . $ext,
					'type'     => $files['type'][ $key ],
					'tmp_name' => $files['tmp_name'][ $key ],
					'error'    => $files['error'][ $key ],
					'size'     => $files['size'][ $key ],
				]
			);
		}

		$data = [];

		foreach ( $to_upload as &$file ) {
			$processed_file = Uploads::add_file( $file );

			if ( array_key_exists( 'error', $processed_file ) ) {
				http_response_code( 400 );
				echo esc_html( $processed_file['error'] );
				return self::die();
			}

			Tracked_Files::track_file( WC()->session->get_customer_id(), $processed_file['file'] );

			array_push(
				$data,
				[
					'name' => wp_slash( $file['old_name'] ),
					'path' => wp_slash( $processed_file['file'] ),
					'url'  => wp_slash( $processed_file['url'] ),
				]
			);
		}

		$uploads = WC()->session->dfu_file_uploads ?? [];

		if ( isset( $uploads[ $field_id ] ) ) {
			foreach ( $uploads[ $field_id ] as &$file ) {
				Tracked_Files::delete_file( WC()->session->get_customer_id(), wp_unslash( $file['path'] ) );
			}
		}

		$uploads[ $field_id ]           = $data;
		WC()->session->dfu_file_uploads = $uploads;

		return self::die();
	}
}
