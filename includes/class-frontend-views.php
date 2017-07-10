<?php
/**
 * GravityView Frontend functions
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */


class GravityView_frontend {

	/**
	 * Regex strings that are used to determine whether the current request is a GravityView search or not.
	 * @see GravityView_frontend::is_searching()
	 * @since 1.7.4.1
	 * @var array
	 */
	private static $search_parameters = array( 'gv_search', 'gv_start', 'gv_end', 'gv_id', 'gv_by', 'filter_*' );

	/**
	 * Is the currently viewed post a `gravityview` post type?
	 * @var boolean
	 */
	var $is_gravityview_post_type = false;

	/**
	 * Does the current post have a `[gravityview]` shortcode?
	 * @var boolean
	 */
	var $post_has_shortcode = false;

	/**
	 * The Post ID of the currently viewed post. Not necessarily GV
	 * @var int
	 */
	var $post_id = null;

	/**
	 * Are we currently viewing a single entry?
	 * If so, the int value of the entry ID. Otherwise, false.
	 * @var int|boolean
	 */
	var $single_entry = false;

	/**
	 * If we are viewing a single entry, the entry data
	 * @var array|false
	 */
	var $entry = false;

	/**
	 * When displaying the single entry we should always know to which View it belongs (the context is everything!)
	 * @var null
	 */
	var $context_view_id = null;

	/**
	 * The View is showing search results
	 * @since 1.5.4
	 * @var boolean
	 */
	var $is_search = false;

	/**
	 * The view data parsed from the $post
	 *
	 * @see  GravityView_View_Data::__construct()
	 * @var GravityView_View_Data
	 */
	var $gv_output_data = null;

	/**
	 * @var GravityView_frontend
	 */
	static $instance;

	/**
	 * Class constructor, enforce Singleton pattern
	 */
	private function __construct() {}

	private function initialize() {
		add_action( 'wp', array( $this, 'parse_content'), 11 );
		add_filter( 'parse_query', array( $this, 'parse_query_fix_frontpage' ), 10 );
		add_action( 'template_redirect', array( $this, 'set_entry_data'), 1 );

		// Enqueue scripts and styles after GravityView_Template::register_styles()
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_and_styles' ), 20 );

		// Enqueue and print styles in the footer. Added 1 priorty so stuff gets printed at 10 priority.
		add_action( 'wp_print_footer_scripts', array( $this, 'add_scripts_and_styles' ), 1 );

		add_filter( 'the_title', array( $this, 'single_entry_title' ), 1, 2 );
		add_filter( 'the_content', array( $this, 'insert_view_in_content' ) );
		add_filter( 'comments_open', array( $this, 'comments_open' ), 10, 2 );

		add_action( 'gravityview_after', array( $this, 'context_not_configured_warning' ) );
	}

