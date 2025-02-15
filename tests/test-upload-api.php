<?php
/**
 * Tests for the API class.
 *
 * @package Checkout Fields and File Upload
 */

namespace CFFU_Plugin;

/**
 * Tests for the API class.
 *
 * @runTestsInSeparateProcesses - Avoid "Cannot set response code - headers already sent"
 * @preserveGlobalState disabled
 */
class Upload_API_Tests extends Unit_Test_Case {
	public function set_up(): void {
		parent::set_up();

		update_option(
			'cffu_fields',
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

		unset( WC()->session->cffu_file_uploads );

		parent::tear_down();
	}

	public function assert_tracked_file( $key, $name, $data, $index = 0 ) {
		$this->assertArrayHasKey( $key, $data );
		$this->assertArrayHasKey( 0, $data[ $key ] );
		$this->assertEquals( $name, $data[ $key ][ $index ]['name'] );
		$this->assertFileExists( $data[ $key ][ $index ]['path'] );
		$this->assertNotFalse( filter_var( $data[ $key ][ $index ]['url'], FILTER_VALIDATE_URL ) );

		global $wpdb;

		$count = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT count(*) FROM {$wpdb->prefix}cffu_tracked_file_uploads WHERE session_id = %d AND file_path = %s",
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

		$count = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT count(*) FROM {$wpdb->prefix}cffu_tracked_file_uploads WHERE session_id = %d",
				WC()->session->get_customer_id()
			)
		)[0]->{'count(*)'};

