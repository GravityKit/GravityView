<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The Gravity Forms Entry class implementation.
 *
 * Accessible as an array for back-compatibility.
 */
class GF_Entry extends Entry implements \ArrayAccess {

	/**
	 * @var string The identifier of the backend used for this entry.
	 * @api
	 * @since future
	 */
	public static $backend = 'gravityforms';

	/**
	 * Initialization.
	 */
	private function __construct() {
		if ( ! class_exists( 'GFAPI' ) ) {
			gravityview()->log->error( 'Gravity Forms plugin not active.' );
		}
	}

	/**
	 * Construct a \GV\Entry instance by ID.
	 *
	 * @param int|string $entry_id The internal entry ID.
	 *
	 * @api
	 * @since future
	 * @return \GV\Entry|null An instance of this entry or null if not found.
	 */
	public static function by_id( $entry_id ) {
		$entry = \GFAPI::get_entry( $entry_id );
		if ( !$entry ) {
			return null;
		}

		return self::from_entry( $entry );
	}

	/**
	 * Construct a \GV\Entry instance from a Gravity Forms entry array.
	 *
	 * @param array $entry The array ID.
	 *
	 * @return \GV\Entry|null An instance of this entry or null if not found.
	 */
	public static function from_entry( $entry ) {
		if ( empty( $entry['id'] ) ) {
			return null;
		}

		$self = new self();
		$self->entry = $entry;

		$self->ID = $self->entry['id'];

		return $self;
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms entry array.
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 * @return bool Whether the offset exists or not.
	 */
	public function offsetExists( $offset ) {
		return isset( $this->entry[$offset] );
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms entry array.
	 *
	 * Maps the old keys to the new data;
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 *
	 * @return mixed The value of the requested entry data.
	 */
	public function offsetGet( $offset ) {
		return $this->entry[$offset];
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms entry array.
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value ) {
		gravityview()->log->error( 'The underlying Gravity Forms entry is immutable. This is a \GV\Entry object and should not be accessed as an array.' );
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms entry array.
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		gravityview()->log->error( 'The underlying Gravity Forms entry is immutable. This is a \GV\Entry object and should not be accessed as an array.' );
	}
}
