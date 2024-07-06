<?php
/**
 * Tests for the Display class.
 *
 * @package Checkout Fields and File Upload
 */

namespace CFFU_Plugin;

/**
 * Tests for the Display class.
 */
class Display_Tests extends \WP_UnitTestCase {
	public function test_text_field() {
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

		$out = apply_filters(
			'woocommerce_checkout_fields',
			[
				'order' => [
					'order_comments' => [],
				],
			]
		);

		$this->assertEquals(
			[
				'order' => [
					'foo'            => [
						'type'        => 'text',
						'label'       => 'Foo',
						'input_class' => [],
						'required'    => true,
						'clear'       => false,
						'label_class' => '',
						'options'     => [],
						'placeholder' => null,
						'multiple'    => false,
					],
					'order_comments' => [],
				],
			],
			$out
		);
	}

	public function test_file_upload_field() {
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

		$out = apply_filters(
			'woocommerce_checkout_fields',
			[
				'order' => [
					'order_comments' => [],
				],
			]
		);

		$this->assertEquals(
			[
				'order' => [
					'foo'            => [
						'type'        => 'cffu_file_upload',
						'label'       => 'Foo',
						'input_class' => [],
						'required'    => false,
						'clear'       => false,
						'label_class' => '',
						'options'     => [],
						'placeholder' => null,
						'multiple'    => false,
					],
					'order_comments' => [],
				],
			],
			$out
		);
	}

	public function test_show_notes() {
		$out = apply_filters(
			'woocommerce_checkout_fields',
			[
				'order' => [
					'order_comments' => [],
				],
			]
		);

		$this->assertEquals(
			[
				'order' => [
					'order_comments' => [],
				],
			],
			$out
		);
	}

	public function test_hide_notes() {
		update_option( 'cffu_hide_notes', true );

		$out = apply_filters(
			'woocommerce_checkout_fields',
			[
				'order' => [
					'order_comments' => [],
				],
			]
		);

		$this->assertEquals(
			[
				'order' => [],
			],
			$out
		);
	}

	public function test_create_upload_field() {
		$out = apply_filters(
			'woocommerce_form_field_cffu_file_upload',
			'',
			'foo',
			[
				'type'        => 'cffu_file_upload',
				'label'       => 'Foo',
				'input_class' => [],
				'required'    => false,
				'clear'       => false,
				'label_class' => '',
				'options'     => [],
				'placeholder' => null,
				'multiple'    => false,
			]
		);

		$this->assertEquals(
			'<p class="form-row"><label for="foo">Foo&nbsp;<span class="optional">(optional)</span> <button class="cffu-clear-file">Clear</button></label><span class="woocommerce-input-wrapper"><input type="file" id="foo" class="input-text input-cffu-file-upload" data-name="foo" ></span></p>',
			$out
		);
	}

	public function test_required_upload_field() {
		$out = apply_filters(
			'woocommerce_form_field_cffu_file_upload',
			'',
			'foo',
			[
				'type'        => 'cffu_file_upload',
				'label'       => 'Foo',
				'input_class' => [],
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => [],
				'placeholder' => null,
				'multiple'    => false,
			]
		);

		$this->assertEquals(
			'<p class="form-row"><label for="foo">Foo&nbsp;<abbr class="required" title="required">*</abbr></label><span class="woocommerce-input-wrapper"><input type="file" id="foo" class="input-text input-cffu-file-upload" data-name="foo" ></span></p>',
			$out
		);
	}

	public function test_multiple_files() {
		$out = apply_filters(
			'woocommerce_form_field_cffu_file_upload',
			'',
			'foo',
			[
				'type'        => 'cffu_file_upload',
				'label'       => 'Foo',
				'input_class' => [],
				'required'    => false,
				'clear'       => false,
				'label_class' => '',
				'options'     => [],
				'placeholder' => null,
				'multiple'    => true,
			]
		);

		$this->assertEquals(
			'<p class="form-row"><label for="foo">Foo&nbsp;<span class="optional">(optional)</span> <button class="cffu-clear-file">Clear</button></label><span class="woocommerce-input-wrapper"><input type="file" id="foo" class="input-text input-cffu-file-upload" data-name="foo" multiple ></span></p>',
			$out
		);
	}

