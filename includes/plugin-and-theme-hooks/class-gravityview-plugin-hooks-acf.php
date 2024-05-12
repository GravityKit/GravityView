<?php
/**
 * Add Advanced Custom Fields customizations
 *
 * @file      class-gravityview-plugin-hooks-acf.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
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
	 * @since 1.22.2.1
	 */
	protected $class_name = 'acf';

	/**
	 * @since 2.2
	 * @var array
	 */
	protected $style_handles = array( 'acf-global' );

	/**
	 * Microcache for keys by post id.
	 *
	 * @since $ver$
	 *
	 * @var array{int, mixed}
	 */
	private $keys = [];

	/**
	 * @since 1.16.5
	 */
	protected function add_hooks() {
		parent::add_hooks();

		add_filter( 'gravityview/view_collection/from_post/meta_keys', array( $this, 'add_meta_keys_from_post' ), 10, 2 );

		$this->fix_posted_fields();
	}

	/**
	 * Retrieve the "Advanced Custom Field" field keys for the post.
	 *
	 * @since $ver$
	 *
	 * @param int $post_id The post id.
	 *
	 * @return array The ACF field keys.
	 */
	private function get_acf_keys( int $post_id ): array {
		// Can never be too careful: double-check that ACF is active and the functions exist.
		if ( ! function_exists( 'acf_get_meta' ) || ! function_exists( 'acf_get_valid_post_id' ) || ! $post_id ) {
			return [];
		}

		if ( isset( $this->keys[ $post_id ] ) ) {
			return $this->keys[ $post_id ];
		}

		$post_id = acf_get_valid_post_id( $post_id );
		$meta    = acf_get_meta( $post_id );

		/**
		 * Filter non ACF keys. {@see get_field_objects}.
		 * We use this instead of `get_field_objects` to prevent circular reference and save memory.
		 */
		$this->keys[ $post_id ] = array_filter(
			array_keys( $meta ),
			static function ( string $key ) use ( $meta ) {
				return isset( $meta[ '_' . $key ] );
			}
		);

		return $this->keys[ $post_id ];
	}

	/**
	 * @param array    $meta_keys Existing meta keys to parse for [gravityview] shortcode
	 * @param \WP_Post $post Current post ID
	 *
	 * @return array
	 */
	public function add_meta_keys_from_post( $meta_keys = array(), $post = null ) {
		$acf_keys = $this->get_acf_keys( (int) $post->ID );

		if ( $acf_keys ) {
			return array_merge( $acf_keys, $meta_keys );
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
		if ( is_admin() && isset( $_POST['action'] ) && isset( $_POST['post_type'] ) ) {
			if ( 'editpost' === $_POST['action'] && 'gravityview' === $_POST['post_type'] ) {
				$_POST['fields'] = _gravityview_process_posted_fields();
			}
		}
	}
}

new GravityView_Plugin_Hooks_ACF();
