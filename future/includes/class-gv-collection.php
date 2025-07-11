<?php

namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A generic Collection base class.
 * @template T
 */
class Collection {
	/**
	 * @var array<T> Main storage for objects in this collection.
	 */
	protected $storage = [];

	/**
	 * Add an object to this collection.
	 *
	 * @param T $value The object to be added.
	 *
	 * @api
	 * @since 2.0
	 * @return void
	 */
	public function add( $value ) {
		$this->storage [] = $value;
	}

	/**
	 * Clear this collection.
	 *
	 * @api
	 * @since 2.0
	 * @return void
	 */
	public function clear() {
		$this->count() && ( $this->storage = [] );
	}

	/**
	 * Merge another collection into here.
	 *
	 * @param \GV\Collection<T> $collection The collection to be merged.
	 *
	 * @api
	 * @since 2.0
	 * @return void
	 */
	public function merge( Collection $collection ) {
		array_map( [ $this, 'add' ], $collection->all() );
	}

	/**
	 * Returns all the objects in this collection as an an array.
	 *
	 * @since 2.0
	 * @return array<T> The objects in this collection.
	 * @api
	 */
	public function all() {
		return $this->storage;
	}

	/**
	 * Get the last added object.
	 *
	 * @since 2.0
	 * @return T|null The last item in here, or null if there are none.
	 * @api
	 */
	public function last() {
		return end( $this->storage );
	}

	/**
	 * Get the first added object.
	 *
	 * @since 2.0
	 * @return T|null The first item in here, or null if there are none.
	 * @api
	 */
	public function first() {
		return reset( $this->storage );
	}

	/**
	 * Returns the count of the objects in this collection.
	 *
	 * @since 2.0
	 * @return int The size of this collection.
	 * @api
	 */
	public function count() {
		return count( $this->storage );
	}
}
