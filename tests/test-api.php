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
	/**
	 * @beforeClass
	 */
	public static function add_database() {
		Tracked_Files::setup();
	}

	/**
	 * @afterClass
	 */
	public static function remove_database() {
		global $wpdb;

		$table_name = Tracked_Files::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
	}

	/**
	 * @after
	 */
	public static function remove_uploads() {
		$dfu_uploads_path = ABSPATH . '/wp-content/uploads/dfu_file_uploads';

		if ( is_dir( $dfu_uploads_path ) ) {
			$it = new \RecursiveDirectoryIterator( $dfu_uploads_path, \FilesystemIterator::SKIP_DOTS );
			$it = new \RecursiveIteratorIterator( $it, \RecursiveIteratorIterator::CHILD_FIRST );

			foreach ( $it as $file ) {
				if ( $file->isDir() ) {
					rmdir( $file->getPathname() );
				} else {
					unlink( $file->getPathname() );
				}
			}

			rmdir( $dfu_uploads_path );
		}
	}

	public function test_no_nonce() {
		$_POST = [
			'name' => 'foo',
		];

		do_action( 'wc_ajax_dfu_file_upload' );

		$this->expectOutputString( 'Failed to verify nonce.' );
	}

	public function test_invalid_nonce() {
		$_POST = [
			'name'  => 'foo',
			'nonce' => 'RANDOM',
		];

		do_action( 'wc_ajax_dfu_file_upload' );

		$this->expectOutputString( 'Failed to verify nonce.' );
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

		do_action( 'wc_ajax_dfu_file_upload' );

		$this->assertTrue( isset( WC()->session->dfu_file_uploads ) );
		$this->assertArrayHasKey( 'foo', WC()->session->dfu_file_uploads );

		$this->assertArrayHasKey( 0, WC()->session->dfu_file_uploads['foo'] );
		$this->assertEquals( WC()->session->dfu_file_uploads['foo'][0]['name'], 'example-image.png' );
		$this->assertFileExists( WC()->session->dfu_file_uploads['foo'][0]['path'] );
		$this->assertNotFalse( filter_var( WC()->session->dfu_file_uploads['foo'][0]['url'], FILTER_VALIDATE_URL ) );
		$this->assertEquals( WC()->session->dfu_file_uploads['foo'][0]['type'], 'image/png' );

		$this->assertFileExists( ABSPATH . '/wp-content/uploads/dfu_file_uploads/index.html' );

		global $wpdb;

		$table = Tracked_Files::table_name();

		$count = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT count(*) FROM $table WHERE session_id = %d AND file_path = %s",
				WC()->session->get_customer_id(),
				wp_unslash( WC()->session->dfu_file_uploads['foo'][0]['path'] )
			)
		)[0]->{'count(*)'};

		$this->assertEquals( 1, $count );
	}

	public function test_preexisting_folder() {
		mkdir( ABSPATH . '/wp-content/uploads/dfu_file_uploads' );
		$this->test_single_file();
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

		do_action( 'wc_ajax_dfu_file_upload' );

		$this->assertTrue( isset( WC()->session->dfu_file_uploads ) );
		$this->assertArrayHasKey( 'foo', WC()->session->dfu_file_uploads );

		$this->assertArrayHasKey( 0, WC()->session->dfu_file_uploads['foo'] );
		$this->assertEquals( WC()->session->dfu_file_uploads['foo'][0]['name'], 'example-image1.png' );
		$this->assertFileExists( WC()->session->dfu_file_uploads['foo'][0]['path'] );
		$this->assertNotFalse( filter_var( WC()->session->dfu_file_uploads['foo'][0]['url'], FILTER_VALIDATE_URL ) );
		$this->assertEquals( WC()->session->dfu_file_uploads['foo'][0]['type'], 'image/png' );

		$this->assertArrayHasKey( 0, WC()->session->dfu_file_uploads['foo'] );
		$this->assertEquals( WC()->session->dfu_file_uploads['foo'][1]['name'], 'example-image2.png' );
		$this->assertFileExists( WC()->session->dfu_file_uploads['foo'][1]['path'] );
		$this->assertNotFalse( filter_var( WC()->session->dfu_file_uploads['foo'][1]['url'], FILTER_VALIDATE_URL ) );
		$this->assertEquals( WC()->session->dfu_file_uploads['foo'][1]['type'], 'image/png' );

		$this->assertFileExists( ABSPATH . '/wp-content/uploads/dfu_file_uploads/index.html' );

		global $wpdb;

		$table = Tracked_Files::table_name();

		$count = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT count(*) FROM $table WHERE session_id = %d",
				WC()->session->get_customer_id()
			)
		)[0]->{'count(*)'};

		$this->assertEquals( 2, $count );
	}
}
