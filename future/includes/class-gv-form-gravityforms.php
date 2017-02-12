<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) )
	die();

/**
 * The Gravity Forms Form class implementation.
 *
 * Accessible as an array for back-compatibility.
 */
class GF_Form extends Form implements \ArrayAccess {

	/**
	 * @var string The identifier of the backend used for this form.
	 * @api
	 * @since future
	 */
	public static $backend = 'gravityforms';

	/**
	 * Initialization.
	 *
	 * @throws \RuntimeException if the Gravity Forms plugin is not active.
	 */
	private function __construct() {
		if ( ! class_exists( 'GFAPI' ) ) {
			throw new \RuntimeException( 'Gravity Forms plugin not active.' );
		}
	}

	/**
	 * Construct a \GV\GF_Form instance by ID.
	 *
	 * @param int|string $form_id The internal form ID.
	 *
	 * @api
	 * @since future
	 * @return \GV\GF_Form|null An instance of this form or null if not found.
	 */
	public static function by_id( $form_id ) {
		$form = \GFAPI::get_form( $form_id );
		if ( !$form )
			return null;

		$self = new self();
		$self->form = $form;

		$self->ID = $self->form['id'];

		return $self;
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 * @return bool Whether the offset exists or not.
	 */
	public function offsetExists( $offset ) {
		return isset( $this->form[$offset] );
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * Maps the old keys to the new data;
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 *
	 * @throws \RuntimeException during tests if called outside of whiteliested cases.
	 *
	 * @return mixed The value of the requested form data.
	 */
	public function offsetGet( $offset ) {
		return $this->form[$offset];
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 *
	 * @throws \RuntimeException The underlying form data is immutable.
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value ) {
		throw new \RuntimeException( 'The underlying Gravity Forms form is immutable. This is a \GV\Form object and should not be accessed as an array.' );
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		throw new \RuntimeException( 'The underlying Gravity Forms form is immutable. This is a \GV\Form object and should not be accessed as an array.' );
	}
}
