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
class Display_Tests extends Unit_Test_Case {
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
					'cffu_input_foo' => [
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
					'cffu_input_foo' => [
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
		$order = $this->create_order_with_responses();
		$this->assertEquals( false, Display::order_has_fields( $order ) );
	}

	public function test_order_no_meta_data() {
		$order = wc_create_order();
		$this->assertEquals( false, Display::order_has_fields( $order ) );
	}

	public function test_order_has_fields() {
		$order = $this->create_order_with_responses(
			[ 'foo' => [ 'data' => 'here' ] ]
		);

		$this->assertEquals( true, Display::order_has_fields( $order ) );
	}

	public function test_show_known_field() {
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

		$order = $this->create_order_with_responses(
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
		$order = $this->create_order_with_responses(
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
		$order = $this->create_order_with_responses(
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

		$order = $this->create_order_with_responses(
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

		$order = $this->create_order_with_responses(
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

		$order = $this->create_order_with_responses(
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
		$order = $this->create_order_with_responses();

		Display::show_fields_for_order( $order );

		$this->expectOutputString(
			'<div class="cffu-order-details"><div class="cffu-order-detail">No details found.</div></div>'
		);
	}

	public function tests_show_no_meta_data() {
		$order = wc_create_order();

		Display::show_fields_for_order( $order );

		$this->expectOutputString(
			'<div class="cffu-order-details"><div class="cffu-order-detail">No details found.</div></div>'
		);
	}

	public function test_show_order() {
		$order = $this->create_order_with_responses(
			[
				'bar' => [
					'type' => 'text',
					'data' => 'Foo',
				],
			]
		);

		do_action( 'woocommerce_after_order_details', $order );

		$this->expectOutputString(
			'<section class="cffu-responses"><h2 class="cffu-table-title woocommerce-column__title">Additional details</h2><div class="cffu-order-details"><div class="cffu-order-detail"><div><span>{bar}:</span> <span>Foo</span></div></div></div></section>'
		);
	}

	public function test_email_table() {
		$order = $this->create_order_with_responses(
			[
				'bar' => [
					'type' => 'text',
					'data' => 'Foo',
				],
			]
		);

		do_action( 'woocommerce_email_after_order_table', $order );

		$this->expectOutputString(
			'<h2>Additional details</h2><div style="margin-bottom:40px;padding:12px;color:#636363;border:1px solid #e5e5e5;"><div class="cffu-order-detail"><div><span>{bar}:</span> <span>Foo</span></div></div></div>'
		);
	}

	public function test_meta_box() {
		global $wp_meta_boxes;

		do_action( 'add_meta_boxes' );

		$page = convert_to_screen( wc_get_page_screen_id( 'shop_order' ) )->id;

		$this->assertEquals(
			[
				'id'       => 'cffu_order_meta_box',
				'title'    => 'Fields and files',
				'callback' => [ Display::class, 'edit_order_meta_box' ],
				'args'     => null,
			],
			$wp_meta_boxes[ $page ]['side']['default']['cffu_order_meta_box']
		);
	}
	public function test_meta_box_render_post() {
		$order = $this->create_order_with_responses(
			[
				'bar' => [
					'type' => 'text',
					'data' => 'Foo',
				],
			]
		);

		$post = get_post( $order->get_id() );

		Display::edit_order_meta_box( $post );

		$this->expectOutputString(
			'<div class="cffu-order-details"><div class="cffu-order-detail"><div><span>{bar}:</span> <span>Foo</span></div></div></div>'
		);
	}

	public function test_meta_box_render_hpos() {
		$order = $this->create_order_with_responses(
			[
				'bar' => [
					'type' => 'text',
					'data' => 'Foo',
				],
			]
		);

		Display::edit_order_meta_box( $order );

		$this->expectOutputString(
			'<div class="cffu-order-details"><div class="cffu-order-detail"><div><span>{bar}:</span> <span>Foo</span></div></div></div>'
		);
	}

	public function test_plugin_action_links() {
		$actions = apply_filters( 'plugin_action_links_' . plugin_basename( CFFU_PLUGIN_FILE ), [] );

		$this->assertContains(
			'<a href="http://example.org/wp-admin/admin.php?page=fields-and-file-upload-settings">Settings</a>',
			$actions
		);
	}
}
