<?php
/**
 * GravityView Frontend functions
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */


class GravityView_frontend {

	/**
	 * Regex strings that are used to determine whether the current request is a GravityView search or not.
	 *
	 * @see GravityView_frontend::is_searching()
	 * @since 1.7.4.1
	 * @var array
	 */
	private static $search_parameters = array( 'gv_search', 'gv_start', 'gv_end', 'gv_id', 'gv_by', 'filter_*' );

	/**
	 * Is the currently viewed post a `gravityview` post type?
	 *
	 * @var boolean
	 */
	var $is_gravityview_post_type = false;

	/**
	 * Does the current post have a `[gravityview]` shortcode?
	 *
	 * @var boolean
	 */
	var $post_has_shortcode = false;

	/**
	 * The Post ID of the currently viewed post. Not necessarily GV
	 *
	 * @var int
	 */
	var $post_id = null;

	/**
	 * Are we currently viewing a single entry?
	 * If so, the int value of the entry ID. Otherwise, false.
	 *
	 * @var int|boolean
	 */
	var $single_entry = false;

	/**
	 * If we are viewing a single entry, the entry data
	 *
	 * @var array|false
	 */
	var $entry = false;

	/**
	 * When displaying the single entry we should always know to which View it belongs (the context is everything!)
	 *
	 * @var null
	 */
	var $context_view_id = null;

	/**
	 * The View is showing search results
	 *
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
		// WordPress 6.9+ compatibility: disable on-demand block asset loading.
		$this->maybe_disable_block_assets_on_demand();

		add_action( 'wp', array( $this, 'parse_content' ), 11 );
		add_filter( 'render_block', array( $this, 'detect_views_in_block_content' ) );
		add_filter( 'parse_query', array( $this, 'parse_query_fix_frontpage' ), 10 );
		add_action( 'template_redirect', array( $this, 'set_entry_data' ), 1 );

		// Enqueue scripts and styles after GravityView_Template::register_styles().
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_and_styles' ), 20 );

		// Enqueue and print styles in the footer. Added 1 priority so stuff gets printed at 10 priority.
		add_action( 'wp_print_footer_scripts', array( $this, 'add_scripts_and_styles' ), 1 );

		add_filter( 'the_title', array( $this, 'single_entry_title' ), 1, 2 );
		add_filter( 'comments_open', array( $this, 'comments_open' ), 10, 2 );

		add_action( 'gravityview_after', array( $this, 'context_not_configured_warning' ) );
		add_filter( 'gravityview/template/text/no_entries', array( $this, 'filter_no_entries_output' ), 10, 3 );
	}

	/**
	 * Disable on-demand block asset loading in WordPress 6.9+ for classic themes.
	 *
	 * WordPress 6.9 introduced on-demand block asset loading for classic themes, which uses
	 * output buffering to detect which blocks are rendered and then "hoists" late-enqueued
	 * styles back to the <head>. We enqueue styles during shortcode/block rendering
	 * (after wp_head) and in the footer, which doesn't work properly with this new change.
	 *
	 * @see https://core.trac.wordpress.org/ticket/64099
	 * @see https://make.wordpress.org/core/2025/11/18/wordpress-6-9-frontend-performance-field-guide/
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function maybe_disable_block_assets_on_demand() {
		global $wp_version;

		// Only apply for WordPress 6.9+ on the frontend.
		if ( version_compare( $wp_version, '6.9-alpha', '<' ) || is_admin() ) {
			return;
		}

		/**
		 * Controls whether on-demand block asset loading should be disabled in WordPress 6.9+.
		 *
		 * WordPress 6.9 introduced on-demand block asset loading for classic themes, which can
		 * prevent GravityView styles from loading properly.
		 *
		 * @filter `gk/gravityview/compatibility/block-assets-on-demand`
		 *
		 * @since 2.49
		 *
		 * @param bool $disable Whether to disable on-demand block asset loading. Default: true.
		 */
		$disable_on_demand = apply_filters( 'gk/gravityview/compatibility/block-assets-on-demand', true );

		if ( ! $disable_on_demand ) {
			return;
		}

