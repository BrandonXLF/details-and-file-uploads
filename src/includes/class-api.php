<?php
/**
 * Plugin AJAX APIs.
 *
 * @package Details and File Upload
 */

namespace DetailsAndFileUploadPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin AJAX APIs.
 */
class API {
	/**
	 * Initialize plugin AJAX APIs.
	 */
	public static function init() {
		add_action( 'wc_ajax_dfu_file_upload', [ __CLASS__, 'upload_ajax' ] );
	}

	/**
	 * Handle file upload.
	 */
	public static function upload_ajax() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'dfu-file-upload' ) ) {
			echo 'Failed to verify nonce.';
			http_response_code( 401 );
			return;
		}

		if ( empty( $_POST['name'] ) ) {
			echo 'Missing field name.';
			http_response_code( 400 );
			return;
		}

		if ( empty( $_FILES['files'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$files = $_FILES['files'];
		$data  = [];

		foreach ( $files['name'] as $key => $name ) {
			$ext = pathinfo( $name )['extension'];

			$file = [
				'name'     => bin2hex( random_bytes( 15 ) ) . '.' . $ext,
				'type'     => $files['type'][ $key ],
				'tmp_name' => $files['tmp_name'][ $key ],
				'error'    => $files['error'][ $key ],
				'size'     => $files['size'][ $key ],
			];

			$processed_file = Uploads::add_file( $file );

			if ( array_key_exists( 'error', $processed_file ) ) {
				echo esc_html( $processed_file['error'] );
				http_response_code( 400 );
				return;
			}

			Tracked_Files::track_file( WC()->session->get_customer_id(), $processed_file['file'] );

			array_push(
				$data,
				[
					'name' => wp_slash( $name ),
					'path' => wp_slash( $processed_file['file'] ),
					'url'  => wp_slash( $processed_file['url'] ),
					'type' => wp_slash( $processed_file['type'] ),
				]
			);
		}

		if ( ! isset( WC()->session->dfu_file_uploads ) ) {
			WC()->session->dfu_file_uploads = [];
		}

		$name = sanitize_text_field( wp_unslash( $_POST['name'] ) );

		$uploads                        = WC()->session->dfu_file_uploads;
		$uploads[ $name ]               = $data;
		WC()->session->dfu_file_uploads = $uploads;
	}
}
