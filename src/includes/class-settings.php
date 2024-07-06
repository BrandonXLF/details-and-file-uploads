<?php
/**
 * Settings page.
 *
 * @package Checkout Fields and File Upload
 */

namespace CFFU_Plugin;

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
				return ! boolval( $value['deleted'] ?? false ) && count(
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
					$unique = $field_setting['unique'] ?? false;
					$id     = $unique ? $name . '---' . ( $value[ $name ] ?? '' ) : null;

					if (
						( $field_setting['required'] ?? false ) &&
						empty( $value[ $name ] )
					) {
						add_settings_error(
							'cffu_fields',
							'cffu-field-no-' . $name . '-' . $i,
							'Item ' . ( $i + 1 ) . ' does not have a(n) "' . $field_setting['label'] . '"'
						);
					}

					if ( ! $unique ) {
						continue;
					}

					if ( $seen_ids[ $id ] ?? false ) {
						add_settings_error(
							'cffu_fields',
							'cffu-field-dup-' . $name . '-' . $i,
							'Item ' . ( $i + 1 ) . ' uses a duplicate "' . $field_setting['label'] . '"'
						);
					}

					$seen_ids[ $id ] = true;
				}

				foreach ( self::FIELD_SETTINGS as $name => $field_setting ) {
					if ( 'list' === $field_setting['type'] ) {
						if ( ! array_key_exists( $name, $value ) ) {
							$value[ $name ] = [];
						} elseif ( ! is_array( $value[ $name ] ) ) {
							$value[ $name ] = array_filter( explode( ',', $value[ $name ] ) );

							if ( 'int' === $field_setting['item-type'] ) {
								$value[ $name ] = array_map( 'intval', $value[ $name ] );
							}
						}
					} elseif ( 'checkbox' === $field_setting['type'] ) {
						$value[ $name ] = boolval( $value[ $name ] ?? false );
					}
				}

				return $value;
			},
			$filtered,
			array_keys( $filtered )
		);
	}

	/**
	 * Get the name for a given index and setting.
	 *
	 * @param int    $i The index of the field.
	 * @param string $name The name of the setting.
	 */
	private static function input_name( $i, $name ) {
		return 'cffu_fields[' . $i . '][' . $name . ']';
	}

	/**
	 * Print a data-shown-cond attribute if required.
	 *
	 * @param array $field_setting The field's config.
	 */
	private static function print_shown_attr( &$field_setting ) {
		if ( ! ( $field_setting['shown'] ?? false ) ) {
			return;
		}

		echo ' data-shown-cond="' . esc_attr( wp_json_encode( $field_setting['shown'] ) ) . '"';
	}


	/**
	 * Show a field setting for the settings page.
	 *
	 * @param int    $i The index of the field.
	 * @param array  $field The field's data.
	 * @param string $name The name of the setting.
	 * @param array  $field_setting The field's config.
	 */
	public static function print_setting( $i, $field, $name, $field_setting ) {
		if ( ! isset( $field ) && ( $field_setting['existing'] ?? false ) ) {
			return;
		}

		$type     = $field_setting['type'];
		$input_id = uniqid( 'field-input-', true );

		echo '<label for="' . esc_attr( $input_id ) . '"';
		self::print_shown_attr( $field_setting );
		echo '>';
		echo esc_html( $field_setting['label'] ) . ' ';
		echo '</label>';

		echo '<div';
		self::print_shown_attr( $field_setting );
		echo '>';

		$list = false;

		if ( 'select' === $type ) {
			echo '<select id="' . esc_attr( $input_id ) . '" name="' . esc_attr( self::input_name( $i, $name ) ) . '">';

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

			echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( self::input_name( $i, $name ) ) . '" ';

			if ( 'checkbox' !== $type ) {
				echo 'class="regular-text" ';
			}

			echo 'id="' . esc_attr( $input_id ) . '" ';

			if ( 'checkbox' === $type ) {
				echo 'value="1" ';
			} else {
				$value = isset( $field )
					? ( $list ? implode( ',', $field[ $name ] ?? [] ) : $field[ $name ] ?? '' )
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
			echo '<div';
			self::print_shown_attr( $field_setting );
			echo ' class="description list-help"></div>';

			echo '<p';
			self::print_shown_attr( $field_setting );
			echo ' class="description list-help">Separate values with commas (,)</p>';
		}
	}

	/**
	 * Show settings for field.
	 */
	public static function field_settings() {
		$fields = get_option( 'cffu_fields', [] );

		echo '<div id="fields">';

		if ( ! $fields ) {
			echo 'No checkout fields found. Add one below to get started!';
		}

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
		$fields = get_option( 'cffu_fields', [] );

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
		$checked = get_option( 'cffu_hide_notes', false );

		echo '<input type="checkbox" name="cffu_hide_notes" ' . ( $checked ? 'checked' : '' ) . '>';
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting(
			'cffu_settings',
			'cffu_fields',
			[
				'sanitize_callback' => [ __CLASS__, 'sanitize_fields' ],
			]
		);

		register_setting(
			'cffu_settings',
			'cffu_hide_notes'
		);

		add_settings_section(
			'cffu_config_settings',
			null,
			null,
			'fields-and-file-upload-settings'
		);

		add_settings_field(
			'cffu_fields_setting',
			'Checkout fields',
			[ __CLASS__, 'field_settings' ],
			'fields-and-file-upload-settings',
			'cffu_config_settings'
		);

		add_settings_field(
			'cffu_new_field_setting',
			'Add new field',
			[ __CLASS__, 'new_field_setting' ],
			'fields-and-file-upload-settings',
			'cffu_config_settings'
		);

		add_settings_field(
			'cffu_hide_note_setting',
			'Hide order notes',
			[ __CLASS__, 'hide_notes_setting' ],
			'fields-and-file-upload-settings',
			'cffu_config_settings'
		);
	}

	/**
	 * Create settings page HTML.
	 */
	public static function settings_page() {
		wp_enqueue_style(
			'cffu_settings_styles',
			plugin_dir_url( CFFU_PLUGIN_FILE ) . 'src/css/settings.css',
			null,
			CFFU_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'cffu_settings_script',
			plugin_dir_url( CFFU_PLUGIN_FILE ) . 'src/js/settings.js',
			[ 'jquery', 'jquery-ui-sortable', 'jquery-ui-accordion' ],
			CFFU_PLUGIN_VERSION,
			[ 'in_footer' => true ]
		);
		?>
		<div class="wrap">
			<h1>Checkout Fields and File Upload Settings</h1>
			<form action="options.php" method="post">
				<p>
					PHP max upload size is set to <?php echo esc_html( size_format( wp_max_upload_size() ) ); ?>.
				</p>
				<?php settings_fields( 'cffu_settings' ); ?>
				<?php settings_errors(); ?>
				<?php do_settings_sections( 'fields-and-file-upload-settings' ); ?>
				<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save Changes', 'fields-and-file-upload' ); ?>" />
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
			'Checkout Fields and File Upload Settings',
			'Fields and File Upload',
			'manage_woocommerce',
			'fields-and-file-upload-settings',
			[ __CLASS__, 'settings_page' ]
		);
	}
}
