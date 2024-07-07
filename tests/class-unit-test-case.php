<?php
/**
 * Base CFFU abstract test class.
 *
 * @package Checkout Fields and File Upload
 */

namespace CFFU_Plugin;

/**
 * Base CFFU abstract test class.
 */
abstract class Unit_Test_Case extends \WP_UnitTestCase {
	public function create_order_with_responses( $responses = [] ) {
		$order = wc_create_order();

		$order->add_meta_data( 'cffu_responses', $responses, true );
		$order->save();

		return $order;
	}
}
