<?php
/**
 * Tests for the Data_Hooks class.
 *
 * @package Details and File Upload
 */

namespace DetailsAndFileUploadPlugin;

/**
 * Tests for uninstall.php
 */
class Uninstall_Tests extends \WP_UnitTestCase {
	public function test_uninstall_script() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp.png'
			)
		);

		update_option(
			'details_and_file_uploads_fields',
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

		update_option( 'details_and_file_uploads_hide_notes', false );

		Tracked_Files::setup();

		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();
		$file    = [
			'name'     => 'example-image.png',
			'type'     => 'image/png',
			'tmp_name' => $tmp_dir . '/example-image.tmp.png',
			'error'    => UPLOAD_ERR_OK,
			'size'     => filesize( $tmp_dir . '/example-image.tmp.png' ),
		];

		Uploads::add_file( $file );

		global $wpdb;

		define( 'WP_UNINSTALL_PLUGIN', true );
		require 'uninstall.php';

		$this->assertNull( get_option( 'details_and_file_uploads_fields', null ) );
		$this->assertNull( get_option( 'details_and_file_uploads_hide_notes', null ) );

		$this->assertEmpty(
			$wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( Tracked_Files::table_name() ) )
			)
		);

		WP_Filesystem();
		global $wp_filesystem;

		$this->assertFalse( $wp_filesystem->exists( Uploads::get_upload_path() ) );
	}
}