	/**
	 * Get the one true instantiated self
	 * @return GravityView_frontend
	 */
	public static function getInstance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self;
			self::$instance->initialize();
		}

		return self::$instance;
	}

	/**
	 * @return GravityView_View_Data
	 */
	public function getGvOutputData() {
		return $this->gv_output_data;
	}

	/**
	 * @param GravityView_View_Data $gv_output_data
	 */
	public function setGvOutputData( $gv_output_data ) {
		$this->gv_output_data = $gv_output_data;
	}

	/**
	 * @return boolean
	 */
	public function isSearch() {
		return $this->is_search;
	}

	/**
	 * @param boolean $is_search
	 */
	public function setIsSearch( $is_search ) {
		$this->is_search = $is_search;
	}

	/**
	 * @return bool|int
	 */
	public function getSingleEntry() {
		return $this->single_entry;
	}

	/**
	 * Sets the single entry ID and also the entry
	 * @param bool|int|string $single_entry
	 */
	public function setSingleEntry( $single_entry ) {

		$this->single_entry = $single_entry;

	}

	/**
	 * @return array
	 */
	public function getEntry() {
		return $this->entry;
	}

	/**
	 * Set the current entry
	 * @param array|int $entry Entry array or entry slug or ID
	 */
	public function setEntry( $entry ) {

		if ( ! is_array( $entry ) ) {
			$entry = GVCommon::get_entry( $entry );
		}

		$this->entry = $entry;
	}

	/**
	 * @return int
	 */
	public function getPostId() {
		return $this->post_id;
	}

	/**
	 * @param int $post_id
	 */
	public function setPostId( $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * @return boolean
	 */
	public function isPostHasShortcode() {
		return $this->post_has_shortcode;
	}

	/**
	 * @param boolean $post_has_shortcode
	 */
	public function setPostHasShortcode( $post_has_shortcode ) {
		$this->post_has_shortcode = $post_has_shortcode;
	}

	/**
	 * @return boolean
	 */
	public function isGravityviewPostType() {
		return $this->is_gravityview_post_type;
	}

	/**
	 * @param boolean $is_gravityview_post_type
	 */
	public function setIsGravityviewPostType( $is_gravityview_post_type ) {
		$this->is_gravityview_post_type = $is_gravityview_post_type;
	}

	/**
	 * Set the context view ID used when page contains multiple embedded views or displaying the single entry view
	 *
	 *
	 *
	 * @param null $view_id
	 */
	public function set_context_view_id( $view_id = null ) {
		$multiple_views = defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ? gravityview()->views->count() > 1 : ( $this->getGvOutputData() && $this->getGvOutputData()->has_multiple_views() );

		if ( ! empty( $view_id ) ) {

			$this->context_view_id = $view_id;

		} elseif ( isset( $_GET['gvid'] ) && $multiple_views ) {
			/**
			 * used on a has_multiple_views context
			 * @see GravityView_API::entry_link
			 */
			$this->context_view_id = $_GET['gvid'];

		} elseif ( ! $multiple_views ) {
			if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
				$view = gravityview()->views->last();
				$this->context_view_id = $view ? $view->ID : null;
			} else {
				/** GravityView_View_Data::get_views is deprecated. */
				$array_keys = array_keys( $this->getGvOutputData()->get_views() );
				$this->context_view_id = array_pop( $array_keys );
				unset( $array_keys );
			}
		}

	}

	/**
	 * Returns the the view_id context when page contains multiple embedded views or displaying single entry view
	 *
	 * @since 1.5.4
	 *
	 * @return string
	 */
	public function get_context_view_id() {
		return $this->context_view_id;
	}

	/**
	 * Allow GravityView entry endpoints on the front page of a site
	 *
	 * @link  https://core.trac.wordpress.org/ticket/23867 Fixes this core issue
	 * @link https://wordpress.org/plugins/cpt-on-front-page/ Code is based on this
	 *
	 * @since 1.17.3
	 *
	 * @param WP_Query &$query (passed by reference)
	 *
	 * @return void
	 */
	public function parse_query_fix_frontpage( &$query ) {
		global $wp_rewrite;

		$is_front_page = ( $query->is_home || $query->is_page );
		$show_on_front = ( 'page' === get_option('show_on_front') );
		$front_page_id = get_option('page_on_front');

		if (  $is_front_page && $show_on_front && $front_page_id ) {

			// Force to be an array, potentially a query string ( entry=16 )
			$_query = wp_parse_args( $query->query );

			// pagename can be set and empty depending on matched rewrite rules. Ignore an empty pagename.
			if ( isset( $_query['pagename'] ) && '' === $_query['pagename'] ) {
				unset( $_query['pagename'] );
			}

			// this is where will break from core wordpress
			/** @internal Don't use this filter; it will be unnecessary soon - it's just a patch for specific use case */
			$ignore = apply_filters( 'gravityview/internal/ignored_endpoints', array( 'preview', 'page', 'paged', 'cpage' ), $query );
			$endpoints = rgobj( $wp_rewrite, 'endpoints' );
			foreach ( (array) $endpoints as $endpoint ) {
				$ignore[] = $endpoint[1];
			}
			unset( $endpoints );

			// Modify the query if:
			// - We're on the "Page on front" page (which we are), and:
			// - The query is empty OR
			// - The query includes keys that are associated with registered endpoints. `entry`, for example.
			if ( empty( $_query ) || ! array_diff( array_keys( $_query ), $ignore ) ) {

				$qv =& $query->query_vars;

				// Prevent redirect when on the single entry endpoint
				if( self::is_single_entry() ) {
					add_filter( 'redirect_canonical', '__return_false' );
				}

				$query->is_page = true;
				$query->is_home = false;
				$qv['page_id']  = $front_page_id;

				// Correct <!--nextpage--> for page_on_front
				if ( ! empty( $qv['paged'] ) ) {
					$qv['page'] = $qv['paged'];
					unset( $qv['paged'] );
				}
			}

			// reset the is_singular flag after our updated code above
			$query->is_singular = $query->is_single || $query->is_page || $query->is_attachment;
		}
	}

	/**
	 * Read the $post and process the View data inside
	 * @param  array  $wp Passed in the `wp` hook. Not used.
	 * @return void
	 */
	public function parse_content( $wp = array() ) {
		global $post;

		// If in admin and NOT AJAX request, get outta here.
		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) && gravityview()->request->is_admin() ) {
			return;
			/** Deprecated in favor of gravityview()->request->is_admin(). */
		} else if ( GravityView_Plugin::is_admin() ) {
			return;
		}

		// Calculate requested Views
		$this->setGvOutputData( GravityView_View_Data::getInstance( $post ) );

		// !important: we need to run this before getting single entry (to kick the advanced filter)
		$this->set_context_view_id();

		$this->setIsGravityviewPostType( get_post_type( $post ) === 'gravityview' );

		$post_id = $this->getPostId() ? $this->getPostId() : (isset( $post ) ? $post->ID : null );
		$this->setPostId( $post_id );
		$post_has_shortcode = ! empty( $post->post_content ) ? gravityview_has_shortcode_r( $post->post_content, 'gravityview' ) : false;
		$this->setPostHasShortcode( $this->isGravityviewPostType() ? null : ! empty( $post_has_shortcode ) );

		// check if the View is showing search results (only for multiple entries View)
		$this->setIsSearch( $this->is_searching() );

		unset( $entry, $post_id, $post_has_shortcode );
	}

	/**
	 * Set the entry
	 */
	function set_entry_data() {
		$entry_id = self::is_single_entry();
		$this->setSingleEntry( $entry_id );
		$this->setEntry( $entry_id );
	}

	/**
	 * Checks if the current View is presenting search results
	 *
	 * @since 1.5.4
	 *
	 * @return boolean True: Yes, it's a search; False: No, not a search.
	 */
	function is_searching() {

		// It's a single entry, not search
		if ( $this->getSingleEntry() ) {
			return false;
		}

		$search_method = GravityView_Widget_Search::getInstance()->get_search_method();

		if( 'post' === $search_method ) {
			$get = $_POST;
		} else {
			$get = $_GET;
		}

		// No $_GET parameters
		if ( empty( $get ) || ! is_array( $get ) ) {
			return false;
		}

		// Remove empty values
		$get = array_filter( $get );

		// If the $_GET parameters are empty, it's no search.
		if ( empty( $get ) ) {
			return false;
		}

		$search_keys = array_keys( $get );

		$search_match = implode( '|', self::$search_parameters );

		foreach ( $search_keys as $search_key ) {

			// Analyze the search key $_GET parameter and see if it matches known GV args
			if ( preg_match( '/(' . $search_match . ')/i', $search_key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Filter the title for the single entry view
	 *
	 * @param  string $title   current title
	 * @param  int $passed_post_id Post ID
	 * @return string          (modified) title
	 */
	public function single_entry_title( $title, $passed_post_id = null ) {
		global $post;

		// If this is the directory view, return.
		if ( ! $this->getSingleEntry() ) {
			return $title;
		}

		$entry = $this->getEntry();

		/**
		 * @filter `gravityview/single/title/out_loop` Apply the Single Entry Title filter outside the WordPress loop?
		 * @param boolean $in_the_loop Whether to apply the filter to the menu title and the meta tag <title> - outside the loop
		 * @param array $entry Current entry
		 */
		$apply_outside_loop = apply_filters( 'gravityview/single/title/out_loop' , in_the_loop(), $entry );

		if ( ! $apply_outside_loop ) {
			return $title;
		}

		// User reported WooCommerce doesn't pass two args.
		if ( empty( $passed_post_id ) )  {
			return $title;
		}

		// Don't modify the title for anything other than the current view/post.
		// This is true for embedded shortcodes and Views.
		if ( is_object( $post ) && (int) $post->ID !== (int) $passed_post_id ) {
			return $title;
		}

		$context_view_id = $this->get_context_view_id();

		$multiple_views = defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ? gravityview()->views->count() > 1 : $this->getGvOutputData()->has_multiple_views();

		if ( $multiple_views && ! empty( $context_view_id ) ) {
			if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
				$view = gravityview()->views->get( $context_view_id );
				if ( ! $view ) {
					/** Emulate the weird behavior of \GravityView_View_Data::get_view adding a view which wasn't there to begin with. */
					gravityview()->views->add( \GV\View::by_id( $context_view_id ) );
					$view = gravityview()->views->get( $context_view_id );
				}
			} else {
				/** Deprecated. Use gravityview()->views->get() or gravityview()->request->get() */
				$view_meta = $this->getGvOutputData()->get_view( $context_view_id );
			}
		} else {
			if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
				foreach ( gravityview()->views->all() as $_view ) {
					if ( intval( $_view->form->ID ) === intval( $entry['form_id'] ) ) {
						$view = $_view;
						break;
					}
				}

				/** No matching form sources were found, happens when requesting an entry from a different form . */
				if ( ! isset( $view ) )
					return $title;
			} else {
				/** Deprecated. Use gravityview()->views->all() or gravityview()->request->all() */
				foreach ( $this->getGvOutputData()->get_views() as $view_id => $view_data ) {
					if ( intval( $view_data['form_id'] ) === intval( $entry['form_id'] ) ) {
						$view_meta = $view_data;
						break;
					}
				}
			}
		}

		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			if ( $title = $view->settings->get( 'single_title' ) ) {
				$title = GravityView_API::replace_variables( $title, $view->form->form, $entry );
				$title = do_shortcode( $title );
			}
		} else {
			/** Deprecated stuff in the future. See the branch above. */
			if ( ! empty( $view_meta['atts']['single_title'] ) ) {

				$title = $view_meta['atts']['single_title'];

				// We are allowing HTML in the fields, so no escaping the output
				$title = GravityView_API::replace_variables( $title, $view_meta['form'], $entry );

				$title = do_shortcode( $title );
			}
		}


		return $title;
	}


	/**
	 * In case View post is called directly, insert the view in the post content
	 *
	 * @access public
	 * @static
	 * @param mixed $content
	 * @return string Add the View output into View CPT content
	 */
	public function insert_view_in_content( $content ) {

		// Plugins may run through the content in the header. WP SEO does this for its OpenGraph functionality.
		if ( ! did_action( 'loop_start' ) ) {

			do_action( 'gravityview_log_debug', '[insert_view_in_content] Not processing yet: loop_start hasn\'t run yet. Current action:', current_filter() );

			return $content;
		}

		//	We don't want this filter to run infinite loop on any post content fields
		remove_filter( 'the_content', array( $this, 'insert_view_in_content' ) );

		// Otherwise, this is called on the Views page when in Excerpt mode.
		if ( is_admin() ) {
			return $content;
		}

		// Only render in the loop. Fixes issues with the_content filter being applied in places like the sidebar
		if( ! in_the_loop() ) {
			return $content;
		}

		if ( $this->isGravityviewPostType() ) {

			/** @since 1.7.4 */
			if ( is_preview() && ! gravityview_get_form_id( $this->post_id ) ) {
				$content .= __( 'When using a preset template, you must save the View before a Preview is available.', 'gravityview' );
			} else {
				if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
					foreach ( gravityview()->views->all() as $view ) {
						$content .= $this->render_view( array( 'id' => $view->ID ) );
					}
				} else {
					/** The \GravityView_View_Data::get_views method is depreacted. */
					foreach ( $this->getGvOutputData()->get_views() as $view_id => $data ) {
						$content .= $this->render_view( array( 'id' => $view_id ) );
					}
				}
			}
		}

		//	Add the filter back in
		add_filter( 'the_content', array( $this, 'insert_view_in_content' ) );

		return $content;
	}

	/**
	 * Disable comments on GravityView post types
	 * @param  boolean $open    existing status
	 * @param  int $post_id Post ID
	 * @return boolean
	 */
	public function comments_open( $open, $post_id ) {

		if ( $this->isGravityviewPostType() ) {
			$open = false;
		}

		/**
		 * @filter `gravityview/comments_open` Whether to set comments to open or closed.
		 * @since  1.5.4
		 * @param  boolean $open Open or closed status
		 * @param  int $post_id Post ID to set comment status for
		 */
		$open = apply_filters( 'gravityview/comments_open', $open, $post_id );

		return $open;
	}

	/**
	 * Display a warning when a View has not been configured
	 *
	 * @since 1.19.2
	 *
	 * @param int $view_id The ID of the View currently being displayed
	 *
	 * @return void
	 */
	public function context_not_configured_warning( $view_id = 0 ) {

		if ( ! class_exists( 'GravityView_View' ) ) {
			return;
		}

		$fields = GravityView_View::getInstance()->getContextFields();

		if ( ! empty( $fields ) ) {
			return;
		}

		$context = GravityView_View::getInstance()->getContext();

		switch( $context ) {
			case 'directory':
				$tab = __( 'Multiple Entries', 'gravityview' );
				break;
			case 'edit':
				$tab = __( 'Edit Entry', 'gravityview' );
				break;
			case 'single':
			default:
				$tab = __( 'Single Entry', 'gravityview' );
				break;
		}


		$title = sprintf( esc_html_x('The %s layout has not been configured.', 'Displayed when a View is not configured. %s is replaced by the tab label', 'gravityview' ), $tab );
		$edit_link = admin_url( sprintf( 'post.php?post=%d&action=edit#%s-view', $view_id, $context ) );
		$action_text = sprintf( esc_html__('Add fields to %s', 'gravityview' ), $tab );
		$message = esc_html__( 'You can only see this message because you are able to edit this View.', 'gravityview' );

		$image =  sprintf( '<img alt="%s" src="%s" style="margin-top: 10px;" />', $tab, esc_url(plugins_url( sprintf( 'assets/images/tab-%s.png', $context ), GRAVITYVIEW_FILE ) ) );
		$output = sprintf( '<h3>%s <strong><a href="%s">%s</a></strong></h3><p>%s</p>', $title, esc_url( $edit_link ), $action_text, $message );

		echo GVCommon::generate_notice( $output . $image, 'gv-error error', 'edit_gravityview', $view_id );
	}


	/**
	 * Core function to render a View based on a set of arguments
	 *
	 * @access public
	 * @static
	 * @param array $passed_args {
	 *
	 *      Settings for rendering the View
	 *
	 *      @type int $id View id
	 *      @type int $page_size Number of entries to show per page
	 *      @type string $sort_field Form field id to sort
	 *      @type string $sort_direction Sorting direction ('ASC' or 'DESC')
	 *      @type string $start_date - Ymd
	 *      @type string $end_date - Ymd
	 *      @type string $class - assign a html class to the view
	 *      @type string $offset (optional) - This is the start point in the current data set (0 index based).
	 * }
	 *
	 * @return string|null HTML output of a View, NULL if View isn't found
	 */
	public function render_view( $passed_args ) {

		// validate attributes
		if ( empty( $passed_args['id'] ) ) {
			do_action( 'gravityview_log_error', '[render_view] Returning; no ID defined.', $passed_args );
			return null;
		}

		// Solve problem when loading content via admin-ajax.php
		// @hack
		if ( ! $this->getGvOutputData() ) {

			do_action( 'gravityview_log_error', '[render_view] gv_output_data not defined; parsing content.', $passed_args );

			$this->parse_content();
		}

		// Make 100% sure that we're dealing with a properly called situation
		if ( ! is_object( $this->getGvOutputData() ) || ! is_callable( array( $this->getGvOutputData(), 'get_view' ) ) ) {

			do_action( 'gravityview_log_error', '[render_view] gv_output_data not an object or get_view not callable.', $this->getGvOutputData() );

			return null;
		}

		$view_id = $passed_args['id'];

		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			$view = gravityview()->views->get( $view_id );
			if ( ! $view ) {
				/** Emulate the weird behavior of \GravityView_View_Data::get_view adding a view which wasn't there to begin with. */
				gravityview()->views->add( \GV\View::by_id( $view_id ) );
				$view = gravityview()->views->get( $view_id );

				if ( ! $view ) {
					do_action( 'gravityview_log_debug', sprintf( 'GravityView_frontend[render_view] Returning; View #%s does not have a form tied to it.', $view_id ) );
					return null;
				}

			}

			/** Update the view settings with the requested arguments. */
			$view->settings->update( $passed_args );

			/** Form is not valid. */
			if ( ! $view->form ) {
				do_action( 'gravityview_log_debug', sprintf( 'GravityView_frontend[render_view] Returning; View #%s does not exist.', $view_id ) );
				return null;
			}
		} else {
			/** \GravityView_View_Data::get_view is deprecated. */
			$view_data = $this->getGvOutputData()->get_view( $view_id, $passed_args );

			do_action( 'gravityview_log_debug', '[render_view] View Data: ', $view_data );

			do_action( 'gravityview_log_debug', '[render_view] Init View. Arguments: ', $passed_args );

			// The passed args were always winning, even if they were NULL.
			// This prevents that. Filters NULL, FALSE, and empty strings.
			$passed_args = array_filter( $passed_args, 'strlen' );

			//Override shortcode args over View template settings
			$atts = wp_parse_args( $passed_args, $view_data['atts'] );

			do_action( 'gravityview_log_debug', '[render_view] Arguments after merging with View settings: ', $atts );
		}

		// It's password protected and you need to log in.
		if ( post_password_required( $view_id ) ) {

			do_action( 'gravityview_log_error', sprintf( '[render_view] Returning: View %d is password protected.', $view_id ) );

			// If we're in an embed or on an archive page, show the password form
			if ( get_the_ID() !== $view_id ) {
				return get_the_password_form();
			}

			// Otherwise, just get outta here
			return null;
		}

		/**
		 * Don't render View if user isn't allowed to see it
		 * @since 1.15
		 * @since 1.17.2 Added check for if a user has no caps but is logged in (member of multisite, but not any site). Treat as if logged-out.
		 */
		if( is_user_logged_in() && ! ( empty( wp_get_current_user()->caps ) && empty( wp_get_current_user()->roles ) ) && false === GVCommon::has_cap( 'read_gravityview', $view_id ) ) {

			do_action( 'gravityview_log_debug', sprintf( '%s Returning: View %d is not visible by current user.', __METHOD__, $view_id ) );

			return null;
		}

		if( $this->isGravityviewPostType() ) {

			/**
			 * @filter `gravityview_direct_access` Should Views be directly accessible, or only visible using the shortcode?
			 * @see https://codex.wordpress.org/Function_Reference/register_post_type#public
			 * @see \GV\Entry::get_endpoint_name
			 * @since 1.15.2
			 * @param[in,out] boolean `true`: allow Views to be accessible directly. `false`: Only allow Views to be embedded via shortcode. Default: `true`
			 * @param int $view_id The ID of the View currently being requested. `0` for general setting
			 */
			$direct_access = apply_filters( 'gravityview_direct_access', true, $view_id );

			if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
				$embed_only = $view->settings->get( 'embed_only' );
			} else {
				/** Deprecated. View attributes moved to \GV\View::$settings. */
				$embed_only = ! empty( $atts['embed_only'] );
			}

			if( ! $direct_access || ( $embed_only && ! GVCommon::has_cap( 'read_private_gravityviews' ) ) ) {
				return __( 'You are not allowed to view this content.', 'gravityview' );
			}
		}

		ob_start();

		/**
		 * Set globals for templating
		 * @deprecated 1.6.2
		 */
		global $gravityview_view;

		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			$view_data = $view->as_data();
			$gravityview_view = new GravityView_View( $view_data );
			$post_id = intval( $view->settings->get( 'post_id' ) ? : get_the_ID() );
			$template_id = $view->template ? $view->template->ID : null;
		} else {
			/** These constructs are deprecated. Use the new gravityview() wrapper. */
			$gravityview_view = new GravityView_View( $view_data );
			$post_id = ! empty( $atts['post_id'] ) ? intval( $atts['post_id'] ) : get_the_ID();
			$template_id = $view_data['template_id'];
		}

		$gravityview_view->setPostId( $post_id );

		if ( ! $this->getSingleEntry() ) {

			// user requested Directory View
			do_action( 'gravityview_log_debug', '[render_view] Executing Directory View' );

			//fetch template and slug
			$view_slug = apply_filters( 'gravityview_template_slug_'. $template_id, 'table', 'directory' );

			do_action( 'gravityview_log_debug', '[render_view] View template slug: ', $view_slug );

			/**
			 * Disable fetching initial entries for views that don't need it (DataTables)
			 */
			$get_entries = apply_filters( 'gravityview_get_view_entries_'.$view_slug, true );

			if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
				$hide_until_searched = $view->settings->get( 'hide_until_searched' );
			} else {
				/** $atts is deprecated, use \GV\View:$settings */
				$hide_until_searched = ! empty( $atts['hide_until_searched'] );
			}

			/**
			 * Hide View data until search is performed
			 * @since 1.5.4
			 */
			if ( $hide_until_searched && ! $this->isSearch() ) {
				$gravityview_view->setHideUntilSearched( true );
				$get_entries = false;
			}

			if ( $get_entries ) {

				if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
					$sort_columns = $view->settings->get( 'sort_columns' );
				} else {
					/** $atts is deprecated, use \GV\View:$settings */
					$sort_columns = ! empty( $atts['sort_columns'] );
				}

				if ( $sort_columns ) {
					// add filter to enable column sorting
					add_filter( 'gravityview/template/field_label', array( $this, 'add_columns_sort_links' ) , 100, 3 );
				}

				if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
					$view_entries = self::get_view_entries( $view->settings->as_atts(), $view->form->ID );
				} else {
					/** $atts is deprecated, use \GV\View:$settings */
					/** $view_data is depreacted, use \GV\View properties */
					$view_entries = self::get_view_entries( $atts, $view_data['form_id'] );
				}

				do_action( 'gravityview_log_debug', sprintf( '[render_view] Get Entries. Found %s entries total, showing %d entries', $view_entries['count'], sizeof( $view_entries['entries'] ) ) );

			} else {

				$view_entries = array( 'count' => null, 'entries' => null, 'paging' => null );

				do_action( 'gravityview_log_debug', '[render_view] Not fetching entries because `gravityview_get_view_entries_'.$view_slug.'` is false' );
			}

			$gravityview_view->setPaging( $view_entries['paging'] );
			$gravityview_view->setContext( 'directory' );
			$sections = array( 'header', 'body', 'footer' );

		} else {

			// user requested Single Entry View
			do_action( 'gravityview_log_debug', '[render_view] Executing Single View' );

			/**
			 * @action `gravityview_render_entry_{View ID}` Before rendering a single entry for a specific View ID
			 * @since 1.17
			 */
			if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
				do_action( 'gravityview_render_entry_' . $view->ID );
			} else {
				/** $view_data is depreacted, use \GV\View properties */
				do_action( 'gravityview_render_entry_'.$view_data['id'] );
			}

			$entry = $this->getEntry();

			// You are not permitted to view this entry.
			if ( empty( $entry ) || ! self::is_entry_approved( $entry, defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ? $view->settings->as_atts() : $atts ) ) {

				do_action( 'gravityview_log_debug', '[render_view] Entry does not exist. This may be because of View filters limiting access.' );

				// Only display warning once when multiple Views are embedded
				if( $view_id !== (int) GravityView_frontend::get_context_view_id() ) {
					ob_end_clean();
					return null;
				}

				/**
				 * @filter `gravityview/render/entry/not_visible` Modify the message shown to users when the entry doesn't exist or they aren't allowed to view it.
				 * @since 1.6
				 * @param string $message Default: "You have attempted to view an entry that is not visible or may not exist."
				 */
				$message = apply_filters( 'gravityview/render/entry/not_visible', __( 'You have attempted to view an entry that is not visible or may not exist.', 'gravityview' ) );

				/**
				 * @since 1.6
				 */
				echo esc_attr( $message );

				ob_end_clean();
				return null;
			}

			// We're in single view, but the view being processed is not the same view the single entry belongs to.
			// important: do not remove this as it prevents fake attempts of displaying entries from other views/forms
			$multiple_views = defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ? gravityview()->views->count() > 1 : $this->getGvOutputData()->has_multiple_views();
			if ( $multiple_views && $view_id != $this->get_context_view_id() ) {
				do_action( 'gravityview_log_debug', '[render_view] In single entry view, but the entry does not belong to this View. Perhaps there are multiple views on the page. View ID: '. $view_id );
				ob_end_clean();
				return null;
			}

			//fetch template and slug
			$view_slug = apply_filters( 'gravityview_template_slug_' . $template_id, 'table', 'single' );
			do_action( 'gravityview_log_debug', '[render_view] View single template slug: ', $view_slug );

			//fetch entry detail
			$view_entries['count'] = 1;
			$view_entries['entries'][] = $entry;
			do_action( 'gravityview_log_debug', '[render_view] Get single entry: ', $view_entries['entries'] );

			if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
				$back_link_label = $view->settings->get( 'back_link_label', null );
			} else {
				$back_link_label = isset( $atts['back_link_label'] ) ? $atts['back_link_label'] : null;
			}

			// set back link label
			$gravityview_view->setBackLinkLabel( $back_link_label );
			$gravityview_view->setContext( 'single' );
			$sections = array( 'single' );

		}

		// add template style
		self::add_style( $template_id );

		// Prepare to render view and set vars
		$gravityview_view->setEntries( $view_entries['entries'] );
		$gravityview_view->setTotalEntries( $view_entries['count'] );

		// If Edit
		if ( 'edit' === gravityview_get_context() ) {

			do_action( 'gravityview_log_debug', '[render_view] Edit Entry ' );

			do_action( 'gravityview_edit_entry', $this->getGvOutputData() );

			return ob_get_clean();

		} else {
			// finaly we'll render some html
			$sections = apply_filters( 'gravityview_render_view_sections', $sections, $template_id );

			do_action( 'gravityview_log_debug', '[render_view] Sections to render: ', $sections );
			foreach ( $sections as $section ) {
				do_action( 'gravityview_log_debug', '[render_view] Rendering '. $section . ' section.' );
				$gravityview_view->render( $view_slug, $section, false );
			}
		}

		//@todo: check why we need the IF statement vs. print the view id always.
		if ( $this->isGravityviewPostType() || $this->isPostHasShortcode() ) {
			// Print the View ID to enable proper cookie pagination
			echo '<input type="hidden" class="gravityview-view-id" value="' . esc_attr( $view_id ) . '">';
		}
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Process the start and end dates for a view - overrides values defined in shortcode (if needed)
	 *
	 * The `start_date` and `end_date` keys need to be in a format processable by GFFormsModel::get_date_range_where(),
	 * which uses \DateTime() format.
	 *
	 * You can set the `start_date` or `end_date` to any value allowed by {@link http://www.php.net//manual/en/function.strtotime.php strtotime()},
	 * including strings like "now" or "-1 year" or "-3 days".
	 *
	 * @see GFFormsModel::get_date_range_where
	 *
	 * @param  array      $args            View settings
	 * @param  array      $search_criteria Search being performed, if any
	 * @return array                       Modified `$search_criteria` array
	 */
	public static function process_search_dates( $args, $search_criteria = array() ) {

		$return_search_criteria = $search_criteria;

		foreach ( array( 'start_date', 'end_date' ) as $key ) {


			// Is the start date or end date set in the view or shortcode?
			// If so, we want to make sure that the search doesn't go outside the bounds defined.
			if ( ! empty( $args[ $key ] ) ) {

				// Get a timestamp and see if it's a valid date format
				$date = strtotime( $args[ $key ] );

				// The date was invalid
				if ( empty( $date ) ) {
					do_action( 'gravityview_log_error', __METHOD__ . ' Invalid ' . $key . ' date format: ' . $args[ $key ] );
					continue;
				}

				// The format that Gravity Forms expects for start_date and day-specific (not hour/second-specific) end_date
				$datetime_format = 'Y-m-d H:i:s';
				$search_is_outside_view_bounds = false;

				if( ! empty( $search_criteria[ $key ] ) ) {

					$search_date = strtotime( $search_criteria[ $key ] );

					// The search is for entries before the start date defined by the settings
					switch ( $key ) {
						case 'end_date':
							/**
							 * If the end date is formatted as 'Y-m-d', it should be formatted without hours and seconds
							 * so that Gravity Forms can convert the day to 23:59:59 the previous day.
							 *
							 * If it's a relative date ("now" or "-1 day"), then it should use the precise date format
							 *
							 * @see GFFormsModel::get_date_range_where
							 */
							$datetime_format               = gravityview_is_valid_datetime( $args[ $key ] ) ? 'Y-m-d' : 'Y-m-d H:i:s';
							$search_is_outside_view_bounds = ( $search_date > $date );
							break;
						case 'start_date':
							$search_is_outside_view_bounds = ( $search_date < $date );
							break;
					}
				}

				// If there is no search being performed, or if there is a search being performed that's outside the bounds
				if ( empty( $search_criteria[ $key ] ) || $search_is_outside_view_bounds ) {

					// Then we override the search and re-set the start date
					$return_search_criteria[ $key ] = date_i18n( $datetime_format , $date, true );
				}
			}
		}

		if( isset( $return_search_criteria['start_date'] ) && isset( $return_search_criteria['end_date'] ) ) {
			// The start date is AFTER the end date. This will result in no results, but let's not force the issue.
			if ( strtotime( $return_search_criteria['start_date'] ) > strtotime( $return_search_criteria['end_date'] ) ) {
				do_action( 'gravityview_log_error', __METHOD__ . ' Invalid search: the start date is after the end date.', $return_search_criteria );
			}
		}

		return $return_search_criteria;
	}


	/**
	 * Process the approved only search criteria according to the View settings
	 *
	 * @param  array      $args            View settings
	 * @param  array      $search_criteria Search being performed, if any
	 * @return array                       Modified `$search_criteria` array
	 */
	public static function process_search_only_approved( $args, $search_criteria ) {

		/** @since 1.19 */
		if( ! empty( $args['admin_show_all_statuses'] ) && GVCommon::has_cap('gravityview_moderate_entries') ) {
			do_action( 'gravityview_log_debug', __METHOD__ . ': User can moderate entries; showing all approval statuses' );
			return $search_criteria;
		}

		if ( ! empty( $args['show_only_approved'] ) ) {

			$search_criteria['field_filters'][] = array(
				'key' => GravityView_Entry_Approval::meta_key,
				'value' => GravityView_Entry_Approval_Status::APPROVED
			);

			$search_criteria['field_filters']['mode'] = 'all'; // force all the criterias to be met

			do_action( 'gravityview_log_debug', '[process_search_only_approved] Search Criteria if show only approved: ', $search_criteria );
		}

		return $search_criteria;
	}


	/**
	 * Check if a certain entry is approved.
	 *
	 * If we pass the View settings ($args) it will check the 'show_only_approved' setting before
	 *   checking the entry approved field, returning true if show_only_approved = false.
	 *
	 * @since 1.7
	 * @since 1.18 Converted check to use GravityView_Entry_Approval_Status::is_approved
	 *
	 * @uses GravityView_Entry_Approval_Status::is_approved
	 *
	 * @param array $entry  Entry object
	 * @param array $args   View settings (optional)
	 *
	 * @return bool
	 */
	public static function is_entry_approved( $entry, $args = array() ) {

		if ( empty( $entry['id'] ) || ( array_key_exists( 'show_only_approved', $args ) && ! $args['show_only_approved'] ) ) {
			// is implicitly approved if entry is null or View settings doesn't require to check for approval
			return true;
		}

		/** @since 1.19 */
		if( ! empty( $args['admin_show_all_statuses'] ) && GVCommon::has_cap('gravityview_moderate_entries') ) {
			do_action( 'gravityview_log_debug', __METHOD__ . ': User can moderate entries, so entry is approved for viewing' );
			return true;
		}

		$is_approved = gform_get_meta( $entry['id'], GravityView_Entry_Approval::meta_key );

		return GravityView_Entry_Approval_Status::is_approved( $is_approved );
	}

	/**
	 * Parse search criteria for a entries search.
	 *
	 * array(
	 * 	'search_field' => 1, // ID of the field
	 *  'search_value' => '', // Value of the field to search
	 *  'search_operator' => 'contains', // 'is', 'isnot', '>', '<', 'contains'
	 *  'show_only_approved' => 0 or 1 // Boolean
	 * )
	 *
	 * @param  array $args    Array of args
	 * @param  int $form_id Gravity Forms form ID
	 * @return array          Array of search parameters, formatted in Gravity Forms mode, using `status` key set to "active" by default, `field_filters` array with `key`, `value` and `operator` keys.
	 */
	public static function get_search_criteria( $args, $form_id ) {

		/**
		 * @filter `gravityview_fe_search_criteria` Modify the search criteria
		 * @see GravityView_Widget_Search::filter_entries Adds the default search criteria
		 * @param array $search_criteria Empty `field_filters` key
		 * @param int $form_id ID of the Gravity Forms form that is being searched
		 */
		$search_criteria = apply_filters( 'gravityview_fe_search_criteria', array( 'field_filters' => array() ), $form_id );

		$original_search_criteria = $search_criteria;

		do_action( 'gravityview_log_debug', '[get_search_criteria] Search Criteria after hook gravityview_fe_search_criteria: ', $search_criteria );

		// implicity search
		if ( ! empty( $args['search_value'] ) ) {

			// Search operator options. Options: `is` or `contains`
			$operator = ! empty( $args['search_operator'] ) && in_array( $args['search_operator'], array( 'is', 'isnot', '>', '<', 'contains' ) ) ? $args['search_operator'] : 'contains';

			$search_criteria['field_filters'][] = array(
				'key' => rgget( 'search_field', $args ), // The field ID to search
				'value' => _wp_specialchars( $args['search_value'] ), // The value to search. Encode ampersands but not quotes.
				'operator' => $operator,
			);

			// Lock search mode to "all" with implicit presearch filter.
			$search_criteria['field_filters']['mode'] = 'all';
		}

		if( $search_criteria !== $original_search_criteria ) {
			do_action( 'gravityview_log_debug', '[get_search_criteria] Search Criteria after implicity search: ', $search_criteria );
		}

		// Handle setting date range
		$search_criteria = self::process_search_dates( $args, $search_criteria );

		if( $search_criteria !== $original_search_criteria ) {
			do_action( 'gravityview_log_debug', '[get_search_criteria] Search Criteria after date params: ', $search_criteria );
		}

		// remove not approved entries
		$search_criteria = self::process_search_only_approved( $args, $search_criteria );

		/**
		 * @filter `gravityview_status` Modify entry status requirements to be included in search results.
		 * @param string $status Default: `active`. Accepts all Gravity Forms entry statuses, including `spam` and `trash`
		 */
		$search_criteria['status'] = apply_filters( 'gravityview_status', 'active', $args );

		return $search_criteria;
	}



	/**
	 * Core function to calculate View multi entries (directory) based on a set of arguments ($args):
	 *   $id - View id
	 *   $page_size - Page
	 *   $sort_field - form field id to sort
	 *   $sort_direction - ASC / DESC
	 *   $start_date - Ymd
	 *   $end_date - Ymd
	 *   $class - assign a html class to the view
	 *   $offset (optional) - This is the start point in the current data set (0 index based).
	 *
	 *
	 *
	 * @uses  gravityview_get_entries()
	 * @access public
	 * @param array $args\n
	 *   - $id - View id
	 *   - $page_size - Page
	 *   - $sort_field - form field id to sort
	 *   - $sort_direction - ASC / DESC
	 *   - $start_date - Ymd
	 *   - $end_date - Ymd
	 *   - $class - assign a html class to the view
	 *   - $offset (optional) - This is the start point in the current data set (0 index based).
	 * @param int $form_id Gravity Forms Form ID
	 * @return array Associative array with `count`, `entries`, and `paging` keys. `count` has the total entries count, `entries` is an array with Gravity Forms full entry data, `paging` is an array with `offset` and `page_size` keys
	 */
	public static function get_view_entries( $args, $form_id ) {

		do_action( 'gravityview_log_debug', '[get_view_entries] init' );
		// start filters and sorting

		$parameters = self::get_view_entries_parameters( $args, $form_id );

		$count = 0; // Must be defined so that gravityview_get_entries can use by reference

		// fetch entries
		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			list( $entries, $paging, $count ) =
				\GV\Mocks\GravityView_frontend_get_view_entries( $args, $form_id, $parameters, $count );
		} else {
			/** Deprecated, use $form->entries instead. */
			$entries = gravityview_get_entries( $form_id, $parameters, $count );

			/** Set paging. */
			$paging = rgar( $parameters, 'paging' );

			/** Adjust count by defined offset. */
			$count = max( 0, ( $count - rgar( $args, 'offset', 0 ) ) );
		}

		do_action( 'gravityview_log_debug', sprintf( '%s: Get Entries. Found: %s entries', __METHOD__, $count ), $entries );

		/**
		 * @filter `gravityview_view_entries` Filter the entries output to the View
		 * @deprecated since 1.5.2
		 * @param array $args View settings associative array
		 * @var array
		 */
		$entries = apply_filters( 'gravityview_view_entries', $entries, $args );

		$return = array(
			'count' => $count,
			'entries' => $entries,
			'paging' => $paging,
		);

		/**
		 * @filter `gravityview/view/entries` Filter the entries output to the View
		 * @param array $criteria associative array containing count, entries & paging
		 * @param array $args View settings associative array
		 * @since 1.5.2
		 */
		return apply_filters( 'gravityview/view/entries', $return, $args );
	}

	/**
	 * Get an array of search parameters formatted as Gravity Forms requires
	 *
	 * Results are filtered by `gravityview_get_entries` and `gravityview_get_entries_{View ID}` filters
	 *
	 * @uses GravityView_frontend::get_search_criteria
	 * @uses GravityView_frontend::get_search_criteria_paging
	 *
	 * @since 1.20
	 *
	 * @see \GV\View_Settings::defaults For $args options
	 *
	 * @param array $args Array of View settings, as structured in \GV\View_Settings::defaults
	 * @param int $form_id Gravity Forms form ID to search
	 *
	 * @return array With `search_criteria`, `sorting`, `paging`, `cache` keys
	 */
	public static function get_view_entries_parameters( $args = array(), $form_id = 0 ) {


		if ( ! is_array( $args ) || ! is_numeric( $form_id ) ) {

			do_action( 'gravityview_log_error', __METHOD__ . ': Passed args are not an array or the form ID is not numeric' );

			return array();
		}

		$form_id = intval( $form_id );

		/**
		 * Process search parameters
		 * @var array
		 */
		$search_criteria = self::get_search_criteria( $args, $form_id );

		$paging = self::get_search_criteria_paging( $args );

		$parameters = array(
			'search_criteria' => $search_criteria,
			'sorting' => self::updateViewSorting( $args, $form_id ),
			'paging' => $paging,
			'cache' => isset( $args['cache'] ) ? $args['cache'] : true,
		);

		/**
		 * @filter `gravityview_get_entries` Filter get entries criteria
		 * @param array $parameters Array with `search_criteria`, `sorting` and `paging` keys.
		 * @param array $args View configuration args. {
		 *      @type int $id View id
		 *      @type int $page_size Number of entries to show per page
		 *      @type string $sort_field Form field id to sort
		 *      @type string $sort_direction Sorting direction ('ASC' or 'DESC')
		 *      @type string $start_date - Ymd
		 *      @type string $end_date - Ymd
		 *      @type string $class - assign a html class to the view
		 *      @type string $offset (optional) - This is the start point in the current data set (0 index based).
		 * }
		 * @param int $form_id ID of Gravity Forms form
		 */
		$parameters = apply_filters( 'gravityview_get_entries', $parameters, $args, $form_id );

		/**
		 * @filter `gravityview_get_entries_{View ID}` Filter get entries criteria
		 * @param array $parameters Array with `search_criteria`, `sorting` and `paging` keys.
		 * @param array $args View configuration args.
		 */
		$parameters = apply_filters( 'gravityview_get_entries_'.$args['id'], $parameters, $args, $form_id );

		do_action( 'gravityview_log_debug', __METHOD__ . ': $parameters passed to gravityview_get_entries(): ', $parameters );

		return $parameters;
	}

	/**
	 * Get the paging array for the View
	 *
	 * @since 1.19.5
	 *
	 * @param $args
	 * @param int $form_id
	 */
	public static function get_search_criteria_paging( $args ) {

		/**
		 * @filter `gravityview_default_page_size` The default number of entries displayed in a View
		 * @since 1.1.6
		 * @param int $default_page_size Default: 25
		 */
		$default_page_size = apply_filters( 'gravityview_default_page_size', 25 );

		// Paging & offset
		$page_size = ! empty( $args['page_size'] ) ? intval( $args['page_size'] ) : $default_page_size;

		if ( -1 === $page_size ) {
			$page_size = PHP_INT_MAX;
		}

		$curr_page = empty( $_GET['pagenum'] ) ? 1 : intval( $_GET['pagenum'] );
		$offset = ( $curr_page - 1 ) * $page_size;

		if ( ! empty( $args['offset'] ) ) {
			$offset += intval( $args['offset'] );
		}

		$paging = array(
			'offset' => $offset,
			'page_size' => $page_size,
		);

		do_action( 'gravityview_log_debug', __METHOD__ . ': Paging: ', $paging );

		return $paging;
	}

	/**
	 * Updates the View sorting criteria
	 *
	 * @since 1.7
	 *
	 * @param array $args View settings. Required to have `sort_field` and `sort_direction` keys
	 * @param int $form_id The ID of the form used to sort
	 * @return array $sorting Array with `key`, `direction` and `is_numeric` keys
	 */
	public static function updateViewSorting( $args, $form_id ) {
		$sorting = array();
		$sort_field_id = isset( $_GET['sort'] ) ? $_GET['sort'] : rgar( $args, 'sort_field' );
		$sort_direction = isset( $_GET['dir'] ) ? $_GET['dir'] : rgar( $args, 'sort_direction' );

		$sort_field_id = self::_override_sorting_id_by_field_type( $sort_field_id, $form_id );

		if ( ! empty( $sort_field_id ) ) {
			$sorting = array(
				'key' => $sort_field_id,
				'direction' => strtolower( $sort_direction ),
				'is_numeric' => GVCommon::is_field_numeric( $form_id, $sort_field_id )
			);
		}

		GravityView_View::getInstance()->setSorting( $sorting );

		do_action( 'gravityview_log_debug', '[updateViewSorting] Sort Criteria : ', $sorting );

		return $sorting;

	}

	/**
	 * Override sorting per field
	 *
	 * Currently only modifies sorting ID when sorting by the full name. Sorts by first name.
	 * Use the `gravityview/sorting/full-name` filter to override.
	 *
	 * @todo Filter from GravityView_Field
	 * @since 1.7.4
	 *
	 * @param int|string $sort_field_id Field used for sorting (`id` or `1.2`)
	 * @param int $form_id GF Form ID
	 *
	 * @return string Possibly modified sorting ID
	 */
	private static function _override_sorting_id_by_field_type( $sort_field_id, $form_id ) {

		$form = gravityview_get_form( $form_id );

		$sort_field = GFFormsModel::get_field( $form, $sort_field_id );

		if( ! $sort_field ) {
			return $sort_field_id;
		}

		switch ( $sort_field['type'] ) {

			case 'address':
				// Sorting by full address
				if ( floatval( $sort_field_id ) === floor( $sort_field_id ) ) {

					/**
					 * Override how to sort when sorting address
					 *
					 * @since 1.8
					 *
					 * @param string $address_part `street`, `street2`, `city`, `state`, `zip`, or `country` (default: `city`)
					 * @param string $sort_field_id Field used for sorting
					 * @param int $form_id GF Form ID
					 */
					$address_part = apply_filters( 'gravityview/sorting/address', 'city', $sort_field_id, $form_id );

					switch( strtolower( $address_part ) ){
						case 'street':
							$sort_field_id .= '.1';
							break;
						case 'street2':
							$sort_field_id .= '.2';
							break;
						default:
						case 'city':
							$sort_field_id .= '.3';
							break;
						case 'state':
							$sort_field_id .= '.4';
							break;
						case 'zip':
							$sort_field_id .= '.5';
							break;
						case 'country':
							$sort_field_id .= '.6';
							break;
					}

				}
				break;
			case 'name':
				// Sorting by full name, not first, last, etc.
				if ( floatval( $sort_field_id ) === floor( $sort_field_id ) ) {
					/**
					 * @filter `gravityview/sorting/full-name` Override how to sort when sorting full name.
					 * @since 1.7.4
					 * @param[in,out] string $name_part Sort by `first` or `last` (default: `first`)
					 * @param[in] string $sort_field_id Field used for sorting
					 * @param[in] int $form_id GF Form ID
					 */
					$name_part = apply_filters( 'gravityview/sorting/full-name', 'first', $sort_field_id, $form_id );

					if ( 'last' === strtolower( $name_part ) ) {
						$sort_field_id .= '.6';
					} else {
						$sort_field_id .= '.3';
					}
				}
				break;
			case 'list':
				$sort_field_id = false;
				break;
			case 'time':

				/**
				 * @filter `gravityview/sorting/time` Override how to sort when sorting time
				 * @see GravityView_Field_Time
				 * @since 1.14
				 * @param[in,out] string $name_part Field used for sorting
				 * @param[in] int $form_id GF Form ID
				 */
				$sort_field_id = apply_filters( 'gravityview/sorting/time', $sort_field_id, $form_id );
				break;
		}

		return $sort_field_id;
	}

	/**
	 * Verify if user requested a single entry view
	 * @return boolean|string false if not, single entry slug if true
	 */
	public static function is_single_entry() {

		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			$var_name = \GV\Entry::get_endpoint_name();
		} else {
			/** Deprecated. Use \GV\Entry::get_endpoint_name instead. */
			$var_name = GravityView_Post_Types::get_entry_var_name();
		}

		$single_entry = get_query_var( $var_name );

		/**
		 * Modify the entry that is being displayed.
		 *
		 * @internal Should only be used by things like the oEmbed functionality.
		 * @since 1.6
		 */
		$single_entry = apply_filters( 'gravityview/is_single_entry', $single_entry );

		if ( empty( $single_entry ) ){
			return false;
		} else {
			return $single_entry;
		}
	}


	/**
	 * Register styles and scripts
	 *
	 * @access public
	 * @return void
	 */
	public function add_scripts_and_styles() {
		global $post, $posts;
		// enqueue template specific styles
		if ( $this->getGvOutputData() ) {

			if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
				$views = gravityview()->views->all();
			} else {
				/** \GravityView_View_Data::get_view is no more... */
				$views = $this->getGvOutputData()->get_views();
			}

			foreach ( $views as $view_id => $data ) {
				if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
					$view = $data;
					$view_id = $view->ID;
					$template_id = $view->template ? $view->template->ID : null;
					$data = $view->as_data();
				} else {
					$template_id = $data['template_id'];
				}

				/**
				 * Don't enqueue the scripts or styles if it's not going to be displayed.
				 * @since 1.15
				 */
				if( is_user_logged_in() && false === GVCommon::has_cap( 'read_gravityview', $view_id ) ) {
					continue;
				}

				// By default, no thickbox
				$js_dependencies = array( 'jquery', 'gravityview-jquery-cookie' );
				$css_dependencies = array();

				if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
					$lightbox = $view->settings->get( 'lightbox' );
				} else {
					/** View data attributes are now stored in \GV\View::$settings */
					$lightbox = ! empty( $data['atts']['lightbox'] );
				}

				// If the thickbox is enqueued, add dependencies
				if ( $lightbox ) {

					/**
					 * @filter `gravity_view_lightbox_script` Override the lightbox script to enqueue. Default: `thickbox`
					 * @param string $script_slug If you want to use a different lightbox script, return the name of it here.
					 */
					$js_dependencies[] = apply_filters( 'gravity_view_lightbox_script', 'thickbox' );

					/**
					 * @filter `gravity_view_lightbox_style` Modify the lightbox CSS slug. Default: `thickbox`
					 * @param string $script_slug If you want to use a different lightbox script, return the name of its CSS file here.
					 */
					$css_dependencies[] = apply_filters( 'gravity_view_lightbox_style', 'thickbox' );
				}

				/**
				 * If the form has checkbox fields, enqueue dashicons
				 * @see https://github.com/katzwebservices/GravityView/issues/536
				 * @since 1.15
				 */
				if( gravityview_view_has_single_checkbox_or_radio( $data['form'], $data['fields'] ) ) {
					$css_dependencies[] = 'dashicons';
				}

				wp_register_script( 'gravityview-jquery-cookie', plugins_url( 'assets/lib/jquery.cookie/jquery.cookie.min.js', GRAVITYVIEW_FILE ), array( 'jquery' ), GravityView_Plugin::version, true );

				$script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

				wp_register_script( 'gravityview-fe-view', plugins_url( 'assets/js/fe-views' . $script_debug . '.js', GRAVITYVIEW_FILE ), apply_filters( 'gravityview_js_dependencies', $js_dependencies ) , GravityView_Plugin::version, true );

				wp_enqueue_script( 'gravityview-fe-view' );

				if ( ! empty( $data['atts']['sort_columns'] ) ) {
					wp_enqueue_style( 'gravityview_font', plugins_url( 'assets/css/font.css', GRAVITYVIEW_FILE ), $css_dependencies, GravityView_Plugin::version, 'all' );
				}

				$this->enqueue_default_style( $css_dependencies );

				self::add_style( $template_id );
			}

			if ( 'wp_print_footer_scripts' === current_filter() ) {

				$js_localization = array(
					'cookiepath' => COOKIEPATH,
					'clear' => _x( 'Clear', 'Clear all data from the form', 'gravityview' ),
					'reset' => _x( 'Reset', 'Reset the search form to the state that existed on page load', 'gravityview' ),
				);

				/**
				 * @filter `gravityview_js_localization` Modify the array passed to wp_localize_script()
				 * @param array $js_localization The data padded to the Javascript file
				 * @param array $views Array of View data arrays with View settings
				 */
				$js_localization = apply_filters( 'gravityview_js_localization', $js_localization, $views );

				wp_localize_script( 'gravityview-fe-view', 'gvGlobals', $js_localization );
			}
		}
	}

	/**
	 * Handle enqueuing the `gravityview_default_style` stylesheet
	 *
	 * @since 1.17
	 *
	 * @param array $css_dependencies Dependencies for the `gravityview_default_style` stylesheet
	 *
	 * @return void
	 */
	private function enqueue_default_style( $css_dependencies = array() ) {

		/**
		 * @filter `gravityview_use_legacy_search_css` Should GravityView use the legacy Search Bar stylesheet (from before Version 1.17)?
		 * @since 1.17
		 * @param bool $use_legacy_search_style If true, loads `gv-legacy-search(-rtl).css`. If false, loads `gv-default-styles(-rtl).css`. `-rtl` is added on RTL websites. Default: `false`
		 */
		$use_legacy_search_style = apply_filters( 'gravityview_use_legacy_search_style', false );

		$rtl = is_rtl() ? '-rtl' : '';

		$css_file_base = $use_legacy_search_style ? 'gv-legacy-search' : 'gv-default-styles';

		$path = gravityview_css_url( $css_file_base . $rtl . '.css' );

		wp_enqueue_style( 'gravityview_default_style', $path, $css_dependencies, GravityView_Plugin::version, 'all' );
	}

	/**
	 * Add template extra style if exists
	 * @param string $template_id
	 */
	public static function add_style( $template_id ) {

		if ( ! empty( $template_id ) && wp_style_is( 'gravityview_style_' . $template_id, 'registered' ) ) {
			do_action( 'gravityview_log_debug', sprintf( '[add_style] Adding extra template style for %s', $template_id ) );
			wp_enqueue_style( 'gravityview_style_' . $template_id );
		} elseif ( empty( $template_id ) ) {
			do_action( 'gravityview_log_error', '[add_style] Cannot add template style; template_id is empty' );
		} else {
			do_action( 'gravityview_log_error', sprintf( '[add_style] Cannot add template style; %s is not registered', 'gravityview_style_'.$template_id ) );
		}

	}


	/**
	 * Inject the sorting links on the table columns
	 *
	 * Callback function for hook 'gravityview/template/field_label'
	 * @see GravityView_API::field_label() (in includes/class-api.php)
	 *
	 * @since 1.7
	 *
	 * @param string $label Field label
	 * @param array $field Field settings
	 *
	 * @return string Field Label
	 */
	public function add_columns_sort_links( $label = '', $field, $form ) {

		/**
		 * Not a table-based template; don't add sort icons
		 * @since 1.12
		 */
		if( ! preg_match( '/table/ism', GravityView_View::getInstance()->getTemplatePartSlug() ) ) {
			return $label;
		}

		if ( ! $this->is_field_sortable( $field['id'], $form ) ) {
			return $label;
		}

		$sorting = GravityView_View::getInstance()->getSorting();

		$class = 'gv-sort';

		$sort_field_id = self::_override_sorting_id_by_field_type( $field['id'], $form['id'] );

		$sort_args = array(
			'sort' => $field['id'],
			'dir' => 'asc',
		);

		if ( ! empty( $sorting['key'] ) && (string) $sort_field_id === (string) $sorting['key'] ) {
			//toggle sorting direction.
			if ( 'asc' === $sorting['direction'] ) {
				$sort_args['dir'] = 'desc';
				$class .= ' gv-icon-sort-desc';
			} else {
				$sort_args['dir'] = 'asc';
				$class .= ' gv-icon-sort-asc';
			}
		} else {
			$class .= ' gv-icon-caret-up-down';
		}

		$url = add_query_arg( $sort_args, remove_query_arg( array('pagenum') ) );

		return '<a href="'. esc_url_raw( $url ) .'" class="'. $class .'" ></a>&nbsp;'. $label;

	}

	/**
	 * Checks if field (column) is sortable
	 *
	 * @param string $field Field settings
	 * @param array $form Gravity Forms form array
	 *
	 * @since 1.7
	 *
	 * @return bool True: Yes, field is sortable; False: not sortable
	 */
	public function is_field_sortable( $field_id = '', $form = array() ) {

		$field_type = $field_id;

		if( is_numeric( $field_id ) ) {
			$field = GFFormsModel::get_field( $form, $field_id );
			$field_type = $field->type;
		}

		$not_sortable = array(
			'edit_link',
			'delete_link',
		);

		/**
		 * @filter `gravityview/sortable/field_blacklist` Modify what fields should never be sortable.
		 * @since 1.7
		 * @param[in,out] array $not_sortable Array of field types that aren't sortable
		 * @param string $field_type Field type to check whether the field is sortable
		 * @param array $form Gravity Forms form
		 */
		$not_sortable = apply_filters( 'gravityview/sortable/field_blacklist', $not_sortable, $field_type, $form );

		if ( in_array( $field_type, $not_sortable ) ) {
			return false;
		}

		return apply_filters( "gravityview/sortable/formfield_{$form['id']}_{$field_id}", apply_filters( "gravityview/sortable/field_{$field_id}", true, $form ) );

	}

}

GravityView_frontend::getInstance();



