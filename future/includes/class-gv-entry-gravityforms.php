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
		$entry = null;

		/** Always try to grab by numeric ID first. */
		if ( is_numeric( $entry_id ) ) {
			$entry = \GFAPI::get_entry( $entry_id );
		}

		if ( ! $entry || is_wp_error( $entry ) ) {
			/** Hmm, slugs? Must be. */
			if ( apply_filters( 'gravityview_custom_entry_slug', false ) ) {
				return self::by_slug( $entry_id );
			}

			return null;
		}

		return self::from_entry( $entry );
	}

	/**
	 * Construct a \GV\Entry instance by slug name.
	 *
	 * @param int|string $entry_slug The registered slug for the entry.
	 * @param int $form_id The form ID, since slugs can be non-unique. Default: 0.
	 *
	 * @api
	 * @since future
	 * @return \GV\Entry|null An instance of this entry or null if not found.
	 */
	public static function by_slug( $entry_slug, $form_id = 0 ) {
		global $wpdb;

		$lead_meta = \GFFormsModel::get_lead_meta_table_name();

		$sql = "SELECT lead_id FROM $lead_meta WHERE meta_key = 'gravityview_unique_id' AND";

		if ( $form_id = apply_filters( 'gravityview/common/get_entry_id_from_slug/form_id', $form_id ) ) {
			$sql = $wpdb->prepare( "$sql meta_value = %s AND form_id = %s", $entry_slug, $form_id );
		} else {
			$sql = $wpdb->prepare( "$sql meta_value = %s", $entry_slug );
		}

		$entry_id = $wpdb->get_var( $sql );

		if ( ! is_numeric( $entry_id ) ) {
			return null;
		}

		return self::by_id( $entry_id );
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
