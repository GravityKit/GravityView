<?php
/**
 * @file class-gravityview-fields.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Wanted to extend GF_Fields, but couldn't because static variables are inherited,
 * so $_fields would always be GF results
 *
 * @see GF_Fields
 */
final class GravityView_Fields {

	/* @var GravityView_Field[] */
	protected static $_fields = array();

	/**
	 * @param GravityView_Field $field Field to register
	 *
	 * @throws Exception If requirements aren't met
	 *
	 * @return void
	 */
	public static function register( $field ) {
		if ( ! is_subclass_of( $field, 'GravityView_Field' ) ) {
			throw new Exception( 'Must be a subclass of GravityView_Field' );
		}
		if ( empty( $field->name ) ) {
			throw new Exception( 'The name must be set' );
		}
		if ( isset( self::$_fields[ $field->name ] ) && ! defined( 'DOING_GRAVITYVIEW_TESTS' ) ) {
			throw new Exception( 'Field type already registered: ' . $field->name );
		}
		self::$_fields[ $field->name ] = $field;
	}

	/**
	 * @param array $properties
	 *
	 * @return GravityView_Field | bool
	 */
	public static function create( $properties ) {
		$type = isset( $properties['type'] ) ? $properties['type'] : '';
		$type = empty( $properties['inputType'] ) ? $type : $properties['inputType'];
		if ( empty( $type ) || ! isset( self::$_fields[ $type ] ) ) {
			return new GravityView_Field( $properties );
		}
		$class      = self::$_fields[ $type ];
		$class_name = get_class( $class );
		$field      = new $class_name( $properties );

		return $field;
	}

	/**
	 * Does the field exist (has it been registered)?
	 *
	 * @param string $field_name
	 *
	 * @return bool True: yes, it exists; False: nope
	 */
	public static function exists( $field_name ) {
		return isset( self::$_fields["{$field_name}"] );
	}

	/**
	 * @param string $field_name
	 *
	 * @return GravityView_Field|false
	 */
	public static function get_instance( $field_name ) {
		return isset( self::$_fields[ $field_name ] ) ? self::$_fields[ $field_name ] : false;
	}

	/**
	 * Alias for get_instance()
	 *
	 * @param $field_name
	 *
	 * @return GravityView_Field|false
	 */
	public static function get( $field_name ) {
		return self::get_instance( $field_name );
	}

	/**
	 * Alias for get_instance()
	 *
	 * @param string|GF_Field $gf_field Gravity Forms field class or the class name type
	 *
	 * @return GravityView_Field|false Returns false if no matching fields found
	 */
	public static function get_associated_field( $gf_field ) {

		$field_type = is_a( $gf_field, 'GF_Field' ) ? get_class( $gf_field ) : $gf_field;

		foreach( self::$_fields as $field ) {
			if( $field_type === $field->_gf_field_class_name ) {
				return $field;
			}
		}

		return false;
	}

	/**
	 * Get all fields
	 *
	 * @since 1.16 Added $group parameter
	 *
	 * @param string $group Optional. If defined, fetch all fields in a group
	 *
	 * @return GravityView_Field[]
	 */
	public static function get_all( $group = '' ) {

		if( '' !== $group ) {
			$return_fields = self::$_fields;
			foreach ( $return_fields as $key => $field ) {
				if( $group !== $field->group ) {
					unset( $return_fields[ $key ] );
				}
			}
			return $return_fields;
		} else {
			return self::$_fields;
		}
	}

}