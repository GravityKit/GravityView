<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A collection of \GV\Form objects.
 *
 * @implements Collection<Form>
 */
class Form_Collection extends Collection {
	/**
	 * Add a \GV\Form to this collection.
	 *
	 * @param \GV\Form $form The form to add to the internal array.
	 *
	 * @api
	 * @since 2.0
	 * @return void
	 */
	public function add( $form ) {
		if ( ! $form instanceof Form ) {
			gravityview()->log->error( 'Form_Collections can only contain objects of type \GV\Form.' );
			return;
		}
		parent::add( $form );
	}

	/**
	 * Get a \GV\Form from this list.
	 *
	 * @param int    $form_id The ID of the form to get.
	 * @param string $backend The form backend identifier, allows for multiple form backends in the future. Unused until then.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return \GV\Form|null The \GV\Form with the $form_id as the ID, or null if not found.
	 */
	public function get( $form_id, $backend = 'gravityforms' ) {
		foreach ( $this->all() as $form ) {
			if ( $form->ID == $form_id ) {
				return $form;
			}
		}
		return null;
	}
}
