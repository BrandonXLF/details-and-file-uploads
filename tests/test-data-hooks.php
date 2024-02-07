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
	public function set_up() {
		parent::set_up();

		Tracked_Files::setup();
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

		global $wpdb;

		$count = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT count(*) FROM {$wpdb->prefix}dfu_tracked_file_uploads WHERE session_id = %d",
				WC()->session->get_customer_id()
			)
		)[0]->{'count(*)'};

		$this->assertEquals( 0, $count );
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

		$count = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT count(*) FROM {$wpdb->prefix}dfu_tracked_file_uploads WHERE session_id = %d",
				WC()->session->get_customer_id()
			)
		)[0]->{'count(*)'};

		$this->assertEquals( 0, $count );
	}
}