	public function test_order_no_fields() {
		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->any() )
			->method( 'get_meta' )
			->with( $this->equalTo( 'cffu_responses' ) )
			->willReturn( [] );

		$this->assertEquals( false, Display::order_has_fields( $order ) );
	}

	public function test_order_has_fields() {
		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->any() )
			->method( 'get_meta' )
			->with( $this->equalTo( 'cffu_responses' ) )
			->willReturn( [ 'foo' => [ 'data' => 'here' ] ] );

		$this->assertEquals( true, Display::order_has_fields( $order ) );
	}

	public function test_show_known_field() {
		$order = $this->createMock( \WC_Order::class );

		update_option(
			'cffu_fields',
			[
				[
					'id'         => 'foo',
					'type'       => 'text',
					'label'      => 'Foo',
					'required'   => false,
					'products'   => [],
					'categories' => [],
				],
			]
		);

		$order
			->expects( $this->any() )
			->method( 'get_meta' )
			->with( $this->equalTo( 'cffu_responses' ) )
			->willReturn(
				[
					'foo' => [
						'type' => 'text',
						'data' => 'Foo',
					],
				]
			);

		Display::show_fields_for_order( $order );

		$this->expectOutputString(
			'<div class="cffu-order-details"><div class="cffu-order-detail"><div><span>Foo:</span> <span>Foo</span></div></div></div>'
		);
	}

	public function test_show_unknown_field() {
		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->any() )
			->method( 'get_meta' )
			->with( $this->equalTo( 'cffu_responses' ) )
			->willReturn(
				[
					'bar' => [
						'type' => 'text',
						'data' => 'Foo',
					],
				]
			);

		Display::show_fields_for_order( $order );

		$this->expectOutputString(
			'<div class="cffu-order-details"><div class="cffu-order-detail"><div><span>{bar}:</span> <span>Foo</span></div></div></div>'
		);
	}

	public function text_show_multiple_fields() {
		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->any() )
			->method( 'get_meta' )
			->with( $this->equalTo( 'cffu_responses' ) )
			->willReturn(
				[
					'bar' => [
						'type' => 'text',
						'data' => 'Foo',
					],
					'baz' => [
						'type' => 'text',
						'data' => 'Foo',
					],
				]
			);

		Display::show_fields_for_order( $order );

		$this->expectOutputString(
			'<div class="cffu-order-details"><div class="cffu-order-detail"><div><span>{bar}:</span> <span>Foo</span></div></div><div class="cffu-order-detail"><div><span>{baz}:</span> <span>Foo</span></div></div></div>'
		);
	}

	public function test_show_file_field() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$file = [
			'name' => 'example-image.png',
			'path' => $tmp_dir . '/example-image.tmp.png',
			'url'  => 'https://localhost/whatever/example-image.tmp.png',
			'type' => 'image/png',
		];

		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->any() )
			->method( 'get_meta' )
			->with( $this->equalTo( 'cffu_responses' ) )
			->willReturn(
				[
					'bar' => [
						'type' => 'file',
						'data' => [ $file ],
					],
				]
			);

		Display::show_fields_for_order( $order );

		$this->expectOutputString(
			'<div class="cffu-order-details"><div class="cffu-order-detail"><div>{bar}:</div><div class="cffu-file-field"><figure><a target="_blank" href="https://localhost/whatever/example-image.tmp.png"><object data="https://localhost/whatever/example-image.tmp.png"></object></a><figcaption><a target="_blank" href="https://localhost/whatever/example-image.tmp.png">example-image.png</a></figcaption></figure></div></div></div>'
		);
	}

	public function test_show_multiple_fields() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$file = [
			'name' => 'example-image.png',
			'path' => $tmp_dir . '/example-image.tmp.png',
			'url'  => 'https://localhost/whatever/example-image.tmp.png',
			'type' => 'image/png',
		];

		$file2 = [
			'name' => 'example-image2.png',
			'path' => $tmp_dir . '/example-image2.tmp.png',
			'url'  => 'https://localhost/whatever/example-image2.tmp.png',
			'type' => 'image/png',
		];

		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->any() )
			->method( 'get_meta' )
			->with( $this->equalTo( 'cffu_responses' ) )
			->willReturn(
				[
					'bar' => [
						'type' => 'file',
						'data' => [ $file, $file2 ],
					],
				]
			);

		Display::show_fields_for_order( $order );

		$this->expectOutputString(
			'<div class="cffu-order-details"><div class="cffu-order-detail"><div>{bar}:</div><div class="cffu-file-field"><figure><a target="_blank" href="https://localhost/whatever/example-image.tmp.png"><object data="https://localhost/whatever/example-image.tmp.png"></object></a><figcaption><a target="_blank" href="https://localhost/whatever/example-image.tmp.png">example-image.png</a></figcaption></figure><figure><a target="_blank" href="https://localhost/whatever/example-image2.tmp.png"><object data="https://localhost/whatever/example-image2.tmp.png"></object></a><figcaption><a target="_blank" href="https://localhost/whatever/example-image2.tmp.png">example-image2.png</a></figcaption></figure></div></div></div>'
		);
	}

	public function test_shown_file_for_email() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$file = [
			'name' => 'example-image.png',
			'path' => $tmp_dir . '/example-image.tmp.png',
			'url'  => 'https://localhost/whatever/example-image.tmp.png',
			'type' => 'image/png',
		];

		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->any() )
			->method( 'get_meta' )
			->with( $this->equalTo( 'cffu_responses' ) )
			->willReturn(
				[
					'bar' => [
						'type' => 'file',
						'data' => [ $file ],
					],
				]
			);

		Display::show_fields_for_order( $order, true );

		$this->expectOutputString(
			'<div style="margin-bottom:40px;padding:12px;color:#636363;border:1px solid #e5e5e5;"><div class="cffu-order-detail"><div><span>{bar}:</span> <a target="_blank" href="https://localhost/whatever/example-image.tmp.png">example-image.png</a></div></div></div>'
		);
	}

	public function test_show_no_fields() {
		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->any() )
			->method( 'get_meta' )
			->with( $this->equalTo( 'cffu_responses' ) )
			->willReturn( [] );

		Display::show_fields_for_order( $order );

		$this->expectOutputString(
			'<div class="cffu-order-details"><div class="cffu-order-detail">No details found.</div></div>'
		);
	}

	public function test_show_order() {
		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->any() )
			->method( 'get_meta' )
			->with( $this->equalTo( 'cffu_responses' ) )
			->willReturn(
				[
					'bar' => [
						'type' => 'text',
						'data' => 'Foo',
					],
				]
			);

		do_action( 'woocommerce_after_order_details', $order );

		$this->expectOutputString(
			'<section class="cffu-responses"><h2 class="cffu-table-title woocommerce-column__title">Fields and files</h2><div class="cffu-order-details"><div class="cffu-order-detail"><div><span>{bar}:</span> <span>Foo</span></div></div></div></section>'
		);
	}

	public function test_email_table() {
		$order = $this->createMock( \WC_Order::class );

		$order
			->expects( $this->any() )
			->method( 'get_meta' )
			->with( $this->equalTo( 'cffu_responses' ) )
			->willReturn(
				[
					'bar' => [
						'type' => 'text',
						'data' => 'Foo',
					],
				]
			);

		do_action( 'woocommerce_email_after_order_table', $order );

		$this->expectOutputString(
			'<h2>Fields and files</h2><div style="margin-bottom:40px;padding:12px;color:#636363;border:1px solid #e5e5e5;"><div class="cffu-order-detail"><div><span>{bar}:</span> <span>Foo</span></div></div></div>'
		);
	}
}
