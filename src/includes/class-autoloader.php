<?php
/**
 * Plugin file autoloader
 *
 * @package Checkout Fields and File Upload
 */

namespace CFFU_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin file autoloader
 */
class Autoloader {
	/**
	 * Initalize the autoloader
	 */
	public static function init() {
		spl_autoload_register(
			function ( $class_name ) {
				$class_name = str_replace( '\\', '/', $class_name );
				$class_name = str_replace( 'CFFU_Plugin/', '', $class_name );
				$class_name = str_replace( '_', '-', $class_name );
				$class_name = strtolower( $class_name );

				$file = __DIR__ . '/class-' . $class_name . '.php';

				if ( file_exists( $file ) ) {
					include $file;
				}
			}
		);
	}
}
