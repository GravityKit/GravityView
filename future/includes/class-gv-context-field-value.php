<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\Field_Value_Context class.
 *
 * Houses context for \GV\Field::get_value() calls, which
 *  helps the field retrieve the value for itself.
 */
class Field_Value_Context extends Context {
	/**
	 * @var string The context identifier, used in filters.
	 */
	private $_identifier = 'field_value';

	/**
	 * Set a key to a value.
	 *
	 * @param mixed $key The key the value should be added under.
	 * @param mixed $value The value to be added to the key.
	 *
	 * @api
	 * @since future
	 *
	 * @throws \InvalidArgumentException If key type conditions are not met.
	 *
	 * @return void
	 */
	public function __set( $key, $value ) {
		switch ( $key ):
			case 'view':
				if ( ! $value instanceof \GV\View ) {
					throw new \InvalidArgumentException( '\GV\Field_Value_Context::$view has to be of type \GV\View' );
				}
				break;
			case 'form':
				if ( ! $value instanceof \GV\Form ) {
					throw new \InvalidArgumentException( '\GV\Field_Value_Context::$form has to be of type \GV\Form' );
				}
				break;
			case 'entry':
				if ( ! $value instanceof \GV\Entry ) {
					throw new \InvalidArgumentException( '\GV\Field_Value_Context::$entry has to be of type \GV\Entry' );
				}
				break;
		endswitch;
		parent::__set( $key, $value );
	}
}
