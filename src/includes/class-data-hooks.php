<?php
/**
 * Handle hooks relating to plugin data.
 *
 * @package Details and File Upload
 */

namespace DetailsAndFileUploadPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Handle hooks relating to plugin data.
 */
class Data_Hooks {
	/**
	 * Initialize hook actions.
	 */
	public static function init() {
		add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'populate_order' ], 10, 2 );
		add_action( 'before_delete_post', [ __CLASS__, 'before_order_delete' ] );
		add_action( 'woocommerce_before_delete_order', [ __CLASS__, 'before_order_delete' ] );
		register_activation_hook( DETAILS_AND_FILE_UPLOAD_PLUGIN_FILE, [ __CLASS__, 'install' ] );
		add_action( 'woocommerce_cleanup_sessions', [ __CLASS__, 'cleanup_sessions' ] );
	}

	/**
	 * Populate a newly created WooCommerce order.
	 *
	 * @param WC_Order $order The Woocommerce order.
	 * @param array    $data Posted data.
	 */
	public static function populate_order( $order, $data ) {
		$fields = get_option( 'details_and_file_uploads_fields', [] );

		foreach ( $fields as &$field ) {
			$id = wp_unslash( $field['id'] );

			if (
				isset( WC()->session->dfu_file_uploads ) &&
				array_key_exists( $id, WC()->session->dfu_file_uploads )
			) {
				$meta_data[ $id ] = [
					'type' => 'file',
					'data' => WC()->session->dfu_file_uploads[ $id ],
				];

				continue;
			}

			if ( ! empty( $data[ $id ] ) ) {
				$meta_data[ $id ] = [
					'type' => $field['type'],
					'data' => sanitize_text_field( wp_unslash( $data[ $id ] ) ),
				];
			}
		}

		$order->add_meta_data( 'details_and_file_uploads', $meta_data, true );

		Tracked_Files::untrack_session( WC()->session->get_customer_id() );
		unset( WC()->session->dfu_file_uploads );
	}

	/**
	 * Remove uploaded files before an order is deleted.
	 *
	 * @param int $post_id The post ID.
	 */
	public static function before_order_delete( $post_id ) {
		if ( ! OrderUtil::is_order( $post_id ) ) {
			return;
		}

		$order     = wc_get_order( $post_id );
		$meta_data = $order->get_meta( 'details_and_file_uploads' );

		foreach ( $meta_data as $data ) {
			if ( 'file' !== $data['type'] ) {
				continue;
			}

			foreach ( $data['data'] as $file ) {
				wp_delete_file( $file['path'] );
			}
		}
	}

	/**
	 * Handle plugin install.
	 */
	public static function install() {
		Tracked_Files::setup();
	}

	/**
	 * Handle WooCommerce session cleanup.
	 */
	public static function cleanup_sessions() {
		Tracked_Files::cleanup();
	}
}
