<?php
/**
 * Handle hooks relating to plugin data.
 *
 * @package Checkout Fields and File Upload
 */

namespace CFFU_Plugin;

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
		register_activation_hook( CFFU_PLUGIN_FILE, [ __CLASS__, 'install' ] );
		add_action( 'woocommerce_cleanup_sessions', [ __CLASS__, 'cleanup_sessions' ] );
	}

	/**
	 * Populate a newly created WooCommerce order.
	 *
	 * @param WC_Order $order The Woocommerce order.
	 * @param array    $data Posted data.
	 */
	public static function populate_order( $order, $data ) {
		$fields    = get_option( 'cffu_fields' );
		$meta_data = [];

		foreach ( $fields as &$field ) {
			$id         = wp_unslash( $field['id'] );
			$input_name = Display::NAME_PREFIX . $id;

			if (
				isset( WC()->session->cffu_file_uploads ) &&
				array_key_exists( $id, WC()->session->cffu_file_uploads ) &&
				! empty( WC()->session->cffu_file_uploads[ $id ] )
			) {
				$meta_data[ $id ] = [
					'type' => 'file',
					'data' => WC()->session->cffu_file_uploads[ $id ],
				];

				continue;
			}

			if ( ! empty( $data[ $input_name ] ) ) {
				$meta_data[ $id ] = [
					'type' => $field['type'],
					'data' => sanitize_text_field( wp_unslash( $data[ $input_name ] ) ),
				];
			}
		}

		if ( ! empty( $meta_data ) ) {
			$order->add_meta_data( 'cffu_responses', $meta_data, true );
		}

		Tracked_Files::untrack_session( WC()->session->get_customer_id() );
		unset( WC()->session->cffu_file_uploads );
	}

	/**
	 * Remove uploaded files before an order is deleted.
	 *
	 * @param int $id The post or order ID.
	 */
	public static function before_order_delete( $id ) {
		if ( ! OrderUtil::is_order( $id ) ) {
			return;
		}

		$order     = wc_get_order( $id );
		$meta_data = $order->get_meta( 'cffu_responses' );

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
