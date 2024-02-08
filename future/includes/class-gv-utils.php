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
	 * @param mixed  $default The default value if not found (Default: null)
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
	 * @param mixed  $default The default value if not found (Default: null)
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
	 * @param mixed  $default The default value if not found (Default: null)
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
	 * @param mixed  $default The default value if not found (Default: null)
	 *
	 * @return mixed The value or $default if not found.
	 */
	public static function _SERVER( $name, $default = null ) {
		return self::get( $_SERVER, $name, $default );
	}

	/**
	 * Grab a value from an array or an object or default.
	 *
	 * Supports nested arrays, objects via / key delimiters.
	 *
	 * @param array|object|mixed $array The array (or object). If not array or object, returns $default.
	 * @param string             $key The key.
	 * @param mixed              $default The default value. Default: null
	 *
	 * @return mixed  The value or $default if not found.
	 */
	public static function get( $array, $key, $default = null ) {

		if ( ! is_array( $array ) && ! is_object( $array ) ) {
			return $default;
		}

		// Only allow string or integer keys. No null, array, etc.
		if ( ! is_string( $key ) && ! is_int( $key ) ) {
			return $default;
		}

		/**
		 * Try direct key.
		 */
		if ( is_array( $array ) || $array instanceof \ArrayAccess ) {
			if ( isset( $array[ $key ] ) ) {
				return $array[ $key ];
			}
		} elseif ( is_object( $array ) ) {
			if ( isset( $array->$key ) ) {
				return $array->$key;
			}
		}

		/**
		 * Try subkeys after split.
		 */
		if ( count( $parts = explode( '/', $key, 2 ) ) > 1 ) {
			return self::get( self::get( $array, $parts[0] ), $parts[1], $default );
		}

		return $default;
	}

	/**
	 * Sanitizes Excel formulas inside CSV output
	 *
	 * @internal
	 * @since 2.1
	 *
	 * @param string $value The cell value to strip formulas from.
	 *
	 * @return string The sanitized value.
	 */
	public static function strip_excel_formulas( $value ) {

		if ( 0 === strpos( $value, '=' ) ) {
			$value = "'" . $value;
		}

		return $value;
	}

	/**
	 * Return a value by call.
	 *
	 * Use for quick hook callback returns and whatnot.
	 *
	 * @internal
	 * @since 2.1
	 *
	 * @param mixed $value The value to return from the closure.
	 *
	 * @return Closure The closure with the $value bound.
	 */
	public static function _return( $value ) {
		return function () use ( $value ) {
			return $value;
		};
	}

	/**
	 * Output an associative array represenation of the query parameters.
	 *
	 * @internal
	 * @since 2.1
	 *
	 * @param \GF_Query The query object to dump.
	 *
	 * @return array An associative array of parameters.
	 */
	public static function gf_query_debug( $query ) {
		$introspect = $query->_introspect();
		return array(
			'where' => $query->_where_unwrap( $introspect['where'] ),
		);
	}

	/**
	 * Strips aliases in columns
	 *
	 * @see https://github.com/gravityview/GravityView/issues/1308#issuecomment-617075190
	 *
	 * @internal
	 *
	 * @since 2.8.1
	 *
	 * @param \GF_Query_Condition $condition The condition to strip column aliases from.
	 *
	 * @return \GF_Query_Condition
	 */
	public static function gf_query_strip_condition_column_aliases( $condition ) {
		if ( $condition->expressions ) {
			$conditions = array();
			foreach ( $condition->expressions as $expression ) {
				$conditions[] = self::gf_query_strip_condition_column_aliases( $expression );
			}
			return call_user_func_array(
				array( '\GF_Query_Condition', 'AND' == $condition->operator ? '_and' : '_or' ),
				$conditions
			);
		} elseif ( $condition->left instanceof \GF_Query_Column ) {
				return new \GF_Query_Condition(
					new \GF_Query_Column( $condition->left->field_id ),
					$condition->operator,
					$condition->right
				);
		}

		return $condition;
	}
}
