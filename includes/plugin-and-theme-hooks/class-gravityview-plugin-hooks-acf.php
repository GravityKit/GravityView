<?php
/**
 * Add Advanced Custom Fields customizations
 *
 * @file      class-gravityview-plugin-hooks-acf.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.16.5
 */

/**
 * @inheritDoc
 * @since 1.16.5
 */
class GravityView_Plugin_Hooks_ACF extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.16.5
	 */
	protected $function_name = 'acf';

	/**
	 * @since 1.16.5
	 */
	protected function add_hooks() {
		parent::add_hooks();

		add_filter( 'gravityview/data/parse/meta_keys', array( $this, 'add_meta_keys_from_post' ), 10, 2 );

		$this->fix_posted_fields();
	}

	/**
	 * @param array $meta_keys Existing meta keys to parse for [gravityview] shortcode
	 * @param int $post_id Current post ID
	 *
	 * @return array
	 */
	function add_meta_keys_from_post( $meta_keys = array(), $post_id = 0 ) {

		// Can never be too careful
		if ( ! function_exists( 'get_field_objects' ) ) {
			return $meta_keys;
		}

		if( $acf_keys = get_field_objects( $post_id, array( 'load_value' => false ) ) ) {
			return array_merge( array_keys( $acf_keys ), $meta_keys );
		}

		return $meta_keys;
	}

	/**
	 * ACF needs $_POST['fields'] to be an array. GV supports both serialized array and array, so we just process earlier.
	 *
	 * @since 1.16.5
	 *
	 * @return void
	 */
	private function fix_posted_fields() {
		if( is_admin() && isset( $_POST['action'] ) && isset( $_POST['post_type'] ) ) {
			if( 'editpost' === $_POST['action'] && 'gravityview' === $_POST['post_type'] ) {
				$_POST['fields'] = _gravityview_process_posted_fields();
			}
		}
	}
}

new GravityView_Plugin_Hooks_ACF;