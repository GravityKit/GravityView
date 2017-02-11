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
class GF_Form extends Form /* implements \ArrayAccess */ {

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
}
