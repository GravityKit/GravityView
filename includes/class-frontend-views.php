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
	private static $search_parameters = array( 'gv_search', 'gv_start', 'gv_end', 'gv_id', 'filter_*' );

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
	var $post_id = NULL;

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
    var $context_view_id = NULL;

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
	var $gv_output_data = NULL;

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
		add_action( 'template_redirect', array( $this, 'set_entry_data'), 1 );

		// Shortcode to render view (directory)
		add_shortcode( 'gravityview', array( $this, 'shortcode' ) );

		// Enqueue scripts and styles after GravityView_Template::register_styles()
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_and_styles' ), 20);

		// Enqueue and print styles in the footer. Added 1 priorty so stuff gets printed at 10 priority.
		add_action( 'wp_print_footer_scripts', array( $this, 'add_scripts_and_styles' ), 1);

		add_filter( 'the_title', array( $this, 'single_entry_title' ), 1, 2 );
		add_filter( 'the_content', array( $this, 'insert_view_in_content' ) );
		add_filter( 'comments_open', array( $this, 'comments_open' ), 10, 2);

		add_action('add_admin_bar_menus', array( $this, 'admin_bar_remove_links'), 80 );
		add_action('admin_bar_menu', array( $this, 'admin_bar_add_links'), 85 );
	}

	/**
	 * Get the one true instantiated self
	 * @return GravityView_frontend
	 */
	public static function getInstance() {

		if( empty( self::$instance ) ) {
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
	 * @param bool|int $single_entry
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
	 * @param array|int $entry Entry array or entry ID
	 */
	public function setEntry( $entry ) {

		if( !is_array( $entry ) ) {
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

        if ( !empty( $view_id ) ) {

            $this->context_view_id = $view_id;

        } elseif( isset( $_GET['gvid'] ) && $this->getGvOutputData()->has_multiple_views() ) {
            /**
             * used on a has_multiple_views context
             * @see GravityView_API::entry_link
             * @see GravityView_View_Data::getInstance()->has_multiple_views()
             */
            $this->context_view_id = $_GET['gvid'];

        } elseif( !$this->getGvOutputData()->has_multiple_views() )  {
	        $array_keys = array_keys( $this->getGvOutputData()->get_views() );
            $this->context_view_id = array_pop( $array_keys );
	        unset( $array_keys );
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
	 * Read the $post and process the View data inside
	 * @param  array  $wp Passed in the `wp` hook. Not used.
	 * @return void
	 */
	function parse_content( $wp = array() ) {
		global $post;

		// If in admin and NOT AJAX request, get outta here.
		if( GravityView_Plugin::is_admin() )  {
			return;
		}

		// Calculate requested Views
		$this->setGvOutputData( GravityView_View_Data::getInstance( $post ) );

		// !important: we need to run this before getting single entry (to kick the advanced filter)
		$this->set_context_view_id();


		$this->setIsGravityviewPostType( get_post_type( $post ) === 'gravityview' );

		$post_id = $this->getPostId() ? $this->getPostId() : (isset( $post ) ? $post->ID : NULL );
		$this->setPostId( $post_id );
		$post_has_shortcode = !empty( $post->post_content ) ? gravityview_has_shortcode_r( $post->post_content, 'gravityview' ) : false;
		$this->setPostHasShortcode( $this->isGravityviewPostType() ? NULL : !empty( $post_has_shortcode ) );

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
		if( $this->getSingleEntry() ) {
			return false;
		}

		// No $_GET parameters
		if( empty( $_GET ) || !is_array( $_GET ) ) {
			return false;
		}

		// Remove empty values
		$get = array_filter( $_GET );

		// If the $_GET parameters are empty, it's no search.
		if ( empty( $get ) ) {
			return false;
		}

		$search_keys = array_keys( $get );

		$search_match = implode( '|', self::$search_parameters );

		foreach( $search_keys as $search_key ) {

			// Analyze the search key $_GET parameter and see if it matches known GV args
			if( preg_match( '/('.$search_match.')/i', $search_key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add helpful GV links to the menu bar, like Edit Entry on single entry page.
	 *
	 * @return void
	 */
	function admin_bar_add_links() {
		global $wp_admin_bar;

		if( GFCommon::current_user_can_any('gravityforms_edit_entries') && $this->getSingleEntry() ) {

			$entry = $this->getEntry();

			$wp_admin_bar->add_menu( array(
				'id' => 'edit-entry',
				'title' => __('Edit Entry', 'gravityview'),
				'href' => admin_url( sprintf('admin.php?page=gf_entries&amp;screen_mode=edit&amp;view=entry&amp;id=%d&lid=%d', $entry['form_id'], $entry['id'] ) ),
			) );

		}

	}

	/**
	 * Remove "Edit Page" or "Edit View" links when on single entry pages
	 * @return void
	 */
	function admin_bar_remove_links() {

		// If we're on the single entry page, we don't want to cause confusion.
		if( is_admin() || ($this->getSingleEntry() && !$this->isGravityviewPostType() ) ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
		}
	}

	/**
	 * Callback function for add_shortcode()
	 *
	 * @access public
	 * @static
	 * @param mixed $atts
	 * @return null|string If admin, null. Otherwise, output of $this->render_view()
	 */
	public function shortcode( $atts, $content = NULL ) {

		// Don't process when saving post.
		if( is_admin() ) {
			return;
		}

		do_action( 'gravityview_log_debug', '[shortcode] $atts: ', $atts );

		return $this->render_view( $atts );
	}

	/**
	 * Filter the title for the single entry view
	 *
	 * @param  string $title   current title
	 * @param  int $passed_post_id Post ID
	 * @return string          (modified) title
	 */
	public function single_entry_title( $title, $passed_post_id = NULL ) {
		global $post;

		// If this is the directory view, return.
		if( !$this->getSingleEntry() ) {
			return $title;
		}

		$entry = $this->getEntry();

		// to apply the filter to the menu title and the meta tag <title> - outside the loop
		if( !apply_filters( 'gravityview/single/title/out_loop' , in_the_loop(), $entry ) ) {
			return $title;
		}

		// User reported WooCommerce doesn't pass two args.
		if( empty( $passed_post_id ) )  {
			return $title;
		}

		// Don't modify the title for anything other than the current view/post.
		// This is true for embedded shortcodes and Views.
		if( is_object($post) && (int)$post->ID !== (int)$passed_post_id ) {
			return $title;
		}

		$context_view_id = $this->get_context_view_id();

		if( $this->getGvOutputData()->has_multiple_views() && !empty( $context_view_id ) ) {
			$view_meta = $this->getGvOutputData()->get_view( $context_view_id );
		} else {
			foreach ( $this->getGvOutputData()->get_views() as $view_id => $view_data ) {
				if ( intval( $view_data['form_id'] ) === intval( $entry['form_id'] ) ) {
					$view_meta = $view_data;
					break;
				}
			}
		}

		if( !empty( $view_meta['atts']['single_title'] ) ) {
			// We are allowing HTML in the fields, so no escaping the output
			$title = GravityView_API::replace_variables( $view_meta['atts']['single_title'], $view_meta['form'], $entry );
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
		if( !did_action( 'loop_start' ) ) {

			do_action( 'gravityview_log_debug', '[insert_view_in_content] Not processing yet: loop_start hasn\'t run yet. Current action:', current_filter() );

			return $content;
		}

		//	We don't want this filter to run infinite loop on any post content fields
		remove_filter( 'the_content', array( $this, 'insert_view_in_content' ) );

		// Otherwise, this is called on the Views page when in Excerpt mode.
		if( is_admin() ) { return $content; }

		if( $this->isGravityviewPostType() ) {

			/** @since 1.7.4 */
			if( is_preview() && !gravityview_get_form_id( $this->post_id ) ) {
				$content .= __('When using a Start Fresh template, you must save the View before a Preview is available.', 'gravityview' );
			} else {
				foreach ( $this->getGvOutputData()->get_views() as $view_id => $data ) {
					$content .= $this->render_view( array( 'id' => $view_id ) );
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

		if( $this->isGravityviewPostType() ) {
			$open = false;
		}

		/**
		 * Whether to set comments to open or closed.
		 *
		 * @since  1.5.4
		 * @param  boolean $open Open or closed status
		 * @param  int $post_id Post ID to set comment status for
		 */
		$open = apply_filters( 'gravityview/comments_open', $open, $post_id );

		return $open;
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
		if( empty( $passed_args['id'] ) ) {
			do_action( 'gravityview_log_error', '[render_view] Returning; no ID defined.', $passed_args );
			return NULL;
		}

		// Solve problem when loading content via admin-ajax.php
		// @hack
		if( ! $this->getGvOutputData() ) {

			do_action( 'gravityview_log_error', '[render_view] gv_output_data not defined; parsing content.', $passed_args );

			$this->parse_content();
		}

		// Make 100% sure that we're dealing with a properly called situation
		if( !is_object( $this->getGvOutputData() ) || !is_callable( array( $this->getGvOutputData(), 'get_view' ) ) ) {

			do_action( 'gravityview_log_error', '[render_view] gv_output_data not an object or get_view not callable.', $this->getGvOutputData() );

			return NULL;
		}

		$view_id = $passed_args['id'];

		$view_data = $this->getGvOutputData()->get_view( $view_id, $passed_args );

		do_action( 'gravityview_log_debug', '[render_view] View Data: ', $view_data );

		do_action( 'gravityview_log_debug', '[render_view] Init View. Arguments: ', $passed_args );

		// The passed args were always winning, even if they were NULL.
		// This prevents that. Filters NULL, FALSE, and empty strings.
		$passed_args = array_filter( $passed_args, 'strlen' );

		//Override shortcode args over View template settings
		$atts = wp_parse_args( $passed_args, $view_data['atts'] );

		do_action( 'gravityview_log_debug', '[render_view] Arguments after merging with View settings: ', $atts );

		// It's password protected and you need to log in.
		if( post_password_required( $view_id ) ) {

			do_action( 'gravityview_log_error', sprintf('[render_view] Returning: View %d is password protected.', $view_id ) );

			// If we're in an embed or on an archive page, show the password form
			if( get_the_ID() !== $view_id ) { return get_the_password_form(); }

			// Otherwise, just get outta here
			return NULL;
		}

		ob_start();

		/**
		 * Set globals for templating
		 * @deprecated 1.6.2
		 */
		global $gravityview_view;

		$gravityview_view = new GravityView_View( $view_data );

		$post_id = !empty( $atts['post_id'] ) ? intval( $atts['post_id'] ) : $this->getPostId();

		$gravityview_view->setPostId( $post_id );

		if( ! $this->getSingleEntry() ) {

			// user requested Directory View
			do_action( 'gravityview_log_debug', '[render_view] Executing Directory View' );

			//fetch template and slug
			$view_slug =  apply_filters( 'gravityview_template_slug_'. $view_data['template_id'], 'table', 'directory' );
			do_action( 'gravityview_log_debug', '[render_view] View template slug: ', $view_slug );

			/**
			 * Disable fetching initial entries for views that don't need it (DataTables)
			 */
			$get_entries = apply_filters( 'gravityview_get_view_entries_'.$view_slug, true );

			/**
			 * Hide View data until search is performed
			 * @since 1.5.4
			 */
			if( !empty( $atts['hide_until_searched'] ) && !$this->isSearch() ) {
				$gravityview_view->setHideUntilSearched( true );
				$get_entries = false;
			}


			if( $get_entries ) {

				if( !empty( $atts['sort_columns'] ) ) {
					// add filter to enable column sorting
					add_filter( 'gravityview/template/field_label', array( $this, 'add_columns_sort_links' ) , 100, 3 );
				}

				$view_entries = self::get_view_entries( $atts, $view_data['form_id'] );

				do_action( 'gravityview_log_debug', sprintf( '[render_view] Get Entries. Found %s entries total, showing %d entries', $view_entries['count'], sizeof( $view_entries['entries'] ) ) );

			} else {

				$view_entries = array( 'count' => NULL, 'entries' => NULL, 'paging' => NULL );

				do_action( 'gravityview_log_debug', '[render_view] Not fetching entries because `gravityview_get_view_entries_'.$view_slug.'` is false');

			}

			$gravityview_view->setPaging( $view_entries['paging'] );
			$gravityview_view->setContext('directory');
			$sections = array( 'header', 'body', 'footer' );

		} else {

			// user requested Single Entry View
			do_action( 'gravityview_log_debug', '[render_view] Executing Single View' );

			do_action('gravityview_render_entry_'.$view_data['id']);

			$entry = $this->getEntry();

			// You are not permitted to view this entry.
			if( empty( $entry ) || !self::is_entry_approved( $entry, $atts ) ) {

				do_action( 'gravityview_log_debug', '[render_view] Entry does not exist. This may be because of View filters limiting access.');

				/**
				 * @since 1.6
				 */
				echo esc_attr( apply_filters( 'gravityview/render/entry/not_visible', __( 'You have attempted to view an entry that is not visible or may not exist.', 'gravityview') ) );

				return NULL;
			}

			// We're in single view, but the view being processed is not the same view the single entry belongs to.
			// important: do not remove this as it prevents fake attempts of displaying entries from other views/forms
			if( $this->getGvOutputData()->has_multiple_views() && $view_id != $this->get_context_view_id() ) {
				do_action( 'gravityview_log_debug', '[render_view] In single entry view, but the entry does not belong to this View. Perhaps there are multiple views on the page. View ID: '. $view_id );
				return NULL;
			}


			//fetch template and slug
			$view_slug =  apply_filters( 'gravityview_template_slug_'. $view_data['template_id'], 'table', 'single' );
			do_action( 'gravityview_log_debug', '[render_view] View single template slug: ', $view_slug );

			//fetch entry detail
			$view_entries['count'] = 1;
			$view_entries['entries'][] = $entry;
			do_action( 'gravityview_log_debug', '[render_view] Get single entry: ', $view_entries['entries'] );

			$back_link_label = isset( $atts['back_link_label'] ) ? $atts['back_link_label'] : NULL;

			// set back link label
			$gravityview_view->setBackLinkLabel( $back_link_label );
			$gravityview_view->setContext('single');
			$sections = array( 'single' );

		}

		// add template style
		self::add_style( $view_data['template_id'] );

		// Prepare to render view and set vars
		$gravityview_view->setEntries( $view_entries['entries'] );
		$gravityview_view->setTotalEntries( $view_entries['count'] );

		// If Edit
		if ( apply_filters( 'gravityview_is_edit_entry', false ) ) {

			do_action( 'gravityview_log_debug', '[render_view] Edit Entry ' );

			do_action( 'gravityview_edit_entry', $this->getGvOutputData() );

			return NULL;

		} else {
			// finaly we'll render some html
			$sections = apply_filters( 'gravityview_render_view_sections', $sections, $view_data['template_id'] );

			do_action( 'gravityview_log_debug', '[render_view] Sections to render: ', $sections );
			foreach( $sections as $section ) {

				do_action( 'gravityview_log_debug', '[render_view] Rendering '. $section . ' section.' );
				$gravityview_view->render( $view_slug, $section, false );
			}

		}

		//@todo: check why we need the IF statement vs. print the view id always.
		if( $this->isGravityviewPostType() || $this->isPostHasShortcode() ) {
			// Print the View ID to enable proper cookie pagination ?>
			<input type="hidden" class="gravityview-view-id" value="<?php echo $view_id; ?>">
<?php
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
	 * @todo  Compress into one
	 * @param  array      $args            View settings
	 * @param  array      $search_criteria Search being performed, if any
	 * @return array                       Modified `$search_criteria` array
	 */
	static function process_search_dates( $args, $search_criteria ) {

		foreach ( array( 'start_date', 'end_date' ) as $key ) {

			// Is the start date or end date set in the view or shortcode?
			// If so, we want to make sure that the search doesn't go outside the bounds defined.
			if( !empty( $args[ $key ] ) ) {

				// Get a timestamp and see if it's a valid date format
				$date = strtotime( $args[ $key ] );

				// The date was invalid
				if( empty( $date ) ) {
					do_action( 'gravityview_log_error', '[process_search_dates] Invalid '.$key.' date format: ' . $args[ $key ]);
					continue;
				}

				if(
					// If there is no search being performed
					empty( $search_criteria[ $key ] ) ||

					// Or if there is a search being performed
					( !empty( $search_criteria[ $key ] )
						// And the search is for entries before the start date defined by the settings
						&& (
							( $key === 'start_date' && strtotime( $search_criteria[ $key ] ) < $date ) ||
							( $key === 'end_date' && strtotime( $search_criteria[ $key ] ) > $date )
						)
					)
				) {
					// Then we override the search and re-set the start date
					$search_criteria[ $key ] = date( 'Y-m-d H:i:s' , $date );
				}
			}

		}

		return $search_criteria;
	}


	/**
	 * Process the approved only search criteria according to the View settings
	 *
	 * @param  array      $args            View settings
	 * @param  array      $search_criteria Search being performed, if any
	 * @return array                       Modified `$search_criteria` array
	 */
	public static function process_search_only_approved( $args, $search_criteria ) {

		if( !empty( $args['show_only_approved'] ) ) {
			$search_criteria['field_filters'][] = array( 'key' => 'is_approved', 'value' => 'Approved' );
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
	 *
	 * @param array $entry  Entry object
	 * @param array $args   View settings (optional)
	 *
	 * @return bool
	 */
	public static function is_entry_approved( $entry, $args = array() ) {

		if( empty( $entry['id'] ) || ( array_key_exists( 'show_only_approved', $args ) && !$args['show_only_approved'] ) ) {
			// is implicitly approved if entry is null or View settings doesn't require to check for approval
			return true;
		}

		$is_approved = gform_get_meta( $entry['id'], 'is_approved' );

		if( $is_approved ) {
			return true;
		}

		return false;
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

		// Search Criteria
		$search_criteria = apply_filters( 'gravityview_fe_search_criteria', array( 'field_filters' => array() ), $form_id );
		do_action( 'gravityview_log_debug', '[get_search_criteria] Search Criteria after hook gravityview_fe_search_criteria: ', $search_criteria );

		// implicity search
		if( !empty( $args['search_value'] ) ) {

			// Search operator options. Options: `is` or `contains`
			$operator = !empty( $args['search_operator'] ) && in_array( $args['search_operator'], array('is', 'isnot', '>', '<', 'contains' ) ) ? $args['search_operator'] : 'contains';



			$search_criteria['field_filters'][] = array(
				'key' => rgget('search_field', $args ), // The field ID to search
				'value' => esc_attr( $args['search_value'] ), // The value to search
				'operator' => $operator,
			);
		}

		do_action( 'gravityview_log_debug', '[get_search_criteria] Search Criteria after implicity search: ', $search_criteria );

		// Handle setting date range
		$search_criteria = self::process_search_dates( $args, $search_criteria );

		do_action( 'gravityview_log_debug', '[get_search_criteria] Search Criteria after date params: ', $search_criteria );

		// remove not approved entries
		$search_criteria = self::process_search_only_approved( $args, $search_criteria );

		// Only show active listings
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
	 * @param mixed $args
	 * @param int $form_id Gravity Forms Form ID
	 * @return array Associative array with `count`, `entries`, and `paging` keys. `count` has the total entries count, `entries` is an array with Gravity Forms full entry data, `paging` is an array with `offset` and `page_size` keys
	 */
	public static function get_view_entries( $args, $form_id ) {

		do_action( 'gravityview_log_debug', '[get_view_entries] init' );
		// start filters and sorting

		/**
		 * Process search parameters
		 * @var array
		 */
		$search_criteria = self::get_search_criteria( $args, $form_id );

		// Paging & offset
		$page_size = !empty( $args['page_size'] ) ? intval( $args['page_size'] ) : apply_filters( 'gravityview_default_page_size', 25 );

		if( $page_size === -1 ) {
			$page_size = PHP_INT_MAX;
		}

		if( isset( $args['offset'] ) ) {
			$offset = intval( $args['offset'] );
		} else {
			$curr_page = empty( $_GET['pagenum'] ) ? 1 : intval( $_GET['pagenum'] );
			$offset = ( $curr_page - 1 ) * $page_size;
		}

		$paging = array(
			'offset' => $offset,
			'page_size' => $page_size
		);

		do_action( 'gravityview_log_debug', '[get_view_entries] Paging: ', $paging );


		// Sorting
		$sorting = self::updateViewSorting( $args, $form_id );

		$parameters = array(
			'search_criteria' => $search_criteria,
			'sorting' => $sorting,
			'paging' => $paging,
			'cache' => isset( $args['cache'] ) ? $args['cache'] : true,
		);

		/**
		 * Filter get entries criteria
		 *
		 * Passes and returns array with `search_criteria`, `sorting` and `paging` keys.
		 *
		 * @var array
		 */
		$parameters = apply_filters( 'gravityview_get_entries', apply_filters( 'gravityview_get_entries_'.$args['id'], $parameters, $args, $form_id ), $args, $form_id );

		do_action( 'gravityview_log_debug', '[get_view_entries] $parameters passed to gravityview_get_entries(): ', $parameters );

		//fetch entries
		$count = 0;
		$entries = gravityview_get_entries( $form_id, $parameters, $count );

		do_action( 'gravityview_log_debug', sprintf( '[get_view_entries] Get Entries. Found: %s entries', $count ), $entries );

		/**
		 * Filter the entries output to the View
		 * @deprecated since 1.5.2
		 * @param array $args View settings associative array
		 * @var array
		 */
		$entries = apply_filters( 'gravityview_view_entries', $entries, $args );

		/**
		 * Filter the entries output to the View
		 *
		 * @param array  associative array containing count, entries & paging
		 * @param array $args View settings associative array
		 *
		 * @since 1.5.2
		 */
		return apply_filters( 'gravityview/view/entries', compact( 'count', 'entries', 'paging' ), $args );

	}


	/**
	 * Updates the View sorting criteria
	 *
	 * @since 1.7
	 *
	 * @param $args View settings. Required to have `sort_field` and `sort_direction` keys
	 * @param int $form_id The ID of the form used to sort
	 * @return array $sorting Array with `key`, `direction` and `is_numeric` keys
	 */
	public static function updateViewSorting( $args, $form_id ) {

		$sorting = array();
		$sort_field_id = isset( $_GET['sort'] ) ? $_GET['sort'] : rgar( $args, 'sort_field' );
		$sort_direction = isset( $_GET['dir'] ) ? $_GET['dir'] : rgar( $args, 'sort_direction' );

		$sort_field_id = self::_override_sorting_id_by_field_type( $sort_field_id, $form_id );

		if( !empty( $sort_field_id ) ) {
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
	 * @since 1.7.4
	 *
	 * @param int|string $sort_field_id Field used for sorting (`id` or `1.2`)
	 * @param int $form_id GF Form ID
	 *
	 * @return string Possibly modified sorting ID
	 */
	private static function _override_sorting_id_by_field_type( $sort_field_id, $form_id ) {

		$form = GFAPI::get_form( $form_id );

		$sort_field = GFFormsModel::get_field( $form, $sort_field_id );

		switch( $sort_field['type'] ) {
			case 'name':
				// Sorting by full name, not first, last, etc.
				if( floatval( $sort_field_id ) === floor( $sort_field_id ) ) {

					/**
					 * Override how to sort when sorting full name.
					 *
					 * @since 1.7.4
					 *
					 * @param string $name_part `first` or `last` (default: `first`)
					 * @param string $sort_field_id Field used for sorting
					 * @param int $form_id GF Form ID
					 */
					$name_part = apply_filters('gravityview/sorting/full-name', 'first', $sort_field_id, $form_id );

					if( strtolower( $name_part ) === 'last' ) {
						$sort_field_id .= '.6';
					} else {
						$sort_field_id .= '.3';
					}

				}
				break;
		}

		return $sort_field_id;
	}

	/**
	 * Verify if user requested a single entry view
	 * @return boolean|string false if not, single entry slug if true
	 */
	public static function is_single_entry() {

		$var_name = GravityView_Post_Types::get_entry_var_name();

		$single_entry = get_query_var( $var_name );

		/**
		 * Modify the entry that is being displayed.
		 *
		 * @internal Should only be used by things like the oEmbed functionality.
		 * @since 1.6
		 */
		$single_entry = apply_filters('gravityview/is_single_entry', $single_entry );

		if( empty( $single_entry ) ){
			return false;
		} else {
			return $single_entry;
		}
	}


	/**
	 * Register styles and scripts
	 *
	 * @filter  gravity_view_lightbox_script Modify the lightbox JS slug. Default: `thickbox`
	 * @filter  gravity_view_lightbox_style Modify the thickbox CSS slug. Default: `thickbox`
	 * @access public
	 * @return void
	 */
	public function add_scripts_and_styles() {
		global $post, $posts;

		//foreach ($posts as $p) {

		// enqueue template specific styles
		if( $this->getGvOutputData() ) {

			$views = $this->getGvOutputData()->get_views();

			$js_localization = array(
				'cookiepath' => COOKIEPATH,
				'clear' => _x('Clear', 'Clear all data from the form', 'gravityview'),
				'reset' => _x('Reset', 'Reset the search form to the state that existed on page load', 'gravityview'),
			);

			foreach ( $views as $view_id => $data ) {

				// By default, no thickbox
				$js_dependencies = array( 'jquery', 'gravityview-jquery-cookie' );
				$css_dependencies = array();

				// If the thickbox is enqueued, add dependencies
				if( !empty( $data['atts']['lightbox'] ) ) {
					$js_dependencies[] = apply_filters( 'gravity_view_lightbox_script', 'thickbox' );
					$css_dependencies[] = apply_filters( 'gravity_view_lightbox_style', 'thickbox' );
				}

				wp_register_script( 'gravityview-jquery-cookie', plugins_url('includes/lib/jquery-cookie/jquery_cookie.js', GRAVITYVIEW_FILE), array( 'jquery' ), GravityView_Plugin::version, true );

				$script_debug = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

				wp_register_script( 'gravityview-fe-view', plugins_url('assets/js/fe-views'.$script_debug.'.js', GRAVITYVIEW_FILE), apply_filters('gravityview_js_dependencies', $js_dependencies ) , GravityView_Plugin::version, true );

				wp_enqueue_script( 'gravityview-fe-view' );

				/**
				 * Modify the array passed to wp_localize_script
				 * @var array Contains `datepicker` key, which passes settings to the JS file
				 */
				$js_localization = apply_filters('gravityview_js_localization', $js_localization, $data );

				if( !empty( $data['atts']['sort_columns'] ) ) {
					wp_enqueue_style( 'gravityview_font', plugins_url('assets/css/font.css', GRAVITYVIEW_FILE ), $css_dependencies, GravityView_Plugin::version, 'all' );
				}

				wp_enqueue_style( 'gravityview_default_style', plugins_url('templates/css/gv-default-styles.css', GRAVITYVIEW_FILE), $css_dependencies, GravityView_Plugin::version, 'all' );

				self::add_style( $data['template_id'] );

			}

			if( current_filter() === 'wp_print_footer_scripts' ) {
				wp_localize_script( 'gravityview-fe-view', 'gvGlobals', $js_localization );
			}

		}
	}

	/**
	 * Add template extra style if exists
	 * @param string $template_id
	 */
	public static function add_style( $template_id ) {

		if( !empty( $template_id ) && wp_style_is( 'gravityview_style_' . $template_id, 'registered' ) ) {
			do_action( 'gravityview_log_debug', sprintf( '[add_style] Adding extra template style for %s', $template_id ) );
			wp_enqueue_style( 'gravityview_style_' . $template_id );
		} else if( empty( $template_id ) ) {
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
	 * @param $label Field label
	 * @param $field Field settings
	 *
	 * @return string Field Label
	 */
	public function add_columns_sort_links( $label = '', $field, $form ) {

		if( !$this->is_field_sortable( $field['id'], $form ) ) {
			return $label;
		}

		$sorting = GravityView_View::getInstance()->getSorting();

		$class = 'gv-sort icon';

		$sort_field_id = self::_override_sorting_id_by_field_type( $field['id'], $form['id'] );

		$sort_args = array(
			'sort' => $field['id'],
			'dir' => 'asc'
		);

		if( !empty( $sorting['key'] ) && (string)$sort_field_id === (string)$sorting['key'] ) {
			//toggle sorting direction.
			if( $sorting['direction'] == 'asc' ) {
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

		return '<a href="'. esc_url( $url ) .'" class="'. $class .'" ></a>&nbsp;'. $label;

	}

	/**
	 * Checks if field (column) is sortable
	 *
	 * @param string $field Field settings
	 * @param $form Gravity Forms form object
	 *
	 * @since 1.7
	 *
	 * @return bool True: Yes, field is sortable; False: not sortable
	 */
	public function is_field_sortable( $field_id = '' , $form ) {

		$not_sortable = array(
			'entry_link',
			'edit_link',
			'delete_link',
			'custom'
		);

		/**
		 * Modify what fields should never be sortable.
		 * @since 1.7
		 */
		$not_sortable = apply_filters( 'gravityview/sortable/field_blacklist', $not_sortable, $field_id, $form );

		if( in_array( $field_id, $not_sortable ) ) {
			return false;
		}

		return apply_filters( "gravityview/sortable/formfield_{$form['id']}_{$field_id}", apply_filters( "gravityview/sortable/field_{$field_id}", true, $form ) );

	}

}

GravityView_frontend::getInstance();



