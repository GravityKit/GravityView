<?php
namespace GV\Wrappers;

/**
 * This file contains magic wrapper code for `gravityview()`.
 *
 * Every `gravityview()` magic key maps to a class that exposes more magic.
 *  Chains of infinite magic can be constructed spanning seas of mermaids,
 *  valleys of unicorns and whirlwinds of shooting stars.
 */

/**
 * The views magic wrapper.
 */
class views {

	/**
	 * @var \GV\View An internal View keeper.
	 */
	private $view = null;

	/**
	 * Gets a View.
	 *
	 * Doesn't care what you provide it. Will try to find
	 *  out what you need from the current context, from the supplied
	 *  args, etc.
	 *
	 * @param string|int|array|\GV\View|\WP_Post|null Anything goes.
	 *
	 * @return \GV\View|null The detected View.
	 */
	public function get( $view = null ) {

		/**
		 * By View.
		 */
		if ( $view instanceof \GV\View && $view->ID ) {
			return $this->get( $view->ID );
		}

		/** 
		 * By View ID.
		 */
		if ( is_numeric( $view ) ) {
			return \GV\View::by_id( $view );
		}

		/** 
		 * By post object.
		 */
		if ( $view instanceof \WP_Post ) {
			return \GV\View::from_post( $view );
		}

		/**
		 * By array.
		 */
		if ( is_array( $view ) && ! empty( $view['id'] ) ) {
			return $this->get( $view['id'] );
		}

		/**
		 * From various contexts.
		 */
		if ( is_null( $view ) ) {
			if ( in_array( get_class( gravityview()->request ), array( 'GV\Frontend_Request', 'GV\Mock_Request' ) ) && $view = gravityview()->request->is_view() ) {
				return $view;
			}

			global $post;

			if ( $post instanceof \WP_Post && $post->post_type == 'gravityview' ) {
				return $this->get( $post );
			}

			/**
			 * Final fallback.
			 */
			return $this->view;
		}

		return null;
	}

	/**
	 * Mock the internal pointer.
	 *
	 * @param \GV\View $view The View to supply on fallback in ::get()
	 *
	 * @return void
	 */
	public function set( $view ) {
		$this->view = $view;
	}
}
