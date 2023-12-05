<?php
/**
 * Tests for the Data_Hooks class.
 *
 * @package Details and File Upload
 */

namespace DetailsAndFileUploadPlugin;

/**
 * Tests for the Data_Hooks class.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Data_Hooks_Tracked_Files_Tests extends \WP_UnitTestCase {
	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	public function test_cleanup_sessions() {
		$mock = \Mockery::mock( 'overload:' . Tracked_Files::class );
		$mock->expects( 'cleanup' )->once();

		do_action( 'woocommerce_cleanup_sessions' );
	}

	public function test_activate() {
		$mock = \Mockery::mock( 'overload:' . Tracked_Files::class );
		$mock->expects( 'setup' )->once();

		do_action( 'activate_' . plugin_basename( realpath( dirname( __DIR__ ) . '/details-and-file-upload.php' ) ) );
	}

	public function test_deactivate() {
		$mock = \Mockery::mock( 'overload:' . Tracked_Files::class );
		$mock->expects( 'uninstall' )->once();

		do_action( 'deactivate_' . plugin_basename( realpath( dirname( __DIR__ ) . '/details-and-file-upload.php' ) ) );
	}
}
