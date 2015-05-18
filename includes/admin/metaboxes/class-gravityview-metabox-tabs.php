<?php

/**
 * Stores each GravityView_Metabox_Tab instance
 * @since 1.8
 * @see https://gist.github.com/zackkatz/6cc381bcf54849f2ed41 For example of adding a metabox
 * @see https://github.com/WebDevStudios/CMB2/blob/master/includes/CMB2_Boxes.php Thanks for the inspiration
 */
class GravityView_Metabox_Tabs {

	/**
	 * Array of all GravityView_Metabox_Tab objects
	 * @var   array
	 * @since 1.8
	 */
	protected static $meta_boxes = array();

	/**
	 * Add a tab
	 *
	 * @since 1.8
	 *
	 * @param GravityView_Metabox_Tab $meta_box
	 *
	 * @return void
	 */
	public static function add( GravityView_Metabox_Tab $meta_box ) {
		self::$meta_boxes[ $meta_box->id ] = $meta_box;
	}

	/**
	 * Remove a tab by tab ID
	 *
	 * @since 1.8
	 *
	 * @param string $meta_box_id
	 *
	 * @return void
	 */
	public static function remove( $meta_box_id ) {
		if ( array_key_exists( $meta_box_id, self::$meta_boxes ) ) {
			unset( self::$meta_boxes[ $meta_box_id ] );
		}
	}

	/**
	 * Get a tab by ID
	 *
	 * @since 1.8
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
	 * @return array|GravityView_Metabox_Tab[] Empty array if no tabs, otherwise array of `GravityView_Metabox_Tab`s
	 */
	public static function get_all() {
		return self::$meta_boxes;
	}

}