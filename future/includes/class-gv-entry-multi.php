<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The multi-entry Entry implementation.
 *
 * An entry that is really a join of 2+ entries.
 * Used for JOINS in the \GF_Query component.
 */
class Multi_Entry extends Entry implements \ArrayAccess {
	/**
	 * The entries in this form.
	 */
	public $entries = array();

	/**
	 * @var string The identifier of the backend used for this entry.
	 * @api
	 * @since 2.0
	 */
	public static $backend = 'multi';

	/**
	 * Initialization.
	 */
	private function __construct() {
	}

	/**
	 * Construct a multientry from an array of entries.
	 *
	 * @param \GV\Entry[] $entries The entries.
	 *
	 * @return \GV\Multi_Entry A multientry object.
	 */
	public static function from_entries( $entries ) {
		$_entry = new self();
		foreach ( $entries as &$entry ) {
			if ( ! $entry instanceof Entry ) {
				continue;
			}
			$_entry->entries[ $entry['form_id'] ]  = &$entry;
		}
		return $_entry;
	}

	/**
	 * Fake legacy template support.
	 *
	 * Take the first entry and set it as the current entry.
	 * But support nesting.
	 *
	 * @return array See \GV\Entry::as_entry()
	 */
	public function as_entry() {
		$_entry = array();

		if ( $entry = reset( $this->entries ) ) {
			$_entry = $entry->as_entry();

			foreach ( $this->entries as $entry ) {
				$entry = $entry->as_entry();
				$_entry['_multi'][ $entry['form_id'] ] = $entry;
			}
		}

		return $_entry;
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms entry array.
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 * @return bool Whether the offset exists or not.
	 */
	public function offsetExists( $offset ) {
		return isset( $this->entries[ $offset ] );
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms entry array.
	 *
	 * Maps the old keys to the new data;
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 *
	 * @return mixed The value of the requested entry data.
	 */
	public function offsetGet( $offset ) {
		if ( ! $this->offsetExists( $offset ) ) {
			return null;
		}
		return $this->entries[ $offset ];
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms entry array.
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value ) {
		gravityview()->log->error( 'The underlying multi entry is immutable. This is a \GV\Entry object and should not be accessed as an array.' );
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms entry array.
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		gravityview()->log->error( 'The underlying multi entry is immutable. This is a \GV\Entry object and should not be accessed as an array.' );
	}
}
