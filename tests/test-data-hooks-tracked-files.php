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
		$mock = \Mockery::mock('overload:' . Tracked_Files::class);
		$mock->expects('cleanup')->once();

		do_action( 'woocommerce_cleanup_sessions' );
	}
}