		$this->assertEquals( $expected, $count );
	}

	public function action_provider() {
		return [
			[ 'wp_ajax_cffu_file_upload' ],
			[ 'wp_ajax_nopriv_cffu_file_upload' ],
		];
	}

	/**
	 * @dataProvider action_provider
	 * @param string $action The action to call.
	 */
	public function test_no_nonce( $action ) {
		$_POST = [
			'name' => 'cffu_input_foo',
		];

		do_action( $action );

		$this->expectOutputString( 'Failed to verify nonce.' );
		$this->assertFalse( isset( WC()->session->cffu_file_uploads ) );
	}

	/**
	 * @dataProvider action_provider
	 * @param string $action The action to call.
	 */
	public function test_invalid_nonce( $action ) {
		$_POST = [
			'name'  => 'cffu_input_foo',
			'nonce' => 'RANDOM',
		];

		do_action( $action );

		$this->expectOutputString( 'Failed to verify nonce.' );
		$this->assertFalse( isset( WC()->session->cffu_file_uploads ) );
	}

	/**
	 * @dataProvider action_provider
	 * @param string $action The action to call.
	 */
	public function test_single_file( $action ) {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp.png'
			)
		);

		$_POST = [
			'name'  => 'cffu_input_foo',
			'nonce' => wp_create_nonce( 'cffu-file-upload' ),
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

		do_action( $action );

		$this->assertTrue( isset( WC()->session->cffu_file_uploads ) );
		$this->assertFileExists( ABSPATH . '/wp-content/uploads/cffu_file_uploads/index.html' );
		$this->assert_file_count( 'foo', 1, WC()->session->cffu_file_uploads );
		$this->assert_tracked_file( 'foo', 'example-image.png', WC()->session->cffu_file_uploads );
	}

	/**
	 * @dataProvider action_provider
	 * @param string $action The action to call.
	 */
	public function test_no_files( $action ) {
		$this->test_single_file( $action );

		$_POST = [
			'name'  => 'cffu_input_foo',
			'nonce' => wp_create_nonce( 'cffu-file-upload' ),
		];

		$_FILES = [];

		do_action( $action );

		$this->assert_file_count( 'foo', 0, WC()->session->cffu_file_uploads );
	}

	/**
	 * @dataProvider action_provider
	 * @param string $action The action to call.
	 */
	public function test_preexisting_folder( $action ) {
		WP_Filesystem();
		global $wp_filesystem;

		$wp_filesystem->mkdir( ABSPATH . '/wp-content/uploads/cffu_file_uploads' );

		$this->test_single_file( $action );
	}

	/**
	 * @dataProvider action_provider
	 * @param string $action The action to call.
	 */
	public function test_double_submit( $action ) {
		$this->test_single_file( $action );
		$this->test_single_file( $action );
	}

	/**
	 * @dataProvider action_provider
	 * @param string $action The action to call.
	 */
	public function test_overwrite_submit( $action ) {
		$this->test_single_file( $action );
		$this->test_no_files( $action );
	}

	/**
	 * @dataProvider action_provider
	 * @param string $action The action to call.
	 */
	public function test_multiple_files( $action ) {
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
			'name'  => 'cffu_input_foo',
			'nonce' => wp_create_nonce( 'cffu-file-upload' ),
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

		do_action( $action );

		$this->assertTrue( isset( WC()->session->cffu_file_uploads ) );
		$this->assertFileExists( ABSPATH . '/wp-content/uploads/cffu_file_uploads/index.html' );
		$this->assert_file_count( 'foo', 2, WC()->session->cffu_file_uploads );
		$this->assert_tracked_file( 'foo', 'example-image1.png', WC()->session->cffu_file_uploads );
		$this->assert_tracked_file( 'foo', 'example-image2.png', WC()->session->cffu_file_uploads, 1 );
	}

	/**
	 * @dataProvider action_provider
	 * @param string $action The action to call.
	 */
	public function test_allowed_file_ext( $action ) {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp.png'
			)
		);

		$_POST = [
			'name'  => 'cffu_input_foo',
			'nonce' => wp_create_nonce( 'cffu-file-upload' ),
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
			'cffu_fields',
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

		do_action( $action );

		$this->assertTrue( isset( WC()->session->cffu_file_uploads ) );
		$this->assertFileExists( ABSPATH . '/wp-content/uploads/cffu_file_uploads/index.html' );
		$this->assert_file_count( 'foo', 1, WC()->session->cffu_file_uploads );
		$this->assert_tracked_file( 'foo', 'example-image.png', WC()->session->cffu_file_uploads );
	}

	/**
	 * @dataProvider action_provider
	 * @param string $action The action to call.
	 */
	public function test_allowed_type( $action ) {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp.png'
			)
		);

		$_POST = [
			'name'  => 'cffu_input_foo',
			'nonce' => wp_create_nonce( 'cffu-file-upload' ),
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
			'cffu_fields',
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

		do_action( $action );

		$this->assertTrue( isset( WC()->session->cffu_file_uploads ) );
		$this->assertFileExists( ABSPATH . '/wp-content/uploads/cffu_file_uploads/index.html' );
		$this->assert_file_count( 'foo', 1, WC()->session->cffu_file_uploads );
		$this->assert_tracked_file( 'foo', 'example-image.png', WC()->session->cffu_file_uploads );
	}

	/**
	 * @dataProvider action_provider
	 * @param string $action The action to call.
	 */
	public function test_not_allowed_type( $action ) {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp.png'
			)
		);

		$_POST = [
			'name'  => 'cffu_input_foo',
			'nonce' => wp_create_nonce( 'cffu-file-upload' ),
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
			'cffu_fields',
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

		do_action( $action );

		$this->expectOutputString( 'Sorry, you are not allowed to upload this file type.' );
		$this->assertFalse( isset( WC()->session->cffu_file_uploads ) );
		$this->assert_file_count( 'foo', 0, WC()->session->cffu_file_uploads );
	}

	/**
	 * @dataProvider action_provider
	 * @param string $action The action to call.
	 */
	public function test_wp_unknown_type( $action ) {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp.zzz'
			)
		);

		$_POST = [
			'name'  => 'cffu_input_foo',
			'nonce' => wp_create_nonce( 'cffu-file-upload' ),
		];

		$_FILES = [
			'files' => [
				'name'     => [ 'example-image.zzz' ],
				'type'     => [ 'application/octet-stream' ],
				'tmp_name' => [ $tmp_dir . '/example-image.tmp.zzz' ],
				'error'    => [ UPLOAD_ERR_OK ],
				'size'     => [ filesize( $tmp_dir . '/example-image.tmp.zzz' ) ],
			],
		];

		update_option(
			'cffu_fields',
			[
				[
					'id'         => 'foo',
					'type'       => 'file',
					'label'      => 'Foo',
					'required'   => false,
					'types'      => [ '.zzz' ],
					'products'   => [],
					'categories' => [],
				],
			]
		);

		do_action( $action );

		$this->assertTrue( isset( WC()->session->cffu_file_uploads ) );
		$this->assertFileExists( ABSPATH . '/wp-content/uploads/cffu_file_uploads/index.html' );
		$this->assert_file_count( 'foo', 1, WC()->session->cffu_file_uploads );
		$this->assert_tracked_file( 'foo', 'example-image.zzz', WC()->session->cffu_file_uploads );
	}
}
