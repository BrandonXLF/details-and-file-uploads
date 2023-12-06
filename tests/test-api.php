<?php
/**
 * Tests for the API class.
 *
 * @package Details and File Upload
 */

namespace DetailsAndFileUploadPlugin;

/**
 * Tests for the API class.
 */
class API_Tests extends \WP_UnitTestCase {
	public function set_up(): void {
		parent::set_up();

		update_option(
			'details_and_file_uploads_fields',
			[
				[
					'id'         => 'foo',
					'type'       => 'file',
					'label'      => 'Foo',
					'required'   => false,
					'products'   => [],
					'categories' => [],
				],
			]
		);

		Tracked_Files::setup();
	}

	public function tear_down(): void {
		Uploads::uninstall();

		unset( WC()->session->dfu_file_uploads );

		parent::tear_down();
	}

	public function assert_tracked_file( $key, $name, $data, $index = 0 ) {
		$this->assertArrayHasKey( $key, $data );
		$this->assertArrayHasKey( 0, $data[ $key ] );
		$this->assertEquals( $name, $data[ $key ][ $index ]['name'] );
		$this->assertFileExists( $data[ $key ][ $index ]['path'] );
		$this->assertNotFalse( filter_var( $data[ $key ][ $index ]['url'], FILTER_VALIDATE_URL ) );

		global $wpdb;

		$table = Tracked_Files::table_name();

		$count = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT count(*) FROM $table WHERE session_id = %d AND file_path = %s",
				WC()->session->get_customer_id(),
				wp_unslash( $data[ $key ][ $index ]['path'] )
			)
		)[0]->{'count(*)'};

		$this->assertEquals( 1, $count );
	}

	public function assert_file_count( $key, $expected, $data ) {
		if ( $expected > 0 ) {
			$this->assertArrayHasKey( $key, $data );
			$this->assertCount( $expected, $data[ $key ] );
		} elseif ( isset( $data ) && array_key_exists( $key, $data ) ) {
			$this->assertCount( $expected, $data[ $key ] );
		}

		global $wpdb;

		$table = Tracked_Files::table_name();

		$count = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT count(*) FROM $table WHERE session_id = %d",
				WC()->session->get_customer_id()
			)
		)[0]->{'count(*)'};

		$this->assertEquals( $expected, $count );
	}

	public function test_no_nonce() {
		$_POST = [
			'name' => 'foo',
		];

		do_action( 'wp_ajax_dfu_file_upload' );

		$this->expectOutputString( 'Failed to verify nonce.' );
		$this->assertFalse( isset( WC()->session->dfu_file_uploads ) );
	}

	public function test_invalid_nonce() {
		$_POST = [
			'name'  => 'foo',
			'nonce' => 'RANDOM',
		];

		do_action( 'wp_ajax_dfu_file_upload' );

		$this->expectOutputString( 'Failed to verify nonce.' );
		$this->assertFalse( isset( WC()->session->dfu_file_uploads ) );
	}

	public function test_single_file() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp.png'
			)
		);

		$_POST = [
			'name'  => 'foo',
			'nonce' => wp_create_nonce( 'dfu-file-upload' ),
		];

		$_FILES = [
			'files' => [
				'name'     => [ 'example-image.png' ],
				'type'     => [ 'image/png' ],
				'tmp_name' => [ $tmp_dir . '/example-image.tmp.png' ],
				'error'    => [ UPLOAD_ERR_OK ],
				'size'     => [ filesize( $tmp_dir . '/example-image.tmp.png' ) ],
			],
		];

		do_action( 'wp_ajax_dfu_file_upload' );

		$this->assertTrue( isset( WC()->session->dfu_file_uploads ) );
		$this->assertFileExists( ABSPATH . '/wp-content/uploads/dfu_file_uploads/index.html' );
		$this->assert_file_count( 'foo', 1, WC()->session->dfu_file_uploads );
		$this->assert_tracked_file( 'foo', 'example-image.png', WC()->session->dfu_file_uploads );
	}

	public function test_preexisting_folder() {
		mkdir( ABSPATH . '/wp-content/uploads/dfu_file_uploads' );

		$this->test_single_file();
	}

	public function test_double_submit() {
		$this->test_single_file();
		$this->test_single_file();
	}

	public function test_overwride_submit() {
		$this->test_single_file();

		$_POST = [
			'name'  => 'foo',
			'nonce' => wp_create_nonce( 'dfu-file-upload' ),
		];

		$_FILES = [];

		do_action( 'wp_ajax_dfu_file_upload' );

		$this->assert_file_count( 'foo', 0, WC()->session->dfu_file_uploads );
	}

	public function test_multiple_files() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image1.tmp.png'
			)
		);

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image2.tmp.png'
			)
		);

		$_POST = [
			'name'  => 'foo',
			'nonce' => wp_create_nonce( 'dfu-file-upload' ),
		];

		$_FILES = [
			'files' => [
				'name'     => [ 'example-image1.png', 'example-image2.png' ],
				'type'     => [ 'image/png', 'image/png' ],
				'tmp_name' => [
					$tmp_dir . '/example-image1.tmp.png',
					$tmp_dir . '/example-image2.tmp.png',
				],
				'error'    => [ UPLOAD_ERR_OK, UPLOAD_ERR_OK ],
				'size'     => [
					filesize( $tmp_dir . '/example-image1.tmp.png' ),
					filesize( $tmp_dir . '/example-image2.tmp.png' ),
				],
			],
		];

		do_action( 'wp_ajax_dfu_file_upload' );

		$this->assertTrue( isset( WC()->session->dfu_file_uploads ) );
		$this->assertFileExists( ABSPATH . '/wp-content/uploads/dfu_file_uploads/index.html' );
		$this->assert_file_count( 'foo', 2, WC()->session->dfu_file_uploads );
		$this->assert_tracked_file( 'foo', 'example-image1.png', WC()->session->dfu_file_uploads );
		$this->assert_tracked_file( 'foo', 'example-image2.png', WC()->session->dfu_file_uploads, 1 );
	}

	public function test_allowed_file_ext() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp.png'
			)
		);

		$_POST = [
			'name'  => 'foo',
			'nonce' => wp_create_nonce( 'dfu-file-upload' ),
		];

		$_FILES = [
			'files' => [
				'name'     => [ 'example-image.png' ],
				'type'     => [ 'image/png' ],
				'tmp_name' => [ $tmp_dir . '/example-image.tmp.png' ],
				'error'    => [ UPLOAD_ERR_OK ],
				'size'     => [ filesize( $tmp_dir . '/example-image.tmp.png' ) ],
			],
		];

		update_option(
			'details_and_file_uploads_fields',
			[
				[
					'id'         => 'foo',
					'type'       => 'file',
					'label'      => 'Foo',
					'required'   => false,
					'types'      => [ '.png' ],
					'products'   => [],
					'categories' => [],
				],
			]
		);

		do_action( 'wp_ajax_dfu_file_upload' );

		$this->assertTrue( isset( WC()->session->dfu_file_uploads ) );
		$this->assertFileExists( ABSPATH . '/wp-content/uploads/dfu_file_uploads/index.html' );
		$this->assert_file_count( 'foo', 1, WC()->session->dfu_file_uploads );
		$this->assert_tracked_file( 'foo', 'example-image.png', WC()->session->dfu_file_uploads );
	}

	public function test_allowed_type() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp.png'
			)
		);

		$_POST = [
			'name'  => 'foo',
			'nonce' => wp_create_nonce( 'dfu-file-upload' ),
		];

		$_FILES = [
			'files' => [
				'name'     => [ 'example-image.png' ],
				'type'     => [ 'image/png' ],
				'tmp_name' => [ $tmp_dir . '/example-image.tmp.png' ],
				'error'    => [ UPLOAD_ERR_OK ],
				'size'     => [ filesize( $tmp_dir . '/example-image.tmp.png' ) ],
			],
		];

		update_option(
			'details_and_file_uploads_fields',
			[
				[
					'id'         => 'foo',
					'type'       => 'file',
					'label'      => 'Foo',
					'required'   => false,
					'types'      => [ 'image' ],
					'products'   => [],
					'categories' => [],
				],
			]
		);

		do_action( 'wp_ajax_dfu_file_upload' );

		$this->assertTrue( isset( WC()->session->dfu_file_uploads ) );
		$this->assertFileExists( ABSPATH . '/wp-content/uploads/dfu_file_uploads/index.html' );
		$this->assert_file_count( 'foo', 1, WC()->session->dfu_file_uploads );
		$this->assert_tracked_file( 'foo', 'example-image.png', WC()->session->dfu_file_uploads );
	}

	public function test_not_allowed_type() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp.png'
			)
		);

		$_POST = [
			'name'  => 'foo',
			'nonce' => wp_create_nonce( 'dfu-file-upload' ),
		];

		$_FILES = [
			'files' => [
				'name'     => [ 'example-image.png' ],
				'type'     => [ 'image/png' ],
				'tmp_name' => [ $tmp_dir . '/example-image.tmp.png' ],
				'error'    => [ UPLOAD_ERR_OK ],
				'size'     => [ filesize( $tmp_dir . '/example-image.tmp.png' ) ],
			],
		];

		update_option(
			'details_and_file_uploads_fields',
			[
				[
					'id'         => 'foo',
					'type'       => 'file',
					'label'      => 'Foo',
					'required'   => false,
					'types'      => [ '.pdf' ],
					'products'   => [],
					'categories' => [],
				],
			]
		);

		do_action( 'wp_ajax_dfu_file_upload' );

		$this->expectOutputString( 'File type/extension is not allowed.' );
		$this->assertFalse( isset( WC()->session->dfu_file_uploads ) );
		$this->assert_file_count( 'foo', 0, WC()->session->dfu_file_uploads );
	}
}
