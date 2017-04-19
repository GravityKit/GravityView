<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\Form class.
 *
 * Houses all base Form functionality and provides a uniform
 *  API to various form backends via \GV\Form implementations.
 */
abstract class Form extends Source {
	/**
	 * @var int The ID for this form.
	 *
	 * @api
	 * @since future
	 */
	public $ID = null;

	/**
	 * @var mixed The backing form.
	 */
	private $form;

	/**
	 * Construct a \GV\Form instance by ID.
	 *
	 * @param int|string $form_id The internal form ID.
	 *
	 * @api
	 * @since future
	 * @return \GV\Form|null An instance of this form or null if not found.
	 */
	public static function by_id( $form_id ) {
		return null;
	}

	/**
	 * Get all entries for this form.
	 *
	 * @api
	 * @since future
	 *
	 * @return \GV\Entry_Collection The \GV\Entry_Collection
	 */
	abstract public function get_entries();

	/**
	 * Magic shortcuts.
	 *
	 * - `entries` -> `$this->get_entries()`
	 */
	public function __get( $key ) {
		switch ( $key ):
			case 'entries':
				return $this->get_entries();
		endswitch;
	}
}
