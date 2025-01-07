<?php
/**
 * Tests for the Data_Hooks class.
 *
 * @package Checkout Fields and File Upload
 */

namespace CFFU_Plugin;

/**
 * Tests for the Data_Hooks class.
 */
class Data_Hooks_Tests extends Unit_Test_Case {
	public function set_up() {
		parent::set_up();

		Tracked_Files::setup();
	}

	public function test_populate_text() {
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

		unset( WC()->session->cffu_file_uploads );

		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->once() )
			->method( 'add_meta_data' )
			->with(
				$this->equalTo( 'cffu_responses' ),
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
			[ 'cffu_input_foo' => 'Foo' ]
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
		WC()->session->cffu_file_uploads = [ 'bar' => [ $file ] ];

		update_option(
			'cffu_fields',
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
				$this->equalTo( 'cffu_responses' ),
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
			[ 'cffu_input_foo' => 'Foo' ]
		);

		global $wpdb;

		$count = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT count(*) FROM {$wpdb->prefix}cffu_tracked_file_uploads WHERE session_id = %d",
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
		WC()->session->cffu_file_uploads = [ 'bar' => [ $file ] ];

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
				$this->equalTo( 'cffu_responses' ),
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
			[ 'cffu_input_foo' => 'Foo' ]
		);

		global $wpdb;

		$count = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT count(*) FROM {$wpdb->prefix}cffu_tracked_file_uploads WHERE session_id = %d",
				WC()->session->get_customer_id()
			)
		)[0]->{'count(*)'};

		$this->assertEquals( 0, $count );
	}

	public function provide_create_uploads_array() {
		return [
			[ true ],
			[ false ],
		];
	}

	/**
	 * @dataProvider provide_create_uploads_array
	 * @param boolean $create_upload_array Weather to create the upload array.
	 */
	public function test_some_empty( $create_upload_array ) {
		if ( $create_upload_array ) {
			WC()->session->cffu_file_uploads = [ 'bar' => [] ];
		} else {
			unset( WC()->session->cffu_file_uploads );
		}

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
				[
					'id'         => 'bar',
					'type'       => 'file',
					'label'      => 'Foo',
					'required'   => false,
					'products'   => [],
					'categories' => [],
				],
				[
					'id'         => 'fruit',
					'type'       => 'text',
					'label'      => 'Fruit',
					'required'   => true,
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
				$this->equalTo( 'cffu_responses' ),
				$this->equalTo(
					[
						'fruit' => [
							'type' => 'text',
							'data' => 'Mango',
						],
					]
				),
				$this->equalTo( true )
			);

		do_action(
			'woocommerce_checkout_create_order',
			$order,
			[
				'cffu_input_foo'   => '',
				'cffu_input_fruit' => 'Mango',
			]
		);
	}

	/**
	 * @dataProvider provide_create_uploads_array
	 * @param boolean $create_upload_array Weather to create the upload array.
	 */
	public function test_all_empty_responses( $create_upload_array ) {
		if ( $create_upload_array ) {
			WC()->session->cffu_file_uploads = [ 'bar' => [] ];
		} else {
			unset( WC()->session->cffu_file_uploads );
		}

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
			->expects( $this->never() )
			->method( 'add_meta_data' );

		do_action(
			'woocommerce_checkout_create_order',
			$order,
			[ 'cffu_input_foo' => '' ]
		);
	}

	public function test_before_delete_post() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$file1 = [
			'name' => 'example-image.png',
			'path' => $tmp_dir . '/example-image1.tmp.png',
			'url'  => 'https://localhost/whatever/example-image1.tmp.png',
			'type' => 'image/png',
		];

		$file2 = [
			'name' => 'example-image.png',
			'path' => $tmp_dir . '/example-image2.tmp.png',
			'url'  => 'https://localhost/whatever/example-image2.tmp.png',
			'type' => 'image/png',
		];

		$this->assertTrue( copy( __DIR__ . '/example-image.png', $file1['path'] ) );
		$this->assertTrue( copy( __DIR__ . '/example-image.png', $file2['path'] ) );

		$order = $this->create_order_with_responses(
			[
				'bar'        => [
					'type' => 'file',
					'data' => [ $file1, $file2 ],
				],
				'not-a-file' => [
					'type' => 'text',
					'data' => 'Foo',
				],
			]
		);

		do_action(
			'before_delete_post',
			$order->get_id(),
			get_post( $order->get_id() )
		);

		$this->assertFileDoesNotExist( $file1['path'] );
		$this->assertFileDoesNotExist( $file2['path'] );
	}

	public function test_before_delete_hpos() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$file1 = [
			'name' => 'example-image.png',
			'path' => $tmp_dir . '/example-image3.tmp.png',
			'url'  => 'https://localhost/whatever/example-image3.tmp.png',
			'type' => 'image/png',
		];

		$file2 = [
			'name' => 'example-image.png',
			'path' => $tmp_dir . '/example-image4.tmp.png',
			'url'  => 'https://localhost/whatever/example-image4.tmp.png',
			'type' => 'image/png',
		];

		$this->assertTrue( copy( __DIR__ . '/example-image.png', $file1['path'] ) );
		$this->assertTrue( copy( __DIR__ . '/example-image.png', $file2['path'] ) );

		$order = $this->create_order_with_responses(
			[
				'bar'        => [
					'type' => 'file',
					'data' => [ $file1, $file2 ],
				],
				'not-a-file' => [
					'type' => 'text',
					'data' => 'Foo',
				],
			]
		);

		do_action( 'woocommerce_before_delete_order', $order->get_id(), $order );

		$this->assertFileDoesNotExist( $file1['path'] );
		$this->assertFileDoesNotExist( $file2['path'] );
	}
}
