<?php

/**
 * Stores each GravityView_Metabox instance
 * @link https://github.com/WebDevStudios/CMB2/blob/master/includes/CMB2_Boxes.php Thanks for the inspiration
 */
class GravityView_Metaboxes {

	/**
	 * Array of all GravityView_Metabox objects
	 * @var   array
	 * @since 2.0.0
	 */
	protected static $meta_boxes = array();

	public static function add( GravityView_Metabox $meta_box ) {
		self::$meta_boxes[ $meta_box->id ] = $meta_box;
	}

	public static function remove( $meta_box_id ) {
		if ( array_key_exists( $meta_box_id, self::$meta_boxes ) ) {
			unset( self::$meta_boxes[ $meta_box_id ] );
		}
	}

	public static function get( $id ) {
		if ( empty( self::$meta_boxes ) || empty( self::$meta_boxes[ $id ] ) ) {
			return false;
		}

		return self::$meta_boxes[ $id ];
	}

	public static function get_all() {
		return self::$meta_boxes;
	}

}