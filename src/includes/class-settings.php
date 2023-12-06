<?php
/**
 * Settings page.
 *
 * @package Details and File Upload
 */

namespace DetailsAndFileUploadPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings page.
 */
class Settings {
	const FIELD_SETTINGS = [
		'deleted'     => [
			'type'     => 'checkbox',
			'label'    => 'Delete',
			'existing' => true,
		],
		'id'          => [
			'type'     => 'text',
			'label'    => 'ID',
			'required' => true,
			'unique'   => true,
			'defining' => true,
		],
		'type'        => [
			'type'     => 'select',
			'label'    => 'Type',
			'options'  => [
				// Default.
				''               => '— Select —',
				// Misc.
				'file'           => 'File',
				'select'         => 'Select',
				'checkbox'       => 'Checkbox',
				'number'         => 'Number',
				// Text.
				'text'           => 'Text',
				'textarea'       => 'Multiline',
				'password'       => 'Password',
				'email'          => 'Email',
				'url'            => 'URL',
				'tel'            => 'Phone #',
				// Date and time.
				'datetime-local' => 'Date & time',
				'date'           => 'Date',
				'time'           => 'Time',
				'month'          => 'Month',
				'week'           => 'Week',
			],
			'required' => true,
			'defining' => true,
		],
		'label'       => [
			'type'     => 'text',
			'label'    => 'Label',
			'defining' => true,
		],
		'options'     => [
			'type'  => 'list',
			'label' => 'Options',
			'shown' => [ 'select' ],
		],
		'multiple'    => [
			'type'  => 'checkbox',
			'label' => 'Multi-file',
			'shown' => [ 'file' ],
		],
		'types'       => [
			'type'      => 'list',
			'item-type' => 'string',
			'label'     => 'File types',
			'items'     => [ __CLASS__, 'get_types' ],
			'initial'   => [ 'image' ],
			'shown'     => [ 'file' ],
		],
		'placeholder' => [
			'type'  => 'text',
			'label' => 'Placeholder',
			'shown' => [ 'number', 'text', 'textarea', 'password', 'email', 'url', 'tel' ],
		],
		'required'    => [
			'type'  => 'checkbox',
			'label' => 'Required',
		],
		'products'    => [
			'type'      => 'list',
			'item-type' => 'int',
			'label'     => 'Products',
			'items'     => [ __CLASS__, 'get_products' ],
		],
		'categories'  => [
			'type'      => 'list',
			'item-type' => 'int',
			'label'     => 'Categories',
			'items'     => [ __CLASS__, 'get_categories' ],
		],
	];


	/**
	 * Initialize settings page hooks.
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_menu', [ __CLASS__, 'configure_admin_menu' ] );
	}

	/**
	 * Get a list of recognized file types.
	 *
	 * @return array<string, string>
	 */
	public static function get_types() {
		$mime_types = wp_get_mime_types();

		$out = [
			'image' => 'Image files',
			'video' => 'Video files',
			'audio' => 'Audio files',
		];

		foreach ( $mime_types as $exts => $_ ) {
			$out[ '.' . explode( '|', $exts )[0] ] = '.' . explode( '|', $exts )[0];
		}

		return $out;
	}

	/**
	 * Get a list of all WooCommerce products.
	 *
	 * @return array<string, string>
	 */
	public static function get_products() {
		$out      = [];
		$products = wc_get_products( [] );

		foreach ( $products as $product ) {
			$out[ strval( $product->get_id() ) ] = $product->get_name();
			$children                            = $product->get_children();

			foreach ( $children as $child_id ) {
				$child_product              = wc_get_product( $child_id );
				$out[ strval( $child_id ) ] = $child_product->get_name();
			}
		}

		return $out;
	}

