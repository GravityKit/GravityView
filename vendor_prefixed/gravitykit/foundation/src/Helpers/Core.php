<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 07-November-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Helpers;

use Closure;
use Exception;

class Core {
	/**
	 * Processes return object based on the request type (e.g., AJAX) and status.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed|Exception $return_object Return object (default: 'true').
	 *
	 * @throws Exception
	 *
	 * @return void|mixed Send JSON response if an AJAX request or return the response as is.
	 */
	public static function process_return( $return_object = true ) {
		$is_error = $return_object instanceof Exception;

		if ( wp_doing_ajax() ) {
			$buffer = ob_get_clean();

			if ( $buffer ) {
				error_log( "[GravityKit] Buffer output before returning AJAX response: {$buffer}" );

				header( 'GravityKit: ' . json_encode( $buffer ) );
			}

			if ( $is_error ) {
				wp_send_json_error( $return_object->getMessage() );
			} else {
				wp_send_json_success( $return_object );
			}
		}

		if ( $is_error ) {
			throw new Exception( $return_object->getMessage() );
		}

		return $return_object;
	}

	/**
	 * Returns path to UI assets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file (optional) File name to append to path.
	 *
	 * @return string
	 */
	public static function get_assets_path( $file = '' ) {
		$path = realpath( __DIR__ . '/../../assets' );

		return $file ? trailingslashit( $path ) . $file : $path;
	}

	/**
	 * Returns URL to UI assets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file (optional) File name to append to URL.
	 *
	 * @return string
	 */
	public static function get_assets_url( $file = '' ) {
		$url = plugin_dir_url( self::get_assets_path() ) . 'assets';

		return $file ? trailingslashit( $url ) . $file : $url;
	}

	/**
	 * Checks if the current page is a network admin area.
	 * The AJAX check is not to be fully relied upon as the referer can be changed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_network_admin() {
		return ! wp_doing_ajax()
			? is_network_admin()
			: is_multisite() && strpos( wp_get_referer(), network_admin_url() ) !== false;
	}

	/**
	 * Wrapper for WP's get_plugins() function.
	 *
	 * @see   https://github.com/WordPress/wordpress-develop/blob/2bb5679d666474d024352fa53f07344affef7e69/src/wp-admin/includes/plugin.php#L274-L411
	 *
	 * @since 1.0.0
	 *
	 * @return array[]
	 */
	public static function get_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugins();
	}

	/**
	 * Wrapper for WP's get_plugin_data() function.
	 *
	 * @see   https://github.com/WordPress/wordpress-develop/blob/2bb5679d666474d024352fa53f07344affef7e69/src/wp-admin/includes/plugin.php#L72-L118
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 * @param bool   $markup      (optional) If the returned data should have HTML markup applied. Default is true.
	 * @param bool   $translate   (optional) If the returned data should be translated. Default is true.
	 *
	 * @return array[]
	 */
	public static function get_plugin_data( $plugin_file, $markup = true, $translate = true ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugin_data( $plugin_file, $markup, $translate );
	}

	/**
	 * Checks if value is a callable function.
	 *
	 * @since 1.0.0
	 *
	 * @param string|mixed $value Value to check.
	 *
	 * @return bool
	 */
	public static function is_callable_function( $value ) {
		return ( is_string( $value ) && function_exists( $value ) ) ||
		       ( is_object( $value ) && ( $value instanceof Closure ) );
	}

	/**
	 * Checks if value is a callable class method.
	 *
	 * @since 1.0.0
	 *
	 * @param array|mixed $value Value to check.
	 *
	 * @return bool
	 */
	public static function is_callable_class_method( $value ) {
		if ( ! is_array( $value ) || count( $value ) !== 2 ) {
			return false;
		}

		$value = array_values( $value );

		return ( is_object( $value[0] ) || is_string( $value[0] ) ) &&
		       method_exists( $value[0], $value[1] );
	}
}
