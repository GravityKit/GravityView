<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) )
	die();

/**
 * A collection of \GV\View objects.
 */
class ViewList extends DefaultList {
	/**
	 * Add a \GV\View to this collection.
	 *
	 * @param \GV\View $view The view to append to the internal array.
	 *
	 * @throws \InvalidArgumentException if $view is not of type \GV\View.
	 *
	 * @api
	 * @since future
	 * @return void
	 */
	public function append( $view ) {
		if ( ! $view instanceof View ) {
			throw new \InvalidArgumentException( __( 'ViewLists can only contain objects of type \GV\View.', 'gravityview' ) );
		}
		parent::append( $view );
	}
}