		add_filter( 'should_load_block_assets_on_demand', '__return_false' );
	}

	/**
	 * Get the one true instantiated self
	 *
	 * @return GravityView_frontend
	 */
	public static function getInstance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
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
	 * @param \GravityView_View_Data $gv_output_data
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
	 *
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
	 *
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
	 * @param null $view_id
	 */
	public function set_context_view_id( $view_id = null ) {
		$multiple_views = $this->getGvOutputData() && $this->getGvOutputData()->has_multiple_views();

		if ( ! empty( $view_id ) ) {

			$this->context_view_id = (int) $view_id;

		} elseif ( isset( $_GET['gvid'] ) && $multiple_views ) {
			/**
			 * used on a has_multiple_views context
			 *
			 * @see GravityView_API::entry_link
			 */
			$this->context_view_id = (int) $_GET['gvid'];

		} elseif ( ! $multiple_views ) {
			$array_keys            = array_keys( $this->getGvOutputData()->get_views() );
			$this->context_view_id = (int) array_pop( $array_keys );
			unset( $array_keys );
		}
	}

	/**
	 * Returns the the view_id context when page contains multiple embedded views or displaying single entry view
	 *
	 * @since 1.5.4
	 *
	 * @return int|null
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
		$show_on_front = ( 'page' === get_option( 'show_on_front' ) );
		$front_page_id = get_option( 'page_on_front' );

		if ( $is_front_page && $show_on_front && $front_page_id ) {

			// Force to be an array, potentially a query string ( entry=16 )
			$_query = wp_parse_args( $query->query );

			// pagename can be set and empty depending on matched rewrite rules. Ignore an empty pagename.
			if ( isset( $_query['pagename'] ) && '' === $_query['pagename'] ) {
				unset( $_query['pagename'] );
			}

			// this is where will break from core WordPress
			/** @internal Don't use this filter; it will be unnecessary soon - it's just a patch for specific use case */
			$ignore    = apply_filters( 'gravityview/internal/ignored_endpoints', array( 'preview', 'page', 'paged', 'cpage' ), $query );
			$endpoints = \GV\Utils::get( $wp_rewrite, 'endpoints' );
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
				if ( self::is_single_entry() ) {
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
	 * Detect GV Views in parsed Gutenberg block content
	 *
	 * @since 2.13.4
	 *
	 * @see   \WP_Block::render()
	 *
	 * @param string $block_content Gutenberg block content
	 *
	 * @return false|string
	 *
	 * @todo Once we stop using the legacy `GravityView_frontend::parse_content()` method to detect Views in post content, this code should either be dropped or promoted to some core class given its applicability to other themes/plugins
	 */
	public function detect_views_in_block_content( $block_content ) {
		if ( ! class_exists( 'GV\View_Collection' ) || ! class_exists( 'GV\View' ) ) {
			return $block_content;
		}

		$gv_view_data = GravityView_View_Data::getInstance();

		$views = \GV\View_Collection::from_content( $block_content );

		foreach ( $views->all() as $view ) {
			if ( ! $gv_view_data->views->contains( $view->ID ) ) {
				$gv_view_data->views->add( $view );
			}
		}

		return $block_content;
	}

	/**
	 * Read the $post and process the View data inside
	 *
	 * @param  array $wp Passed in the `wp` hook. Not used.
	 * @return void
	 */
	public function parse_content( $wp = array() ) {
		global $post;

		// If in admin and NOT AJAX request, get outta here.
		if ( gravityview()->request->is_admin() ) {
			return;
		}

		$is_gv_post_type = 'gravityview' === get_post_type( $post );

		// Calculate requested Views
		$post_content = ! empty( $post->post_content ) ? $post->post_content : null;

		if ( $post_content && ! $is_gv_post_type && function_exists( 'parse_blocks' ) && preg_match_all( '/"ref":\d+/', $post_content ) ) {
			$blocks = parse_blocks( $post_content );

			foreach ( $blocks as $block ) {
				if ( empty( $block['attrs']['ref'] ) ) {
					continue;
				}

				$block_post = get_post( $block['attrs']['ref'] );

				if ( $block_post ) {
					$post_content .= $block_post->post_content;
				}
			}

			$this->setGvOutputData( GravityView_View_Data::getInstance( $post_content ) );
		} else {
			$this->setGvOutputData( GravityView_View_Data::getInstance( $post ) );
		}

		// !important: we need to run this before getting single entry (to kick the advanced filter)
		$this->set_context_view_id();

		$this->setIsGravityviewPostType( $is_gv_post_type );

		$post_id = $this->getPostId() ? $this->getPostId() : ( isset( $post ) ? $post->ID : null );
		$this->setPostId( $post_id );
		$post_has_shortcode = $post_content ? gravityview_has_shortcode_r( $post_content, 'gravityview' ) : false;
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

		if ( 'post' === $search_method ) {
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
	 * @param  string $passed_title  Current title
	 * @param  int    $passed_post_id Post ID
	 * @return string (modified) title
	 */
	public function single_entry_title( $passed_title, $passed_post_id = null ) {
		global $post;

		// Since this is a public method, it can be called outside of the plugin. Don't assume things have been loaded properly.
		if ( ! class_exists( '\GV\Entry' ) ) {
			return $passed_title;
		}

		$gventry = gravityview()->request->is_entry();

		// If this is the directory view, return.
		if ( ! $gventry ) {
			return $passed_title;
		}

		$entry = $gventry->as_entry();

		/**
		 * Apply the Single Entry Title filter outside the WordPress loop?
		 *
		 * @param boolean $in_the_loop Whether to apply the filter to the menu title and the meta tag <title> - outside the loop
		 * @param array $entry Current entry
		 */
		$apply_outside_loop = apply_filters( 'gravityview/single/title/out_loop', in_the_loop(), $entry );

		if ( ! $apply_outside_loop ) {
			return $passed_title;
		}

		// WooCommerce doesn't $post_id
		if ( empty( $passed_post_id ) ) {
			return $passed_title;
		}

		// Don't modify the title for anything other than the current view/post.
		// This is true for embedded shortcodes and Views.
		if ( is_object( $post ) && (int) $post->ID !== (int) $passed_post_id ) {
			return $passed_title;
		}

		$view = gravityview()->request->is_view( true );

		if ( $view ) {
			return $this->_get_single_entry_title( $view, $entry, $passed_title );
		}

		$_gvid = \GV\Utils::_GET( 'gvid', null );

		// $_GET['gvid'] is set; we know what View to render
		if ( $_gvid ) {

			$view = \GV\View::by_id( $_gvid );

			return $this->_get_single_entry_title( $view, $entry, $passed_title );
		}

		global $post;

		if ( ! $post ) {
			return $passed_title;
		}

		$view_collection = \GV\View_Collection::from_post( $post );

		// We have multiple Views, but no gvid...this isn't valid security
		if ( 1 < $view_collection->count() ) {
			return $passed_title;
		}

		return $this->_get_single_entry_title( $view_collection->first(), $entry, $passed_title );
	}

	/**
	 * Returns the single entry title for a View with variables replaced and shortcodes parsed
	 *
	 * @since 2.7.2
	 *
	 * @param \GV\View|null $view
	 * @param array         $entry
	 * @param string        $passed_title
	 *
	 * @return string
	 */
	private function _get_single_entry_title( $view, $entry = array(), $passed_title = '' ) {

		if ( ! $view ) {
			return $passed_title;
		}

		/**
		 * Override whether to check entry display rules against filters.
		 *
		 * @internal This might change in the future! Don't rely on it.
		 * @since 2.7.2
		 * @param bool $check_entry_display Check whether the entry is visible for the current View configuration. Default: true.
		 * @param array $entry Gravity Forms entry array
		 * @param \GV\View $view The View
		 */
		$check_entry_display = apply_filters( 'gravityview/single/title/check_entry_display', true, $entry, $view );

		if ( $check_entry_display ) {

			$check_display = GVCommon::check_entry_display( $entry, $view );

			if ( is_wp_error( $check_display ) ) {
				return $passed_title;
			}
		}

		$title = $view->settings->get( 'single_title', $passed_title );

		$form = GVCommon::get_form( $entry['form_id'] );

		// We are allowing HTML in the fields, so no escaping the output
		$title = GravityView_API::replace_variables( $title, $form, $entry );

		$title = do_shortcode( $title );

		return $title;
	}


	/**
	 * In case View post is called directly, insert the view in the post content
	 *
	 * @deprecated {@see \GV\View::content()} instead.
	 *
	 * @static
	 * @param mixed $content
	 * @return string Add the View output into View CPT content
	 */
	public function insert_view_in_content( $content ) {
		gravityview()->log->notice( '\GravityView_frontend::insert_view_in_content is deprecated. Use \GV\View::content()' );
		return \GV\View::content( $content );
	}

	/**
	 * Disable comments on GravityView post types
	 *
	 * @param  boolean $open    existing status
	 * @param  int     $post_id Post ID
	 * @return boolean
	 */
	public function comments_open( $open, $post_id ) {

		if ( $this->isGravityviewPostType() ) {
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
	 * Display a warning when a View has not been configured
	 *
	 * @since 1.19.2
	 * @used-by \GV\Renderer::maybe_print_notices()
	 * @depecated 2.0
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

		switch ( $context ) {
			case 'directory':
				$tab = esc_html__( 'Multiple Entries', 'gk-gravityview' );
				break;
			case 'edit':
				$tab = esc_html__( 'Edit Entry', 'gk-gravityview' );
				break;
			case 'single':
			default:
				$tab = esc_html__( 'Single Entry', 'gk-gravityview' );
				break;
		}

		$title       = sprintf( esc_html_x( 'The %s layout has not been configured.', 'Displayed when a View is not configured. %s is replaced by the tab label', 'gk-gravityview' ), $tab );
		$edit_link   = admin_url( sprintf( 'post.php?post=%d&action=edit#%s-view', $view_id, $context ) );
		$action_text = sprintf( esc_html__( 'Add fields to %s', 'gk-gravityview' ), $tab );
		$message     = esc_html__( 'You can only see this message because you are able to edit this View.', 'gk-gravityview' );

		$image  = sprintf( '<img alt="%s" src="%s" style="margin-top: 10px;" />', $tab, esc_url( plugins_url( sprintf( 'assets/images/tab-%s.png', $context ), GRAVITYVIEW_FILE ) ) );
		$output = sprintf( '<h3>%s <strong><a href="%s">%s</a></strong></h3><p>%s</p>', $title, esc_url( $edit_link ), $action_text, $message );

		echo GVCommon::generate_notice( $output . $image, 'gv-error error', 'edit_gravityview', $view_id );
	}

	/**
	 * Modify what happens when there are no entries in the View based on View settings.
	 *
	 * @since 2.17
	 *
	 * @param string                    $output The existing 'No Entries' text.
	 * @param boolean                   $is_search Is the current page a search result, or just a multiple entries screen?
	 * @param \GV\Template_Context|null $context The context, if available.
	 *
	 * @return string|void If search, existing text. If form,  new 'No Entries' text.
	 */
	public function filter_no_entries_output( $no_entries_text, $is_search, $context = null ) {

		// Only proceed if it's not a search and we aren't using legacy paths.
		if ( $is_search || ! $context instanceof \GV\Template_Context ) {
			return $no_entries_text;
		}

		$no_entries_option = (int) $context->view->settings->get( 'no_entries_options', 0 );

		// Default is to display the message.
		if ( empty( $no_entries_option ) ) {
			return $no_entries_text;
		}

		if ( 1 === $no_entries_option ) {
			$form_id = (int) $context->view->settings->get( 'no_entries_form' );

			if ( ! empty( $form_id ) ) {

				$output = '
<style>
	.gv-table-multiple-container:has( .gv-no-results-form ) th,
	.gv-table-multiple-container:has( .gv-no-results-form ) td {
		padding: 0;
	}
	.gv-table-multiple-container:has( .gv-no-results-form ) thead,
	.gv-table-multiple-container:has( .gv-no-results-form ) tfoot,
	.gv-table-multiple-container:has( .gv-no-results-form ) + .gv-powered-by {
		display: none;
	}
</style>';

				$form_title = $context->view->settings->get( 'no_entries_form_title', true );
				$form_desc  = $context->view->settings->get( 'no_entries_form_description', true );

				$output .= \GFForms::get_form( $form_id, $form_title, $form_desc );

				return $output;
			}
		}

		if ( 2 === $no_entries_option ) {
			$no_entries_redirect = $context->view->settings->get( 'no_entries_redirect' );

			if ( $no_entries_redirect ) {
				$redirect_url = GFCommon::replace_variables( $no_entries_redirect, $context->form, $context->entry, false, false, false, 'text' );

				if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
					return strtr(
						// Translators: Do not translate the [url] placeholder.
						esc_html__( 'No entries found. This page will redirect to "[url]" when opened in a browser.', 'gk-gravityview' ),
						[ '[url]' => $redirect_url ]
					);
				}

				$redirected = wp_redirect( $redirect_url );

				if ( defined( 'DOING_GRAVITYVIEW_TESTS' ) && DOING_GRAVITYVIEW_TESTS ) {
					return $redirected;
				}

				exit;
			}
		}

		return $no_entries_text;
	}


	/**
	 * Core function to render a View based on a set of arguments
	 *
	 * @static
	 * @param array $passed_args {
	 *
	 *      Settings for rendering the View
	 *
	 *      @type int $id View id
	 *      @type int $page_size Number of entries to show per page
	 *      @type string $sort_field Form field id to sort
	 *      @type string $sort_direction Sorting direction ('ASC', 'DESC', or 'RAND')
	 *      @type string $start_date - Ymd
	 *      @type string $end_date - Ymd
	 *      @type string $class - assign a html class to the view
	 *      @type string $offset (optional) - This is the start point in the current data set (0 index based).
	 * }
	 *
	 * @deprecated Use \GV\View_Renderer
	 *
	 * @return string|null HTML output of a View, NULL if View isn't found
	 */
	public function render_view( $passed_args ) {
		gravityview()->log->notice( '\GravityView_frontend::render_view is deprecated. Use \GV\View_Renderer etc.' );

		/**
		 * We can use a shortcode here, since it's pretty much the same.
		 *
		 * But we do need to check embed permissions, since shortcodes don't do this.
		 */

		if ( ! $view = gravityview()->views->get( $passed_args ) ) {
			return null;
		}

		$view->settings->update( $passed_args );

		$direct_access = apply_filters( 'gravityview_direct_access', true, $view->ID );
		$embed_only    = $view->settings->get( 'embed_only' );

		if ( ! $direct_access || ( $embed_only && ! GVCommon::has_cap( 'read_private_gravityviews' ) ) ) {
			return __( 'You are not allowed to view this content.', 'gk-gravityview' );
		}

		$shortcode = new \GV\Shortcodes\gravityview();
		return $shortcode->callback( $passed_args );
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
	 * @param  array $args            View settings
	 * @param  array $search_criteria Search being performed, if any
	 * @return array                       Modified `$search_criteria` array
	 */
	public static function process_search_dates( $args, $search_criteria = array() ) {

		$return_search_criteria = $search_criteria;

		foreach ( array( 'start_date', 'end_date' ) as $key ) {

			// Is the start date or end date set in the view or shortcode?
			// If so, we want to make sure that the search doesn't go outside the bounds defined.
			if ( ! empty( $args[ $key ] ) ) {

				// Get a timestamp and see if it's a valid date format
				$date = strtotime( $args[ $key ], GFCommon::get_local_timestamp() );

				// The date was invalid
				if ( empty( $date ) ) {
					gravityview()->log->error(
						' Invalid {key} date format: {format}',
						array(
							'key'    => $key,
							'format' => $args[ $key ],
						)
					);
					continue;
				}

				// The format that Gravity Forms expects for start_date and day-specific (not hour/second-specific) end_date
				$datetime_format               = 'Y-m-d H:i:s';
				$search_is_outside_view_bounds = false;

				if ( ! empty( $search_criteria[ $key ] ) ) {

					$search_date = strtotime( $search_criteria[ $key ], GFCommon::get_local_timestamp() );

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
							$datetime_format               = gravityview_is_valid_datetime( $args[ $key ] ) ? 'Y-m-d' : $datetime_format;
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
					$return_search_criteria[ $key ] = date_i18n( $datetime_format, $date, true );
				}
			}
		}

		if ( isset( $return_search_criteria['start_date'] ) && isset( $return_search_criteria['end_date'] ) ) {
			// The start date is AFTER the end date. This will result in no results, but let's not force the issue.
			if ( strtotime( $return_search_criteria['start_date'] ) > strtotime( $return_search_criteria['end_date'] ) ) {
				gravityview()->log->error( 'Invalid search: the start date is after the end date.', array( 'data' => $return_search_criteria ) );
			}
		}

		return $return_search_criteria;
	}


	/**
	 * Process the approved only search criteria according to the View settings
	 *
	 * @param  array $args            View settings
	 * @param  array $search_criteria Search being performed, if any
	 * @return array                       Modified `$search_criteria` array
	 */
	public static function process_search_only_approved( $args, $search_criteria ) {

		/** @since 1.19 */
		if ( ! empty( $args['admin_show_all_statuses'] ) && GVCommon::has_cap( 'gravityview_moderate_entries' ) ) {
			gravityview()->log->debug( 'User can moderate entries; showing all approval statuses' );
			return $search_criteria;
		}

		if ( ! empty( $args['show_only_approved'] ) ) {
			$search_criteria['field_filters'][] = [
				'key'      => GravityView_Entry_Approval::meta_key,
				'operator' => '=',
				'value'    => GravityView_Entry_Approval_Status::APPROVED,
			];

			$search_criteria['field_filters']['mode'] = 'all'; // force all the criterias to be met

			gravityview()->log->debug( '[process_search_only_approved] Search Criteria if show only approved: ', array( 'data' => $search_criteria ) );
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
		if ( ! empty( $args['admin_show_all_statuses'] ) && GVCommon::has_cap( 'gravityview_moderate_entries' ) ) {
			gravityview()->log->debug( 'User can moderate entries, so entry is approved for viewing' );
			return true;
		}

		$is_approved = gform_get_meta( $entry['id'], GravityView_Entry_Approval::meta_key );

		return GravityView_Entry_Approval_Status::is_approved( $is_approved );
	}

	/**
	 * Parse search criteria for a entries search.
	 *
	 * array(
	 *  'search_field' => 1, // ID of the field
	 *  'search_value' => '', // Value of the field to search
	 *  'search_operator' => 'contains', // 'is', 'isnot', '>', '<', 'contains'
	 *  'show_only_approved' => 0 or 1 // Boolean
	 * )
	 *
	 * @param  array $args    Array of args
	 * @param  int   $form_id Gravity Forms form ID
	 * @return array          Array of search parameters, formatted in Gravity Forms mode, using `status` key set to "active" by default, `field_filters` array with `key`, `value` and `operator` keys.
	 */
	public static function get_search_criteria( $args, $form_id ) {
		/**
		 * Compatibility with filters hooking in `gravityview_search_criteria` instead of `gravityview_fe_search_criteria`.
		 */
		$criteria        = apply_filters( 'gravityview_search_criteria', array(), array( $form_id ), \GV\Utils::get( $args, 'id' ) );
		$search_criteria = isset( $criteria['search_criteria'] ) ? $criteria['search_criteria'] : array( 'field_filters' => array() );

		/**
		 * Modify the search criteria.
		 *
		 * @see GravityView_Widget_Search::filter_entries Adds the default search criteria
		 * @param array $search_criteria Empty `field_filters` key
		 * @param int $form_id ID of the Gravity Forms form that is being searched
		 * @param array $args The View settings.
		 */
		$search_criteria = apply_filters( 'gravityview_fe_search_criteria', $search_criteria, $form_id, $args );

		if ( ! is_array( $search_criteria ) ) {
			return array();
		}

		$original_search_criteria = $search_criteria;

		gravityview()->log->debug( '[get_search_criteria] Search Criteria after hook gravityview_fe_search_criteria: ', array( 'data' => $search_criteria ) );

		// implicity search
		if ( ! empty( $args['search_value'] ) ) {
			// Search operator options. Options: `is` or `contains`
			$operator = ! empty( $args['search_operator'] ) && in_array( $args['search_operator'], array( 'is', 'isnot', '>', '<', 'contains' ) ) ? $args['search_operator'] : 'contains';

			$args['search_value'] = html_entity_decode( $args['search_value'], ENT_QUOTES );

			$search_criteria['field_filters'][] = array(
				'key'      => \GV\Utils::_GET( 'search_field', \GV\Utils::get( $args, 'search_field' ) ), // The field ID to search
				'value'    => _wp_specialchars( $args['search_value'] ), // The value to search. Encode ampersands but not quotes.
				'operator' => $operator,
			);

			// Lock search mode to "all" with implicit presearch filter.
			$search_criteria['field_filters']['mode'] = 'all';
		}

		if ( $search_criteria !== $original_search_criteria ) {
			gravityview()->log->debug( '[get_search_criteria] Search Criteria after implicity search: ', array( 'data' => $search_criteria ) );
		}

		// Handle setting date range
		$search_criteria = self::process_search_dates( $args, $search_criteria );

		if ( $search_criteria !== $original_search_criteria ) {
			gravityview()->log->debug( '[get_search_criteria] Search Criteria after date params: ', array( 'data' => $search_criteria ) );
		}

		// remove not approved entries
		$search_criteria = self::process_search_only_approved( $args, $search_criteria );

		/**
		 * Modify entry status requirements to be included in search results.
		 *
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
	 * @uses  gravityview_get_entries()
	 * @param array $args\n
	 *   - $id - View id
	 *   - $page_size - Page
	 *   - $sort_field - form field id to sort
	 *   - $sort_direction - ASC / DESC
	 *   - $start_date - Ymd
	 *   - $end_date - Ymd
	 *   - $class - assign a html class to the view
	 *   - $offset (optional) - This is the start point in the current data set (0 index based).
	 * @param int   $form_id Gravity Forms Form ID
	 * @return array Associative array with `count`, `entries`, and `paging` keys. `count` has the total entries count, `entries` is an array with Gravity Forms full entry data, `paging` is an array with `offset` and `page_size` keys
	 */
	public static function get_view_entries( $args, $form_id ) {

		gravityview()->log->debug( '[get_view_entries] init' );
		// start filters and sorting

		$parameters = self::get_view_entries_parameters( $args, $form_id );

		$count = 0; // Must be defined so that gravityview_get_entries can use by reference

		// fetch entries
		list( $entries, $paging, $count ) =
			\GV\Mocks\GravityView_frontend_get_view_entries( $args, $form_id, $parameters, $count );

		gravityview()->log->debug(
			'Get Entries. Found: {count} entries',
			array(
				'count' => $count,
				'data'  => $entries,
			)
		);

		/**
		 * Filter the entries output to the View.
		 *
		 * @deprecated since 1.5.2
		 * @param array $entries Array of entries to be displayed
		 * @param array $args View settings associative array
		 */
		$entries = apply_filters( 'gravityview_view_entries', $entries, $args );

		$return = array(
			'count'   => $count,
			'entries' => $entries,
			'paging'  => $paging,
		);

		/**
		 * Filter the entries output to the View.
		 *
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
	 * @param int   $form_id Gravity Forms form ID to search
	 *
	 * @return array With `search_criteria`, `sorting`, `paging`, `cache` keys
	 */
	public static function get_view_entries_parameters( $args = array(), $form_id = 0 ) {

		if ( ! is_array( $args ) || ! is_numeric( $form_id ) ) {

			gravityview()->log->error( 'Passed args are not an array or the form ID is not numeric' );

			return array();
		}

		$form_id = intval( $form_id );
		$view_id = \GV\Utils::get( $args, 'id' );

		/**
		 * Process search parameters
		 *
		 * @var array
		 */
		$search_criteria = self::get_search_criteria( $args, $form_id );

		$paging = self::get_search_criteria_paging( $args );

		$parameters = array(
			'search_criteria' => $search_criteria,
			'sorting'         => self::updateViewSorting( $args, $form_id ),
			'paging'          => $paging,
			'cache'           => isset( $args['cache'] ) ? $args['cache'] : true,
		);

		/**
		 * Filter get entries criteria.
		 *
		 * @param array $parameters Array with `search_criteria`, `sorting` and `paging` keys.
		 * @param array $args View configuration args. {
		 *      @type int $id View id
		 *      @type int $page_size Number of entries to show per page
		 *      @type string $sort_field Form field id to sort
		 *      @type string $sort_direction Sorting direction ('ASC', 'DESC', or 'RAND')
		 *      @type string $start_date - Ymd
		 *      @type string $end_date - Ymd
		 *      @type string $class - assign a html class to the view
		 *      @type string $offset (optional) - This is the start point in the current data set (0 index based).
		 * }
		 * @param int $form_id ID of Gravity Forms form
		 */
		$parameters = apply_filters( 'gravityview_get_entries', $parameters, $args, $form_id );

		/**
		 * Filter get entries criteria for a specific View.
		 *
		 * @param array $parameters Array with `search_criteria`, `sorting` and `paging` keys.
		 * @param array $args View configuration args.
		 */
		$parameters = apply_filters( "gravityview_get_entries_{$view_id}", $parameters, $args, $form_id );

		gravityview()->log->debug( '$parameters passed to gravityview_get_entries(): ', array( 'data' => $parameters ) );

		return $parameters;
	}

	/**
	 * Get the paging array for the View
	 *
	 * @since 1.19.5
	 *
	 * @param $args
	 * @param int  $form_id
	 */
	public static function get_search_criteria_paging( $args ) {

		/**
		 * The default number of entries displayed in a View.
		 *
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
		$offset    = ( $curr_page - 1 ) * $page_size;

		if ( ! empty( $args['offset'] ) ) {
			$offset += intval( $args['offset'] );
		}

		$paging = array(
			'offset'    => $offset,
			'page_size' => $page_size,
		);

		gravityview()->log->debug( 'Paging: ', array( 'data' => $paging ) );

		return $paging;
	}

	/**
	 * Updates the View sorting criteria
	 *
	 * @since 1.7
	 *
	 * @param array $args View settings. Required to have `sort_field` and `sort_direction` keys
	 * @param int   $form_id The ID of the form used to sort
	 * @return array $sorting Array with `key`, `direction` and `is_numeric` keys
	 */
	public static function updateViewSorting( $args, $form_id ) {
		$sorting = array();

		$has_values = isset( $_GET['sort'] );

		if ( $has_values && is_array( $_GET['sort'] ) ) {
			$sorts = array_keys( $_GET['sort'] );
			$dirs  = array_values( $_GET['sort'] );

			if ( $has_values = array_filter( $dirs ) ) {
				$sort_field_id  = end( $sorts );
				$sort_direction = end( $dirs );
			}
		}

		if ( ! isset( $sort_field_id ) ) {
			$sort_field_id = isset( $_GET['sort'] ) ? $_GET['sort'] : \GV\Utils::get( $args, 'sort_field' );
		}

		if ( ! isset( $sort_direction ) ) {
			$sort_direction = isset( $_GET['dir'] ) ? $_GET['dir'] : \GV\Utils::get( $args, 'sort_direction' );
		}

		if ( is_array( $sort_field_id ) ) {
			$sort_field_id = array_pop( $sort_field_id );
		}

		if ( is_array( $sort_direction ) ) {
			$sort_direction = array_pop( $sort_direction );
		}

		if ( ! empty( $sort_field_id ) ) {
			if ( is_array( $sort_field_id ) ) {
				$sort_direction = array_values( $sort_field_id );
				$sort_field_id  = array_keys( $sort_field_id );

				$sort_field_id  = reset( $sort_field_id );
				$sort_direction = reset( $sort_direction );
			}

			$sort_field_id = self::_override_sorting_id_by_field_type( $sort_field_id, $form_id );
			$sorting       = array(
				'key'        => $sort_field_id,
				'direction'  => strtolower( $sort_direction ),
				'is_numeric' => GVCommon::is_field_numeric( $form_id, $sort_field_id ),
			);

			if ( 'RAND' === $sort_direction ) {

				$form = GVCommon::get_form( $form_id );

				// Get the first GF_Field field ID, set as the key for entry randomization
				if ( ! empty( $form['fields'] ) ) {

					/** @var GF_Field $field */
					foreach ( $form['fields'] as $field ) {
						if ( ! is_a( $field, 'GF_Field' ) ) {
							continue;
						}

						$sorting = array(
							'key'        => $field->id,
							'is_numeric' => false,
							'direction'  => 'RAND',
						);

						break;
					}
				}
			}
		}

		if ( ! class_exists( 'GravityView_View' ) ) {
			gravityview()->plugin->include_legacy_frontend( true );
		}

		GravityView_View::getInstance()->setSorting( $sorting );

		gravityview()->log->debug( '[updateViewSorting] Sort Criteria : ', array( 'data' => $sorting ) );

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
	 * @internal Hi developer! Although this is public, don't call this method; we're going to replace it.
	 *
	 * @param int|string|array $sort_field_id Field used for sorting (`id` or `1.2`), or an array for multisorts
	 * @param int              $form_id GF Form ID
	 *
	 * @return string|array Possibly modified sorting ID. Array if $sort_field_id is passed as array.
	 */
	public static function _override_sorting_id_by_field_type( $sort_field_id, $form_id ) {

		if ( is_array( $sort_field_id ) ) {
			$modified_ids = array();
			foreach ( $sort_field_id as $_sort_field_id ) {
				$modified_ids [] = self::_override_sorting_id_by_field_type( $_sort_field_id, $form_id );
			}
			return $modified_ids;
		}

		$form = gravityview_get_form( $form_id );

		$sort_field = GFFormsModel::get_field( $form, $sort_field_id );

		if ( ! $sort_field ) {
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

					switch ( strtolower( $address_part ) ) {
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
					 * Override how to sort when sorting full name.
					 *
					 * @since 1.7.4
					 * @since 2.28.0 Default sorting is set to first and last name.
					 *
					 * @param string $name_part Sort by `first`, `last` or `first-last` (default: `first-last`)
					 * @param string $sort_field_id Field used for sorting
					 * @param int $form_id GF Form ID
					 */
					$name_part = apply_filters( 'gravityview/sorting/full-name', 'first-last', $sort_field_id, $form_id );

					if ( 'last' === strtolower( $name_part ) ) {
						$sort_field_id .= '.6';
					} elseif ( 'first' === strtolower( $name_part ) ) {
						$sort_field_id .= '.3';
					} elseif ( 'first-last' === strtolower( $name_part ) ) {
						$sort_field_id = "{$sort_field_id}.3|{$sort_field_id}.6";
					}
				}
				break;
			case 'list':
				$sort_field_id = false;
				break;
			case 'time':
				/**
				 * Override how to sort when sorting time.
				 *
				 * @see GravityView_Field_Time
				 * @since 1.14
				 * @param string $name_part Field used for sorting
				 * @param int $form_id GF Form ID
				 */
				$sort_field_id = apply_filters( 'gravityview/sorting/time', $sort_field_id, $form_id );
				break;
		}

		return $sort_field_id;
	}

	/**
	 * Verify if user requested a single entry view
	 *
	 * @since 2.3.3 Added return null
	 * @return boolean|string|null false if not, single entry slug if true, null if \GV\Entry doesn't exist yet
	 */
	public static function is_single_entry() {

		// Since this is a public method, it can be called outside of the plugin. Don't assume things have been loaded properly.
		if ( ! class_exists( '\GV\Entry' ) ) {

			// Not using gravityview()->log->error(), since that may not exist yet either!
			do_action( 'gravityview_log_error', '\GV\Entry not defined yet. Backtrace: ' . wp_debug_backtrace_summary() );

			return null;
		}

		$var_name = \GV\Entry::get_endpoint_name();

		$single_entry = get_query_var( $var_name );

		/**
		 * Modify the entry that is being displayed.
		 *
		 * @internal Should only be used by things like the oEmbed functionality.
		 * @since 1.6
		 */
		$single_entry = apply_filters( 'gravityview/is_single_entry', $single_entry );

		if ( empty( $single_entry ) ) {
			return false;
		} else {
			return $single_entry;
		}
	}


	/**
	 * Register styles and scripts
	 *
	 * @return void
	 */
	public function add_scripts_and_styles() {
		global $post, $posts;
		// enqueue template specific styles
		if ( $this->getGvOutputData() ) {

			$views = $this->getGvOutputData()->get_views();

			foreach ( $views as $view_id => $data ) {
				$view        = \GV\View::by_id( $data['id'] );
				$data        = $view->as_data();
				$template_id = $this->single_entry
					? gravityview_get_single_entry_template_id( $view->ID )
					: gravityview_get_directory_entries_template_id( $view->ID );

				// By default, no thickbox
				$js_dependencies  = array( 'jquery', 'gravityview-jquery-cookie' );
				$css_dependencies = array();

				$lightbox = $view->settings->get( 'lightbox' );

				// If the thickbox is enqueued, add dependencies
				if ( $lightbox ) {

					global $wp_filter;

					/**
					 * Override the lightbox script to enqueue. Default: `thickbox`.
					 *
					 * @deprecated 2.5.1 Naming. See `gravityview_lightbox_script` instead.
					 *
					 * @param string $script_slug If you want to use a different lightbox script, return the name of it here.
					 */
					$js_dependency = apply_filters_deprecated( 'gravity_view_lightbox_script', array( 'thickbox' ), '2.5.1', 'gravityview_lightbox_script' );

					/**
					 * Override the lightbox script to enqueue. Default: `thickbox`.
					 *
					 * @since 2.5.1
					 *
					 * @param string $script_slug If you want to use a different lightbox script, return the name of it here.
					 * @param \GV\View The View.
					 */
					$js_dependency     = apply_filters( 'gravityview_lightbox_script', $js_dependency, $view );
					$js_dependencies[] = $js_dependency;

					/**
					 * Modify the lightbox CSS slug. Default: `thickbox`.
					 *
					 * @deprecated 2.5.1 Naming. See `gravityview_lightbox_style` instead.
					 *
					 * @param string $script_slug If you want to use a different lightbox script, return the name of its CSS file here.
					 */
					$css_dependency = apply_filters_deprecated( 'gravity_view_lightbox_style', array( 'thickbox' ), '2.5.1', 'gravityview_lightbox_style' );

					/**
					 * Override the lightbox script to enqueue. Default: `thickbox`.
					 *
					 * @since 2.5.1
					 *
					 * @param string $script_slug If you want to use a different lightbox script, return the name of it here.
					 * @param \GV\View The View.
					 */
					$css_dependency     = apply_filters( 'gravityview_lightbox_style', $css_dependency, $view );
					$css_dependencies[] = $css_dependency;
				}

				/**
				 * If the form has checkbox fields, enqueue dashicons
				 *
				 * @see https://github.com/katzwebservices/GravityView/issues/536
				 * @since 1.15
				 */
				if ( gravityview_view_has_single_checkbox_or_radio( $data['form'], $data['fields'] ) ) {
					$css_dependencies[] = 'dashicons';
				}

				wp_register_script( 'gravityview-jquery-cookie', plugins_url( 'assets/lib/jquery.cookie/jquery.cookie.min.js', GRAVITYVIEW_FILE ), array( 'jquery' ), GV_PLUGIN_VERSION, true );

				$script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

				wp_register_script(
					'gravityview-fe-view',
					plugins_url( 'assets/js/fe-views' . $script_debug . '.js', GRAVITYVIEW_FILE ),
					apply_filters( 'gravityview_js_dependencies', $js_dependencies ),
					filemtime( GRAVITYVIEW_DIR . 'assets/js/fe-views' . $script_debug . '.js' ),
					true
				);

				static $inlined_scripts = [];


				// Only print once.
				if( ! isset( $inlined_scripts[ $view->ID ] ) ) {

					$custom_javascript = $view->settings->get( 'custom_javascript' );

					if ( ! empty( $custom_javascript ) ) {
						wp_add_inline_script( 'gravityview-fe-view', $custom_javascript, 'after' );
					}
				}

				wp_enqueue_script( 'gravityview-fe-view' );

				if ( ! empty( $data['atts']['sort_columns'] ) ) {
					wp_enqueue_style( 'gravityview_font', plugins_url( 'assets/css/font.css', GRAVITYVIEW_FILE ), $css_dependencies, GV_PLUGIN_VERSION, 'all' );
				}

				$this->enqueue_default_style( $css_dependencies );

				self::add_style( $template_id );

				// Only print once.
				if( ! isset( $inlined_scripts[ $view->ID ] ) ) {
					$custom_css = $view->settings->get( 'custom_css', null );

					if ( $custom_css ) {
						wp_add_inline_style( 'gravityview_default_style', $custom_css );
					}
				}

				$inlined_scripts[ $view->ID ] = true;
			}

			if ( 'wp_print_footer_scripts' === current_filter() ) {

				$js_localization = array(
					'cookiepath' => COOKIEPATH,
					'clear'      => _x( 'Clear', 'Clear all data from the form', 'gk-gravityview' ),
					'reset'      => _x( 'Reset', 'Reset the search form to the state that existed on page load', 'gk-gravityview' ),
				);

				/**
				 * Modify the array passed to wp_localize_script().
				 *
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
		 * Should GravityView use the legacy Search Bar stylesheet (from before Version 1.17)?
		 *
		 * @since 1.17
		 * @param bool $use_legacy_search_style If true, loads `gv-legacy-search(-rtl).css`. If false, loads `gv-default-styles(-rtl).css`. `-rtl` is added on RTL websites. Default: `false`
		 */
		$use_legacy_search_style = apply_filters( 'gravityview_use_legacy_search_style', false );

		$rtl = is_rtl() ? '-rtl' : '';

		$css_file_base = $use_legacy_search_style ? 'gv-legacy-search' : 'gv-default-styles';
		$css_file_path = $css_file_base . $rtl . '.css';

		$css_url = gravityview_css_url( $css_file_path );

		$version = GV_PLUGIN_VERSION;

		if( is_readable( GRAVITYVIEW_DIR . 'templates/css/source/' . $css_file_path ) ) {
			$version = filemtime( GRAVITYVIEW_DIR . 'templates/css/source/' . $css_file_path );
		}

		wp_enqueue_style( 'gravityview_default_style', $css_url, $css_dependencies, $version, 'all' );
	}

	/**
	 * Add template extra style if exists
	 *
	 * @param string $template_id
	 */
	public static function add_style( $template_id ) {

		if ( ! empty( $template_id ) && wp_style_is( 'gravityview_style_' . $template_id, 'registered' ) ) {
			gravityview()->log->debug( 'Adding extra template style for {template_id}', array( 'template_id' => $template_id ) );
			wp_enqueue_style( 'gravityview_style_' . $template_id );
		} elseif ( empty( $template_id ) ) {
			gravityview()->log->error( 'Cannot add template style; template_id is empty' );
		} else {
			gravityview()->log->error( 'Cannot add template style; {template_id} is not registered', array( 'template_id' => 'gravityview_style_' . $template_id ) );
		}
	}


	/**
	 * Inject the sorting links on the table columns
	 *
	 * Callback function for hook 'gravityview/template/field_label'
	 *
	 * @see GravityView_API::field_label() (in includes/class-api.php)
	 *
	 * @since 1.7
	 *
	 * @param string $label Field label.
	 * @param array  $field Field settings.
	 * @param array  $form Form object.
	 *
	 * @return string Field Label.
	 */
	public function add_columns_sort_links( $label = '', $field = array(), $form = array() ) {

		/**
		 * Not a table-based template; don't add sort icons
		 *
		 * @since 1.12
		 */
		if ( ! preg_match( '/table/ism', GravityView_View::getInstance()->getTemplatePartSlug() ) ) {
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
			'dir'  => 'asc',
		);

		if ( ! empty( $sorting['key'] ) && (string) $sort_field_id === (string) $sorting['key'] ) {
			// toggle sorting direction.
			if ( 'asc' === $sorting['direction'] ) {
				$sort_args['dir'] = 'desc';
				$class           .= ' gv-icon-sort-desc';
			} else {
				$sort_args['dir'] = 'asc';
				$class           .= ' gv-icon-sort-asc';
			}
		} else {
			$class .= ' gv-icon-caret-up-down';
		}

		$url = add_query_arg( $sort_args, remove_query_arg( array( 'pagenum' ) ) );

		return '<a href="' . esc_url_raw( $url ) . '" class="' . $class . '" ></a>&nbsp;' . $label;
	}

	/**
	 * Checks if field (column) is sortable
	 *
	 * @param string $field Field settings
	 * @param array  $form Gravity Forms form array
	 *
	 * @since 1.7
	 *
	 * @return bool True: Yes, field is sortable; False: not sortable
	 */
	public function is_field_sortable( $field_id = '', $form = array() ) {

		$field_type = $field_id;

		if ( is_numeric( $field_id ) ) {
			$field      = GFFormsModel::get_field( $form, $field_id );
			$field_type = $field ? $field->type : $field_id;
		}

		$not_sortable = array(
			'edit_link',
			'delete_link',
		);

		/**
		 * @depecated 2.14
		 * @since 1.7
		 */
		$not_sortable = apply_filters_deprecated( 'gravityview/sortable/field_blacklist', array( $not_sortable, $field_type, $form ), '2.14', 'gravityview/sortable/field_blocklist' );

		/**
		 * Modify what fields should never be sortable.
		 *
		 * @since 2.14
		 * @param array $not_sortable Array of field types that aren't sortable.
		 * @param string $field_type Field type to check whether the field is sortable.
		 * @param array $form Gravity Forms form.
		 */
		$not_sortable = apply_filters( 'gravityview/sortable/field_blocklist', $not_sortable, $field_type, $form );

		if ( in_array( $field_type, $not_sortable ) ) {
			return false;
		}

		return apply_filters( "gravityview/sortable/formfield_{$form['id']}_{$field_id}", apply_filters( "gravityview/sortable/field_{$field_id}", true, $form ) );
	}
}

GravityView_frontend::getInstance();
