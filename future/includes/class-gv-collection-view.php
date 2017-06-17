<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A collection of \GV\View objects.
 */
class View_Collection extends Collection {
	/**
	 * Add a \GV\View to this collection.
	 *
	 * @param \GV\View $view The view to add to the internal array.
	 *
	 * @api
	 * @since future
	 * @return void
	 */
	public function add( $view ) {

		if ( ! $view instanceof View ) {
			gravityview()->log->error( 'View_Collections can only contain objects of type \GV\View.' );
			return;
		}

		parent::add( $view );
	}

	/**
	 * Get a \GV\View from this list.
	 *
	 * @param int $view_id The ID of the view to get.
	 *
	 * @api
	 * @since future
	 *
	 * @return \GV\View|null The \GV\View with the $view_id as the ID, or null if not found.
	 */
	public function get( $view_id ) {
		foreach ( $this->all() as $view ) {
			if ( $view->ID == $view_id ) {
				return $view;
			}
		}
		return null;
	}

	/**
	 * Check whether \GV\View with an ID is already here.
	 *
	 * @param int $view_id The ID of the view to check.
	 *
	 * @api
	 * @since future
	 *
	 * @return boolean Whether it exists or not.
	 */
	public function contains( $view_id ) {
		return ! is_null( $this->get( $view_id ) );
	}

	/**
	 * Get a list of \GV\View objects inside the supplied \WP_Post.
	 *
	 * The post can be a gravityview post, which is the simplest case.
	 * The post can contain gravityview shortcodes as well.
	 * The post meta can contain gravityview shortcodes.
	 *
	 * @param \WP_Post $post The \WP_Post object to look into.
	 *
	 * @api
	 * @since future
	 * @return \GV\View_Collection A \GV\View_Collection instance contanining the views inside the supplied \WP_Post.
	 */
	public static function from_post( \WP_Post $post ) {
		$views = new self();

		if ( get_post_type( $post ) == 'gravityview' ) {
			/** A straight up gravityview post. */
			$views->add( View::from_post( $post ) );
		} else {
			$views->merge( self::from_content( $post->post_content ) );

			/**
			 * @filter `gravityview/view_collection/from_post/meta_keys` Define meta keys to parse to check for GravityView shortcode content.
			 *
			 * This is useful when using themes that store content that may contain shortcodes in custom post meta.
			 *
			 * @since future
			 *
			 * @param[in,out] array $meta_keys Array of key values to check. If empty, do not check. Default: empty array
			 * @param[in] \WP_Post $post The post that is being checked
			 */
			$meta_keys = apply_filters( 'gravityview/view_collection/from_post/meta_keys', array(), $post );

			/**
			 * @filter `gravityview/data/parse/meta_keys`
			 * @deprecated
			 * @see The `gravityview/view_collection/from_post/meta_keys` filter.
			 */
			$meta_keys = (array)apply_filters( 'gravityview/data/parse/meta_keys', $meta_keys, $post->ID );

			/** What about inside post meta values? */
			foreach ( $meta_keys as $meta_key ) {
				if ( is_string( $post->$meta_key ) ) {
					$views->merge( self::from_content( $post->$meta_key ) );
				}
			}
		}

		return $views;
	}

	/**
	 * Get a list of detected \GV\View objects inside the supplied content.
	 *
	 * The content can have a shortcode, this is the simplest case.
	 *
	 * @param string $content The content to look into.
	 *
	 * @api
	 * @since future
	 * @return \GV\View_Collection A \GV\View_Collection instance containing the views inside the supplied \WP_Post.
	 */
	public static function from_content( $content ) {
		$views = new self();

		/** Let's find us some [gravityview] shortcodes perhaps. */
		foreach ( Shortcode::parse( $content ) as $shortcode ) {
			if ( $shortcode->name != 'gravityview' || empty( $shortcode->atts['id'] ) ) {
				continue;
			}

			if ( is_numeric( $shortcode->atts['id'] ) ) {
				$view = View::by_id( $shortcode->atts['id'] );
				if ( ! $view ) {
					continue;
				}
				
				$view->settings->update( $shortcode->atts );
				$views->add( $view );
			}
		}

		return $views;
	}
}
