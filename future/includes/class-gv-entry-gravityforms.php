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
	 * @since 2.0
	 */
	public static $backend = 'gravityforms';

	/**
	 * The entry slug.
	 *
	 * @var string
	 */
	public $slug;

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
	 * @param int        $form_id The form ID, since slugs can be non-unique. Default: 0.
	 *
	 * @api
	 * @since 2.0
	 * @return \GV\GF_Entry|null An instance of this entry or null if not found.
	 */
	public static function by_id( $entry_id, $form_id = 0 ) {
		$entry = null;

		/** Always try to grab by numeric ID first. */
		if ( is_numeric( $entry_id ) ) {
			$entry = \GFAPI::get_entry( $entry_id );
		}

		if ( ! $entry || is_wp_error( $entry ) ) {
			/**
			 * Filter whether to enable custom entry slugs.
			 *
			 * When enabled, entries can be retrieved by slug instead of just numeric ID.
			 *
			 * @since 2.0
			 *
			 * @param bool $enable_custom_slug Whether to enable custom entry slugs. Default: false.
			 */
			if ( apply_filters( 'gravityview_custom_entry_slug', false ) ) {
				return self::by_slug( $entry_id, $form_id );
			}

			return null;
		}

		return self::from_entry( $entry );
	}

	/**
	 * Construct a \GV\Entry instance by slug name.
	 *
	 * @param int|string $entry_slug The registered slug for the entry.
	 * @param int        $form_id The form ID, since slugs can be non-unique. Default: 0.
	 *
	 * @api
	 * @since 2.0
	 * @return \GV\GF_Entry|null An instance of this entry or null if not found.
	 */
	public static function by_slug( $entry_slug, $form_id = 0 ) {
		global $wpdb;

		if ( version_compare( \GFFormsModel::get_database_version(), '2.3-dev-1', '>=' ) ) {
			$entry_meta = \GFFormsModel::get_entry_meta_table_name();
			$sql        = "SELECT entry_id FROM $entry_meta";
		} else {
			$lead_meta = \GFFormsModel::get_lead_meta_table_name();
			$sql       = "SELECT lead_id FROM $lead_meta";
		}

		$sql = "$sql WHERE meta_key = 'gravityview_unique_id' AND";

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
	 * @param array $entry The entry array
	 *
	 * @return \GV\GF_Entry|null An instance of this entry or null if not found.
	 */
	public static function from_entry( $entry ) {
		if ( empty( $entry['id'] ) ) {
			return null;
		}

		$self        = new self();
		$self->entry = $entry;

		$self->ID   = $self->entry['id'];
		$self->slug = $self->get_slug();

		return $self;
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms entry array.
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 * @return bool Whether the offset exists or not.
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return isset( $this->entry[ $offset ] );
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
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->entry[ $offset ];
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
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		gravityview()->log->error( 'The underlying Gravity Forms entry is immutable. This is a \GV\Entry object and should not be accessed as an array.' );
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms entry array.
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		gravityview()->log->error( 'The underlying Gravity Forms entry is immutable. This is a \GV\Entry object and should not be accessed as an array.' );
	}
}
