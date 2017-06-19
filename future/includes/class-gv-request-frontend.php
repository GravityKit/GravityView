<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The default Request class.
 *
 * Finds out what Views are being requested.
 */
class Frontend_Request extends Request {
	/**
	 * Bootstrap.
	 *
	 * @return void
	 */
	public function __construct() {

		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_ALPHA_ENABLED' ) ) {
			// add_action( 'wp', array( $this, 'process' ), 10 );
			add_filter( 'the_content', array( $this, 'output' ), 11 );

			/** Remove old renderers */
			remove_filter( 'the_content', array( \GravityView_frontend::getInstance(), 'insert_view_in_content' ) );
			remove_action( 'gravityview_after', array( \GravityView_frontend::getInstance(), 'context_not_configured_warning' ) );
		}

		parent::__construct();
	}

	/**
	 * Render views for this request.
	 *
	 * Runs on `the_content` filter for frontend requests.
	 * Tries to detect the context in each case and filters the response output.
	 * Does not alter any global request state.
	 *
	 * @param string $content A potential piece of View content to be renderd.
	 *
	 * @return string $content Some potentially filtered output.
	 */
	public function output( $content ) {
		global $post;

		/** A list of views local to this context. */
		$views = new View_Collection();

		/** Oh, this one's easy, we have a post global to analyze. */
		if ( $post ) {
			$views->merge( View_Collection::from_post( $post ) );
		} else {
			/**
			 * What's going on here?
			 *
			 * Has someone called apply_filters( 'the_content', '...' ); manually?
			 * Sure, we can parse it for shortcodes and whatnot.
			 */
			$views->merge( View_Collection::from_content( $content ) );
		}

		/**
		 * @filter `gravityview/request/output/views` Views about to be rendered during request output.
		 * @param \GV\View_Collection $views View_Collection to be rendered during request output.
		 * @param \GV\Request $request The current request.
		 */
		$views = apply_filters( 'gravityview/request/output/views', $views, $this );

		/**
		 * We can render a gravityview single post directly here.
		 * Other Views in this context will be output by their respective handlers:
		 *  shortcodes, embed handlers, etc.
		 */
		if ( $this->is_view() ) {

			/**
			 * This View is password protected. Nothing to do here.
			 * WordPress outputs the form automagically inside `get_the_content`.
			 */
			if ( post_password_required( $post ) ) {
				return $content;
			}

			foreach ( $views->all() as $view ) {

				if ( ! $view->form  ) {
					/**
					 * This View has no data source. There's nothing to show really.
					 * ...apart from a nice message if the user can do anything about it.
					 */
					if ( \GVCommon::has_cap( array( 'edit_gravityviews', 'edit_gravityview' ), $view->ID ) ) {
						$content .= __( sprintf( 'This View is not configured properly. Start by <a href="%s">selecting a form</a>.', esc_url( get_edit_post_link( $view->ID, false ) ) ), 'gravityview' );
					}
					
					gravityview()->log->notice( 'View #{id} has no form attached to it.', array(
						'id' => $view->ID,
					) );

					continue;
				}

				/**
				 * Is this View directly accessible via a post URL?
				 *
				 * @see https://codex.wordpress.org/Function_Reference/register_post_type#public
				 */

				/**
				 * @filter `gravityview_direct_access` Should Views be directly accessible, or only visible using the shortcode?
				 * @deprecated
				 * @param[in,out] boolean `true`: allow Views to be accessible directly. `false`: Only allow Views to be embedded. Default: `true`
				 * @param int $view_id The ID of the View currently being requested. `0` for general setting
				 */
				$direct_access = apply_filters( 'gravityview_direct_access', true, $view->ID );

				/**
				 * @filter `gravityview/request/output/direct` Should this View be directly accessbile?
				 * @since future
				 * @param[in,out] boolean Accessible or not. Default: accessbile.
				 * @param \GV\View $view The View we're trying to directly render here.
				 * @param \GV\Request $request The current request.
				 */
				if ( ! apply_filters( 'gravityview/request/output/direct', $direct_access, $view, $this ) ) {
					$content .= __( 'You are not allowed to view this content.', 'gravityview' );
					continue;
				}

				/**
				 * Is this View an embed-only View? If so, don't allow rendering here,
				 *  as this is a direct request.
				 */
				if ( $view->settings->get( 'embed_only' ) && ! \GVCommon::has_cap( 'read_private_gravityviews' ) ) {
					$content .= __( 'You are not allowed to view this content.', 'gravityview' );
					continue;
				}

				/**
				 * Entry editing, huh?
				 */
				if ( $this->is_edit_entry() ) {
					if ( $entry = \GV\GF_Entry::by_id( get_query_var( \GV\Entry::get_endpoint_name() ) ) ) {

						$entries = new \GV\Entry_Collection();
						$entries->add( $entry );

						\GV\Mocks\Legacy_Context::push( array(
							'view' => $view,
							'entries' => $entries,
						) );

						do_action( 'gravityview_edit_entry', null );

						\GV\Mocks\Legacy_Context::pop();
					}

				/**
				 * Viewing a single entry.
				 */
				} else if ( $this->is_entry() ) {
					if ( $entry = \GV\GF_Entry::by_id( get_query_var( \GV\Entry::get_endpoint_name() ) ) ) {
						$renderer = new Entry_Renderer();
						$content .= $renderer->render( $entry, $view, $this );
					}

				/**
				 * Plain old View.
				 */
				} else {
					$renderer = new View_Renderer();
					$content .= $renderer->render( $view, $this );
				}
			}
		
		/**
		 * This is an embedded view of sorts. Shortcode?
		 *  oEmbed? Something else? What will it be?
		 */
		} else {
		}

		return $content;
	}

	/**
	 * Whether this request is for a single view or not.
	 *
	 * @api
	 * @since future
	 * @todo tests
	 *
	 * @return boolean Is a single view (directory).
	 */
	public function is_view() {
		global $post;
		return $post && get_post_type( $post ) == 'gravityview';
	}

	/**
	 * Check whether this is an entry request.
	 *
	 * @api
	 * @since future
	 * @todo tests
	 * @todo implementation
	 *
	 * @return boolean True if this is an entry request.
	 */
	public function is_entry() {
		return $this->is_view() && get_query_var( \GV\Entry::get_endpoint_name() );
	}

	/**
	 * Check whether this an edit entry request.
	 *
	 * @api
	 * @since future
	 * @todo tests
	 * @todo implementation
	 *
	 * @return boolean True if this is an edit entry request.
	 */
	public function is_edit_entry() {
		/**
		* @filter `gravityview_is_edit_entry` Whether we're currently on the Edit Entry screen \n
		* The Edit Entry functionality overrides this value.
		* @param boolean $is_edit_entry
		*/
		return $this->is_entry() && apply_filters( 'gravityview_is_edit_entry', false );
	}

	/**
	 * Check whether this an entry search request.
	 *
	 * @api
	 * @since future
	 * @todo tests
	 * @todo implementation
	 *
	 * @return boolean True if this is a search request.
	 */
	public function is_search() {
		return $this->is_view() && ! empty ( $_GET['gv_search'] );
	}

	/**
	 * Process any View actions.
	 *
	 * Called by the `wp` hook.
	 *
	 * @param \WP $wp The WordPress environment class. Unused.
	 *
	 * @internal
	 * @return void
	 */
	public function process( $wp = null ) {
		/** Nothing to do in an administrative context. */
		if ( $this->is_admin() ) {
			return;
		}

		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		
		/** The post might either be a gravityview, or contain gravityview shortcodes. */
		$views = View_Collection::from_post( $post );

		/** No views detected. */
		if ( ! $views->count() ) {
			return;
		}
	}
}
