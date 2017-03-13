<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A collection of \GV\Entry objects.
 */
class Entry_Collection extends Collection {
	/**
	 * Lazy fetching and counting of data defers
	 *  all processing of entries and entry data until
	 *  it is really requested.
	 *
	 * @see \GV\Entry_Collection::add_fetch_callback
	 * @see \GV\Entry_Collection::add_count_callback
	 *
	 * @var array Lazy data loading callbacks.
	 */
	private $callbacks = array();

	/**
	 * @var \GV\Entry_Filter[] Filtering criteria.
	 */
	private $filters = array();

	/**
	 * @var \GV\Entry_Sort[] Sorting criteria.
	 */
	private $sorts = array();

	/**
	 * @var \GV\Entry_Offset Pagination, limit and offset criteria.
	 */
	private $_offset = null;

	/**
	 * Add an \GV\Entry to this collection.
	 *
	 * @param \GV\Entry $entry The entry to add to the internal array.
	 *
	 * @throws \InvalidArgumentException if $field is not of type \GV\Entry.
	 *
	 * @api
	 * @since future
	 * @return void
	 */
	public function add( $entry ) {
		if ( ! $entry instanceof Entry ) {
			throw new \InvalidArgumentException( 'Entry_Collections can only contain objects of type \GV\Entry.' );
		}
		parent::add( $entry );
	}

	/**
	 * Get a \GV\Entry from this list.
	 *
	 * @param int $entry_id The ID of the entry to get.
	 * @param string $backend The form backend identifier, allows for multiple form backends in the future. Unused until then.
	 *
	 * @api
	 * @since future
	 *
	 * @return \GV\Entry|null The \GV\entry with the $entry_id as the ID, or null if not found.
	 */
	public function get( $entry_id, $backend = 'gravityforms' ) {
		foreach ( $this->all() as $entry ) {
			if ( $entry->ID == $entry_id ) {
				return $entry;
			}
		}
		return null;
	}

	/**
	 * Count the current number of \GV\Entry objects here.
	 *
	 * @api
	 * @since future
	 *
	 * @return int The number of entries here.
	 */
	public function count() {
		$count = 0;

		/** Call all lazy callbacks. */
		foreach ( $this->callbacks as $callback ) {
			if ( $callback[0] != 'count' ) {
				continue;
			}

			$count += $callback[1]( $this->filters );
		}

		return $count;
	}

	/**
	 * Get the entries as an array.
	 *
	 * @api
	 * @since future
	 *
	 * @return \GV\Entry[] The entries as an array.
	 */
	public function all() {
		/** This collection has already been hydrated. */
		if ( parent::count() ) {
			return parent::all();
		}

		$entries = array();

		/** Call all lazy callbacks. */
		foreach ( $this->callbacks as $callback ) {
			if ( $callback[0] != 'fetch' ) {
				continue;
			}

			$entries = array_merge( $entries, $callback[1]( $this->filters, $this->sorts, $this->_offset )->all() );
		}

		return $entries;
	}

	/**
	 * Apply a filter to the current collection.
	 *
	 * This operation is non-destructive as a copy of the collection is returned.
	 *
	 * @param \GV\Entry_Filter $filter The filter to be applied.
	 *
	 * @api
	 * @since future
	 *
	 * @return \GV\Entry_Collection A copy of the this collection with the filter applied.
	 */
	public function filter( \GV\Entry_Filter $filter ) {
		$collection = clone( $this );

		array_push( $collection->filters, $filter );

		return $collection;
	}

	/**
	 * Sort.
	 *
	 * @param \GV\Entry_Sort $sort The sort to apply to this collection.
	 *
	 * @api
	 * @since future
	 *
	 * @return \GV\Entry_Collection A copy of the this collection with the sort applied.
	 */
	public function sort( $sort ) {
		$collection = clone( $this );

		array_push( $collection->sorts, $sort );

		return $collection;
	}

	/**
	 * Limit the fetch to a specified window.
	 *
	 * @param int $limit The limit.
	 *
	 * @api
	 * @since future
	 *
	 * @return \GV\Entry_Collection A copy of the this collection with the limit applied.
	 */
	public function limit( $limit ) {
		$collection = clone( $this );

		if ( ! $collection->_offset ) {
			$collection->_offset = new Entry_Offset();
		}
		$collection->_offset->limit = $limit;

		return $collection;
	}

	/**
	 * Skip $offset entries.
	 *
	 * Useful, you know, for pagination and stuff.
	 *
	 * @param int $offset The number of entries to skip.
	 *
	 * @api
	 * @since future
	 *
	 * @return \GV\Entry_Collection A copy of the this collection with the offset applied.
	 */
	public function offset( $offset ) {
		$collection = clone( $this );

		if ( ! $collection->_offset ) {
			$collection->_offset = new Entry_Offset();
		}
		$collection->_offset->offset = $offset;

		return $collection;
	}

	/**
	 * Defer fetching of data to the provided callable.
	 *
	 * The callback signature should be as follows:
	 *  \GV\Entry_Collection callback( \GV\Entry_Filter $filter, \GV\Entry_Sort $sort, \GV\Entry_Offset $offset );
	 *
	 * The methods that trigger the callback are:
	 * - \GV\Entry_Collection::all
	 * - \GV\Entry_Collection::last
	 *
	 * @param callable $callback The callback to call when needed.
	 *
	 * @internal
	 * @since future
	 *
	 * @return void
	 */
	public function add_fetch_callback( $callback ) {
		$this->add_callback( 'fetch', $callback );
	}

	/**
	 * Defer counting of data to the provided callable.
	 *
	 * The callback signature should be as follows:
	 *  int callback( \GV\Entry_Filter $filter );
	 *
	 * The methods that trigger the callback are:
	 * - \GV\Entry_Collection::count
	 *
	 * @param callable $callback The callback to call when needed.
	 *
	 * @internal
	 * @since future
	 *
	 * @return void
	 */
	public function add_count_callback( $callback ) {
		$this->add_callback( 'count', $callback );
	}

	/**
	 * Add a callback for lazy loading/counting.
	 *
	 * @param callable $callback The callback to call when needed.
	 *
	 * @return void
	 */
	private function add_callback( $type, $callback ) {
		if ( ! is_callable( $callback ) ) {
			return;
		}

		$this->callbacks []= array( $type, $callback );
	}
}
