<?php
/**
 * Tests for the Data_Hooks class.
 *
 * @package Details and File Upload
 */

namespace DetailsAndFileUploadPlugin;

/**
 * Tests for the Data_Hooks class.
 */
class Data_Hooks_Tests extends \WP_UnitTestCase {
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

	public function test_populate_text() {
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

		unset( WC()->session->dfu_file_uploads );

		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->once() )
			->method( 'add_meta_data' )
			->with(
				$this->equalTo( 'details_and_file_uploads' ),
				$this->equalTo(
					[
						'foo' => [
							'type' => 'text',
							'data' => 'Foo',
						],
					]
				),
				$this->equalTo( true )
			);

		do_action(
			'woocommerce_checkout_create_order',
			$order,
			[ 'foo' => 'Foo' ]
		);
	}

	public function test_populate_file() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$file = [
			'name' => 'example-image.png',
			'path' => $tmp_dir . '/example-image.tmp.png',
			'url'  => 'https://localhost/whatever/example-image.tmp.png',
			'type' => 'image/png',
		];

		$this->assertTrue( copy( __DIR__ . '/example-image.png', $file['path'] ) );
		Tracked_Files::track_file( WC()->session->get_customer_id(), $file['path'] );
		WC()->session->dfu_file_uploads = [ 'bar' => [ $file ] ];

		update_option(
			'details_and_file_uploads_fields',
			[
				[
					'id'         => 'bar',
					'type'       => 'file',
					'label'      => 'Foo',
					'required'   => false,
					'products'   => [],
					'categories' => [],
				],
			]
		);

		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->once() )
			->method( 'add_meta_data' )
			->with(
				$this->equalTo( 'details_and_file_uploads' ),
				$this->equalTo(
					[
						'bar' => [
							'type' => 'file',
							'data' => [ $file ],
						],
					]
				),
				$this->equalTo( true )
			);

		do_action(
			'woocommerce_checkout_create_order',
			$order,
			[ 'foo' => 'Foo' ]
		);
	}

	public function test_populate_mixed_types() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$file = [
			'name' => 'example-image.png',
			'path' => $tmp_dir . '/example-image.tmp.png',
			'url'  => 'https://localhost/whatever/example-image.tmp.png',
			'type' => 'image/png',
		];

		$this->assertTrue( copy( __DIR__ . '/example-image.png', $file['path'] ) );
		Tracked_Files::track_file( WC()->session->get_customer_id(), $file['path'] );
		WC()->session->dfu_file_uploads = [ 'bar' => [ $file ] ];

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
				[
					'id'         => 'bar',
					'type'       => 'file',
					'label'      => 'Foo',
					'required'   => false,
					'products'   => [],
					'categories' => [],
				],
			]
		);

		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->once() )
			->method( 'add_meta_data' )
			->with(
				$this->equalTo( 'details_and_file_uploads' ),
				$this->equalTo(
					[
						'foo' => [
							'type' => 'text',
							'data' => 'Foo',
						],
						'bar' => [
							'type' => 'file',
							'data' => [ $file ],
						],
					]
				),
				$this->equalTo( true )
			);

		do_action(
			'woocommerce_checkout_create_order',
			$order,
			[ 'foo' => 'Foo' ]
		);

		global $wpdb;

		$table = Tracked_Files::table_name();

		$count = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT count(*) FROM $table WHERE session_id = %d",
				WC()->session->get_customer_id()
			)
		)[0]->{'count(*)'};

		$this->assertEquals( 0, $count );
	}

	public function test_cleanup_sessions() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp.png'
			)
		);

		Tracked_Files::track_file( 'FAKE_KEY', $tmp_dir . '/example-image.tmp.png' );

		do_action( 'woocommerce_cleanup_sessions' );

		$this->assertFalse( file_exists( $tmp_dir . '/example-image.tmp.png' ) );

		global $wpdb;

		$table = Tracked_Files::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_results( "SELECT count(*) FROM $table" )[0]->{'count(*)'};

		$this->assertEquals( 0, $count );
	}
}
