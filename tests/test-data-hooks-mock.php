<?php
/**
 * Tests for the Data_Hooks class.
 *
 * @package Checkout Fields and File Upload
 */

namespace CFFU_Plugin;

/**
 * Tests for the Data_Hooks class.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Data_Hooks_Tracked_Files_Tests extends \WP_UnitTestCase {
	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	public function test_cleanup_sessios() {
		$mock = \Mockery::mock( 'overload:' . Tracked_Files::class );
		$call = $mock->expects( 'cleanup' )->once();

		do_action( 'woocommerce_cleanup_sessions' );

		$call->verify();
	}

	public function test_activate() {
		$mock = \Mockery::mock( 'overload:' . Tracked_Files::class );
		$call = $mock->expects( 'setup' )->once();

		do_action( 'activate_' . plugin_basename( realpath( dirname( __DIR__ ) . '/fields-and-file-upload.php' ) ) );

		$call->verify();
	}
}
