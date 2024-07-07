<?php
/**
 * Tests for the Data_Hooks class.
 *
 * @package Checkout Fields and File Upload
 */

namespace CFFU_Plugin;

/**
 * Tests for uninstall.php
 */
class Uninstall_Tests extends Unit_Test_Case {
	public function test_uninstall_script() {
		update_option(
			'cffu_fields',
			[
				[
					'id'         => 'foo',
					'type'       => 'text',
					'label'      => 'Foo',
					'required'   => true,
					'products'   => [],
					'categories' => [],
				],
			]
		);

		update_option( 'cffu_hide_notes', false );

		Tracked_Files::setup();

		Uploads::ensure_directory();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				Uploads::get_upload_path() . '/ty345r5fdg4j6cf.png'
			)
		);

		global $wpdb;

		define( 'WP_UNINSTALL_PLUGIN', true );
		require 'uninstall.php';

		$this->assertNull( get_option( 'cffu_fields', null ) );
		$this->assertNull( get_option( 'cffu_hide_notes', null ) );

		$this->assertEmpty(
			$wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( "{$wpdb->prefix}cffu_tracked_file_uploads" ) )
			)
		);

		WP_Filesystem();
		global $wp_filesystem;

		$this->assertFalse( $wp_filesystem->exists( Uploads::get_upload_path() ) );
	}
}