	/**
	 * Get a list of all all WooCommerce product categories.
	 *
	 * @return array<string, string>
	 */
	public static function get_categories() {
		$out        = [];
		$categories = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			]
		);

		foreach ( $categories as $category ) {
			$out[ strval( $category->term_id ) ] = $category->name;
		}

		return $out;
	}

	/**
	 * Handle settings submission sanitization.
	 *
	 * @param array[] $values The fields.
	 */
	public static function sanitize_fields( $values ) {
		$filtered = array_filter(
			$values,
			function ( $value ) {
				return empty( $value['deleted'] ) && count(
					array_filter(
						$value,
						function ( $val, $key ) {
							return ( self::FIELD_SETTINGS[ $key ]['defining'] ?? false ) && ! empty( $val );
						},
						ARRAY_FILTER_USE_BOTH
					)
				);
			}
		);

		$seen_ids = [];

		return array_map(
			function ( $value, $i ) use ( &$seen_ids ) {
				foreach ( self::FIELD_SETTINGS as $name => $field_setting ) {
					$id = $name . '---' . ( $value[ $name ] ?? '' );

					if (
						( $field_setting['required'] ?? false ) &&
						empty( $value[ $name ] )
					) {
						add_settings_error(
							'details_and_file_uploads_fields',
							'dfu-field-no-' . $name . '-' . $i,
							'Item ' . ( $i + 1 ) . ' does not have a(n) "' . $field_setting['label'] . '"'
						);
					}

					if (
						( $field_setting['unique'] ?? false ) &&
						( $seen_ids[ $id ] ?? false )
					) {
						add_settings_error(
							'details_and_file_uploads_fields',
							'dfu-field-dup-' . $name . '-' . $i,
							'Item ' . ( $i + 1 ) . ' uses a duplicate "' . $field_setting['label'] . '"'
						);
					}

					$seen_ids[ $id ] = true;
				}

				foreach ( self::FIELD_SETTINGS as $name => $field_setting ) {
					if ( 'list' === $field_setting['type'] ) {
						if ( ! $value[ $name ] ) {
							$value[ $name ] = [];
						} elseif ( ! is_array( $value[ $name ] ) ) {
							$value[ $name ] = explode( ',', $value[ $name ] );

							if ( 'int' === $field_settings['item-type'] ) {
								$value[ $name ] = array_map( intval, $value[ $name ] );
							}
						}
					} elseif ( 'checkbox' === $field_setting['type'] ) {
						$value[ $name ] = boolval( $value[ $name ] );
					}
				}

				return $value;
			},
			$filtered,
			array_keys( $filtered )
		);
	}

	/**
	 * Show a field setting for the settings page.
	 *
	 * @param number $i The index of the field.
	 * @param array  $field The field's data.
	 * @param string $name The name of the setting.
	 * @param array  $field_setting The field's config.
	 */
	public static function print_setting( $i, $field, $name, $field_setting ) {
		if ( ! isset( $field ) && ( $field_setting['existing'] ?? false ) ) {
			return;
		}

		$type      = esc_attr( $field_setting['type'] );
		$input_id  = uniqid( 'field-input-', true );
		$full_name = 'details_and_file_uploads_fields[' . esc_attr( $i ) . '][' . esc_attr( $name ) . ']';

		$shown_attr = ( $field_setting['shown'] ?? false )
			? 'data-shown-cond="' . esc_attr( wp_json_encode( $field_setting['shown'] ) ) . '"'
			: '';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<label for="' . $input_id . '" ' . $shown_attr . '>';
		echo esc_html( $field_setting['label'] ) . ' ';
		echo '</label>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div ' . $shown_attr . '>';

		$list = false;

		if ( 'select' === $type ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<select id="' . $input_id . '" name="' . $full_name . '">';

			foreach ( $field_setting['options'] as $value => $text ) {
				echo '<option value="' . esc_attr( $value ) . '" '
					. ( isset( $field ) && $field[ $name ] === $value ? 'selected' : '' )
					. '>' . esc_attr( $text ) . '</option>';
			}

			echo '</select>';
		} else {
			$list = false;

			if ( 'list' === $type ) {
				$type = 'text';
				$list = true;
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<input type="' . $type . '" name="' . $full_name . '" ';

			if ( 'checkbox' !== $type ) {
				echo 'class="regular-text" ';
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo 'id="' . $input_id . '" ';

			if ( 'checkbox' === $type ) {
				echo 'value="1" ';
			} else {
				$value = isset( $field )
					? ( $list ? implode( ',', $field[ $name ] ?? '' ) : $field[ $name ] ?? '' )
					: (
						isset( $field_setting['initial'] )
							? ( $list ? implode( ',', $field_setting['initial'] ) : $field_setting['initial'] )
							: ''
					);

					echo 'value="' . esc_attr( $value ) . '" ';
			}

			if ( 'checkbox' === $type && isset( $field ) && ( $field[ $name ] ?? false ) ) {
				echo 'checked ';
			}

			if ( $list ) {
				echo 'data-is-list ';
			}

			if ( $list && isset( $field_setting['items'] ) ) {
				$items = call_user_func( $field_setting['items'] );

				echo 'data-options="' . esc_attr( wp_json_encode( $items ) ) . '" ';
			}

			echo '>';
		}

		echo '</div>';

		if ( $list ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div ' . $shown_attr . ' class="description list-help"></div>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<p ' . $shown_attr . ' class="description list-help">Separate values with commas (,)</p>';
		}
	}

	/**
	 * Show settings for field.
	 */
	public static function field_settings() {
		$fields = get_option( 'details_and_file_uploads_fields', [] );

		echo '<div id="fields">';

		foreach ( $fields as $i => $field ) {
			echo '<div class="field-cnt">';

			echo '<div class="header">';
			echo '<span class="header-number">' . esc_html( $i + 1 ) . '</span>';
			echo ' - ';
			echo esc_html( $field['label'] ?: ( $field['name'] ?? 'LABEL/ID MISSING!' ) );
			echo ' (' . esc_html( self::FIELD_SETTINGS['type']['options'][ $field['type'] ] ) . ')';
			echo '</div>';

			echo '<div class="field">';

			foreach ( self::FIELD_SETTINGS as $name => $field_setting ) {
				self::print_setting( $i, $field, $name, $field_setting );
			}

			echo '</div></div>';
		}

		echo '</div>';
	}

	/**
	 * Show setting to add new field.
	 */
	public static function new_field_setting() {
		$fields = get_option( 'details_and_file_uploads_fields', [] );

		echo '<div class="field">';

		foreach ( self::FIELD_SETTINGS as $name => $field_setting ) {
			self::print_setting( count( $fields ), null, $name, $field_setting );
		}

		echo '</div>';
	}

	/**
	 * Show setting to hide default WooCommerce order notes.
	 */
	public static function hide_notes_setting() {
		$checked = get_option( 'details_and_file_uploads_hide_notes', false );

		echo '<input type="checkbox" name="details_and_file_uploads_hide_notes" ' . ( $checked ? 'checked' : '' ) . '>';
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting(
			'details_and_file_upload_settings',
			'details_and_file_uploads_fields',
			[
				'sanitize_callback' => [ __CLASS__, 'sanitize_fields' ],
			]
		);

		register_setting(
			'details_and_file_upload_settings',
			'details_and_file_uploads_hide_notes'
		);

		add_settings_section(
			'dfu_config_settings',
			null,
			null,
			'details-and-file-uploads-settings'
		);

		add_settings_field(
			'dfu_fields_setting',
			'Checkout fields',
			[ __CLASS__, 'field_settings' ],
			'details-and-file-uploads-settings',
			'dfu_config_settings'
		);

		add_settings_field(
			'dfu_new_field_setting',
			'Add new field',
			[ __CLASS__, 'new_field_setting' ],
			'details-and-file-uploads-settings',
			'dfu_config_settings'
		);

		add_settings_field(
			'dfu_hide_note_setting',
			'Hide order notes',
			[ __CLASS__, 'hide_notes_setting' ],
			'details-and-file-uploads-settings',
			'dfu_config_settings'
		);
	}

	/**
	 * Create settings page HTML.
	 */
	public static function settings_page() {
		wp_enqueue_style(
			'dfu_settings_styles',
			plugin_dir_url( DETAILS_AND_FILE_UPLOAD_PLUGIN_FILE ) . 'src/css/settings.css'
		);

		wp_enqueue_script(
			'dfu_settings_script',
			plugin_dir_url( DETAILS_AND_FILE_UPLOAD_PLUGIN_FILE ) . 'src/js/settings.js',
			[ 'jquery', 'jquery-ui-sortable', 'jquery-ui-accordion' ]
		);
		?>
		<div class="wrap">
			<h1>Details and File Upload Settings</h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'details_and_file_upload_settings' ); ?>
				<?php settings_errors(); ?>
				<?php do_settings_sections( 'details-and-file-uploads-settings' ); ?>
				<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save Changes', 'default' ); ?>" />
			</form>
		</div>
		<?php
	}

	/**
	 * Add link to admin menu.
	 */
	public static function configure_admin_menu() {
		add_submenu_page(
			'woocommerce',
			'Customer uploads',
			'Details and Files',
			'manage_woocommerce',
			'details-and-file-uploads-settings',
			[ __CLASS__, 'settings_page' ]
		);
	}
}
