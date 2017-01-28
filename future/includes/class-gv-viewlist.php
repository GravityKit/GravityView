<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) )
	die();

/**
 * A collection of \GV\View objects.
 */
class ViewList extends DefaultList {
	/**
	 * Add a \GV\View to this collection.
	 *
	 * @param \GV\View $view The view to append to the internal array.
	 *
	 * @throws \InvalidArgumentException if $view is not of type \GV\View.
	 *
	 * @api
	 * @since future
	 * @return void
	 */
	public function append( $view ) {
		if ( ! $view instanceof View ) {
			throw new \InvalidArgumentException( __( 'ViewLists can only contain objects of type \GV\View.', 'gravityview' ) );
		}
		parent::append( $view );
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
			if ( $view->ID == $view_id )
				return $view;
		}
		return null;
	}

	/**
	 * Get a list of \GV\View objects inside the supplied \WP_Post.
	 *
	 * The post can be a gravityview post, which is the simplest case.
	 * The post can contain gravityview shortcodes as well.
	 *
	 * @param \WP_Post $post The \WP_Post object to look into.
	 *
	 * @api
	 * @since future
	 * @return \GV\ViewList A \GV\ViewList instance contanining the views inside the supplied \WP_Post.
	 */
	public static function from_post( \WP_Post $post ) {
		$views = new self();

		/** A straight up gravityview post. */
		if ( get_post_type( $post ) == 'gravityview' ) {
			$views->append( View::from_post( $post ) );
		} else {
			/** Let's find us some [gravityview] shortcodes perhaps. */
			foreach ( Shortcode::parse( $post->post_content ) as $shortcode ) {
				if ( ! $shortcode instanceof Shortcodes\gravityview ) {
					continue;
				}

				if ( is_numeric( $shortcode->atts['id'] ) ) {
					$views->append( View::by_id( $shortcode->atts['id'] ) );
				}
			}
		}

		return $views;
	}
}
