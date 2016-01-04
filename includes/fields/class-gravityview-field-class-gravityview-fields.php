<?php
/**
 * @file class-gravityview-field-class-gravityview-fields.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Fields extends GF_Fields {

	/* @var GravityView_Field[] */
	private static $_fields = array();

	public static function register( $field ) {
		if ( ! is_subclass_of( $field, 'GravityView_Field' ) ) {
			throw new Exception( 'Must be a subclass of GravityView_Field' );
		}
		if ( empty( $field->type ) ) {
			throw new Exception( 'The type must be set' );
		}
		if ( isset( self::$_fields[ $field->type ] ) ) {
			throw new Exception( 'Field type already registered: ' . $field->type );
		}
		self::$_fields[ $field->type ] = $field;
	}

	/**
	 * @param $properties
	 *
	 * @return GravityView_Field | bool
	 */
	public static function create( $properties ) {
		$type = isset($properties['type']) ? $properties['type'] : '';
		$type = empty( $properties['inputType'] ) ? $type : $properties['inputType'];
		if ( empty($type) || ! isset( self::$_fields[ $type ] ) ) {
			return new GravityView_Field( $properties );
		}
		$class      = self::$_fields[ $type ];
		$class_name = get_class( $class );
		$field      = new $class_name( $properties );

		return $field;

	}
}
