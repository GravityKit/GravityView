<?php

/**
 * Stores each GravityView_Metabox_Tab instance
 * @link https://github.com/WebDevStudios/CMB2/blob/master/includes/CMB2_Boxes.php Thanks for the inspiration
 */
class GravityView_Metabox_Tabs {

	/**
	 * Array of all GravityView_Metabox_Tab objects
	 * @var   array
	 * @since 2.0.0
	 */
	protected static $meta_boxes = array();

	/**
	 * Add a tab
	 * @param GravityView_Metabox_Tab $meta_box
	 */
	public static function add( GravityView_Metabox_Tab $meta_box ) {
		self::$meta_boxes[ $meta_box->id ] = $meta_box;
	}

	/**
	 * Remove a tab by tab ID
	 *
	 * @param string $meta_box_id
	 */
	public static function remove( $meta_box_id ) {
		if ( array_key_exists( $meta_box_id, self::$meta_boxes ) ) {
			unset( self::$meta_boxes[ $meta_box_id ] );
		}
	}

	/**
	 * Get a tab by ID
	 *
	 * @param string $id
	 *
	 * @return bool|GravityView_Metabox_Tab False if none exist at the key $id; GravityView_Metabox_Tab if exists.
	 */
	public static function get( $id ) {
		if ( empty( self::$meta_boxes ) || empty( self::$meta_boxes[ $id ] ) ) {
			return false;
		}

		return self::$meta_boxes[ $id ];
	}

	/**
	 * Get array of all registered metaboxes
	 *
	 * @return array
	 */
	public static function get_all() {
		return self::$meta_boxes;
	}

}