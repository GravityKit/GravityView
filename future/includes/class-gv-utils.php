<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Generic utilities.
 */
class Utils {
	/**
	 * Grab a value from the _GET superglobal or default.
	 *
	 * @param string $name The key name (will be prefixed).
	 * @param mixed $default The default value if not found (Default: null)
	 *
	 * @return mixed The value or $default if not found.
	 */
	public static function _GET( $name, $default = null ) {
		return self::get( $_GET, $name, $default );
	}

	/**
	 * Grab a value from the _POST superglobal or default.
	 *
	 * @param string $name The key name (will be prefixed).
	 * @param mixed $default The default value if not found (Default: null)
	 *
	 * @return mixed The value or $default if not found.
	 */
	public static function _POST( $name, $default = null ) {
		return self::get( $_POST, $name, $default );
	}

	/**
	 * Grab a value from the _REQUEST superglobal or default.
	 *
	 * @param string $name The key name (will be prefixed).
	 * @param mixed $default The default value if not found (Default: null)
	 *
	 * @return mixed The value or $default if not found.
	 */
	public static function _REQUEST( $name, $default = null ) {
		return self::get( $_REQUEST, $name, $default );
	}

	/**
	 * Grab a value from the _SERVER superglobal or default.
	 *
	 * @param string $name The key name (will be prefixed).
	 * @param mixed $default The default value if not found (Default: null)
	 *
	 * @return mixed The value or $default if not found.
	 */
	public static function _SERVER( $name, $default = null ) {
		return self::get( $_SERVER, $name, $default );
	}

	/**
	 * Grab a value from an array or default.
	 *
	 * Supports nested arrays via / key delimiters.
	 *
	 * @param array $array The array.
	 * @param string $key The key.
	 * @param mixed $default The default value. Default: null
	 *
	 * @return mixed  The value or $default if not found.
	 */
	public static function get( $array, $key, $default = null ) {
		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof \ArrayAccess ) ) {
			return $default;
		}

		if ( isset( $array[ $key ] ) ) {
			return $array[ $key ];
		}

		if ( count( $parts = explode( '/', $key, 2 ) ) > 1 ) {
			return self::get( self::get( $array, $parts[0] ), $parts[1], $default );
		}

		return $default;
	}
}
