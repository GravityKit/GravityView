<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) )
	die();

/**
 * A generic List base class.
 */
class DefaultList {
	/**
	 * @var array Main storage for objects in this list.
	 */
	private $storage = array();

	/**
	 * Add an object to this list.
	 *
	 * @param mixed $value The object to be added.
	 *
	 * @api
	 * @since future
	 * @return void
	 */
	public function append( $value ) {
		$this->storage []= $value;
	}

	/**
	 * Returns all the objects in this list as an an array.
	 *
	 * @api
	 * @since future
	 * @return array The objects in this list.
	 */
	public function all() {
		return $this->storage;
	}

	/**
	 * Returns the count of the objects in this list.
	 *
	 * @api
	 * @since future
	 * @return int The size of this list.
	 */
	public function count() {
		return count( $this->storage );
	}
}
