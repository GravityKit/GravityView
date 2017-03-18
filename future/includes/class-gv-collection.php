<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A generic Collection base class.
 */
class Collection {
	/**
	 * @var array Main storage for objects in this collection.
	 */
	private $storage = array();

	/**
	 * Add an object to this collection.
	 *
	 * @param mixed $value The object to be added.
	 *
	 * @api
	 * @since future
	 * @return void
	 */
	public function add( $value ) {
		$this->storage []= $value;
	}

	/**
	 * Clear this collection.
	 *
	 * @api
	 * @since future
	 * @return void
	 */
	public function clear() {
		$this->count() && ( $this->storage = array() );
	}

	/**
	 * Merge another collection into here.
	 *
	 * @param \GV\Collection $collection The collection to be merged.
	 *
	 * @api
	 * @since future
	 * @return void
	 */
	public function merge( \GV\Collection $collection ) {
		array_map( array( $this, 'add'), $collection->all() );
	}

	/**
	 * Returns all the objects in this collection as an an array.
	 *
	 * @api
	 * @since future
	 * @return array The objects in this collection.
	 */
	public function all() {
		return $this->storage;
	}

	/**
	 * Get the last added object.
	 *
	 * @api
	 * @since future
	 * @return mixed|null The last item in here, or null if there are none.
	 */
	public function last() {
		return end( $this->storage );
	}

	/**
	 * Returns the count of the objects in this collection.
	 *
	 * @api
	 * @since future
	 * @return int The size of this collection.
	 */
	public function count() {
		return count( $this->storage );
	}
}
