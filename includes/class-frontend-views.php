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

	var $is_gravityview_post_type = false;

	var $post_id = NULL;

	var $single_entry = false;

	var $gv_output_data = array();

	static $instance;

	function __construct() {

		add_action( 'wp', array( $this, 'parse_content'), 11 );

		// Shortcode to render view (directory)
		add_shortcode( 'gravityview', array( $this, 'shortcode' ) );

		// Enqueue scripts and styles after GravityView_Template::register_styles()
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_and_styles' ), 20);

		// Enqueue and print styles in the footer. Added 1 priorty so stuff gets printed at 10 priority.
		add_action( 'wp_print_footer_scripts', array( $this, 'add_scripts_and_styles' ), 1);

		add_filter( 'the_title', array( $this, 'single_entry_title' ), 1, 2 );
		add_filter( 'the_content', array( $this, 'insert_view_in_content' ) );
		add_filter( 'comments_open', array( $this, 'comments_open' ), 10, 2);

		add_action('add_admin_bar_menus', array($this, 'admin_bar_remove_links'), 80 );
		add_action('admin_bar_menu', array($this, 'admin_bar_add_links'), 85 );

		self::$instance = &$this;
	}

	static function getInstance() {

		if( !empty( self::$instance ) ) {
			return self::$instance;
		} else {
			return new GravityView_frontend;
		}
	}

	function parse_content() {
		global $post;

		if( is_admin() ) { return; }
		$this->single_entry = self::is_single_entry();
		$this->entry = ( $this->single_entry ) ? gravityview_get_entry( $this->single_entry ) : false;
		$this->is_gravityview_post_type = ( get_post_type( $post ) === 'gravityview' );

		$this->gv_output_data = new GravityView_View_Data( $post );
	}



	static function r( $content = '', $die = false, $title ='') {
		if( !empty($title)) { echo "<h3>{$title}</h3>"; }
		echo '<pre>'; print_r($content); echo '</pre>';
		if($die) { die(); }
	}

	/**
	 * Add helpful GV links to the menu bar, like Edit Entry on single entry page.
	 * @filter default text
	 * @action default text
	 * @return [type]      [description]
	 */
	function admin_bar_add_links() {
		global $wp_admin_bar, $post, $wp, $wp_the_query;

		if( GFCommon::current_user_can_any('gravityforms_edit_entries') && !empty( $this->single_entry ) ) {

			$wp_admin_bar->add_menu( array(
				'id' => 'edit-entry',
				'title' => __('Edit Entry', 'gravity-view'),
				'href' => admin_url( sprintf('admin.php?page=gf_entries&amp;view=entry&amp;id=%d&lid=%d', $this->entry['form_id'], $this->single_entry ) ),
			) );

		}

	}

	/**
	 * Remove "Edit Page" or "Edit View" links when on single entry pages
	 * @return void
	 */
	function admin_bar_remove_links() {
		global $wp_admin_bar, $post, $wp, $wp_the_query;

		// If we're on the single entry page, we don't want to cause confusion.
		if( is_admin() || ($this->single_entry && !$this->is_gravityview_post_type ) ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
		}
	}

	/**
	 * Callback function for add_shortcode()
	 *
	 * @access public
	 * @static
	 * @param mixed $atts
	 * @return void
	 */
	public function shortcode( $atts, $content = NULL ) {

		do_action( 'gravityview_log_debug', '[shortcode] $atts: ', $atts );

		return $this->render_view( $atts );
	}

	/**
	 * Filter the title for the single entry view
	 * @todo Somehow make this work with multiple shortcodes on a page. The problem is that there's no form data passed...
	 * @param  string $title   current title
	 * @param  int $passed_post_id Post ID
	 * @return string          (modified) title
	 */
	public function single_entry_title( $title, $passed_post_id = NULL ) {
		global $post, $gravityview_view;

		// If this is the directory view, return.
		if( empty( $this->single_entry ) ) {
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

		$view_meta = $this->gv_output_data->get_view( $passed_post_id );

		if( !empty( $view_meta['atts']['single_title'] ) ) {
			// We are allowing HTML in the fields, so no escaping the output
			$title = GravityView_API::replace_variables( $view_meta['atts']['single_title'], $view_meta['form'], $this->entry );
		}

		return $title;
	}


	/**
	 * In case View post is called directly, insert the view in the post content
	 *
	 * @access public
	 * @static
	 * @param mixed $content
	 * @return void
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

		if( $this->is_gravityview_post_type ) {

			foreach ( $this->gv_output_data->get_views() as $view_id => $data ) {
				$content .= $this->render_view( array( 'id' => $view_id ) );
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

		if( $this->is_gravityview_post_type ) {
			return false;
		}

		return $open;
	}


	/**
	 * Core function to render a View based on a set of arguments ($args):
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
	 * @access public
	 * @static
	 * @param mixed $args
	 * @return void
	 */
	public function render_view( $passed_args ) {
		global $post;

		// validate attributes
		if( empty( $passed_args['id'] ) ) {
			do_action( 'gravityview_log_error', '[render_view] Returning; no ID defined.', $passed_args );
			return;
		}

		$view_id = $passed_args['id'];
		$view_data = $this->gv_output_data->get_view( $view_id );

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
			return;
		}

		ob_start();

		// set globals for templating
		global $gravityview_view;

		$gravityview_view = new GravityView_View( $view_data );

		if( empty( $this->single_entry ) ) {

			// user requested Directory View
			do_action( 'gravityview_log_debug', '[render_view] Executing Directory View' );

			//fetch template and slug
			$view_slug =  apply_filters( 'gravityview_template_slug_'. $view_data['template_id'], 'table', 'directory' );
			do_action( 'gravityview_log_debug', '[render_view] View template slug: ', $view_slug );

			$view_entries = self::get_view_entries( $atts, $view_data['form_id'] );

			do_action( 'gravityview_log_debug', sprintf( '[render_view] Get Entries. Found %s entries', $view_entries['count'] ) );

			$gravityview_view->paging = $view_entries['paging'];
			$gravityview_view->context = 'directory';
			$sections = array( 'header', 'body', 'footer' );

		} else {

			// user requested Single Entry View
			do_action( 'gravityview_log_debug', '[render_view] Executing Single View' );

			// You are not permitted to view this entry.
			if( false === $this->entry ) {

				do_action( 'gravityview_log_debug', '[render_view] Entry does not exist. This may be because of View filters limiting access.');

				esc_attr_e( 'You have attempted to view an entry that does not exist.', 'gravity-view');

				return;
			}

			// We're in single view, but the view being processed is not the same view the single entry belongs to.
			if( $view_data['form_id'] !== $this->entry['form_id'] ) {

				$view_id = isset( $view_entries['entries'][0]['id'] ) ? $view_entries['entries'][0]['id'] : '(empty)';
				do_action( 'gravityview_log_debug', '[render_view] In single entry view, but the entry does not belong to this View. Perhaps there are multiple views on the page. View ID: '. $view_id);
				return;
			}

			//fetch template and slug
			$view_slug =  apply_filters( 'gravityview_template_slug_'. $view_data['template_id'], 'table', 'single' );
			do_action( 'gravityview_log_debug', '[render_view] View single template slug: ', $view_slug );

			//fetch entry detail
			$view_entries['count'] = 1;
			$view_entries['entries'][] = $this->entry;
			do_action( 'gravityview_log_debug', '[render_view] Get single entry: ', $view_entries['entries'] );

			// set back link label
			$gravityview_view->back_link_label = isset( $atts['back_link_label'] ) ? $atts['back_link_label'] : NULL;

			$gravityview_view->context = 'single';
			$sections = array( 'single' );

		}

		// add template style
		self::add_style( $view_data['template_id'] );

		// Prepare to render view and set vars
		$gravityview_view->entries = $view_entries['entries'];
		$gravityview_view->total_entries = $view_entries['count'];

		// If Edit
		if ( apply_filters( 'gravityview_is_edit_entry', false ) ) {

			do_action( 'gravityview_edit_entry' );

			return;

		} else {
			// finaly we'll render some html
			$sections = apply_filters( 'gravityview_render_view_sections', $sections, $view_data['template_id'] );

			do_action( 'gravityview_log_debug', '[render_view] Sections to render: ', $sections );
			foreach( $sections as $section ) {

				do_action( 'gravityview_log_debug', '[render_view] Rendering '. $section . ' section.' );
				$gravityview_view->render( $view_slug, $section, false );
			}

		}

		if( $this->is_gravityview_post_type ) {
			// Print the View ID to enable proper cookie pagination ?>
			<input type="hidden" id="gravityview-view-id" value="<?php echo $view_id; ?>">
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
	 * @access public
	 * @static
	 * @param mixed $args
	 * @param int $form_id
	 * @return void
	 */
	public static function get_view_entries( $args, $form_id ) {

		do_action( 'gravityview_log_debug', '[get_view_entries] init' );
		// start filters and sorting

		// Search Criteria
		$search_criteria = apply_filters( 'gravityview_fe_search_criteria', array( 'field_filters' => array() ) );
		do_action( 'gravityview_log_debug', '[get_view_entries] Search Criteria after hook gravityview_fe_search_criteria: ', $search_criteria );

		// implicity search
		if( !empty( $args['search_value'] ) ) {
			$search_criteria['field_filters'][] = array(
				'key' => ( ( !empty( $args['search_field'] ) && is_numeric( $args['search_field'] ) ) ? $args['search_field'] : null ), // The field ID to search
				'value' => esc_attr( $args['search_value'] ), // The value to search
				'operator' => 'contains', // What to search in. Options: `is` or `contains`
			);
		}
		do_action( 'gravityview_log_debug', '[get_view_entries] Search Criteria after implicity search: ', $search_criteria );

		// Handle setting date range
		$search_criteria = self::process_search_dates( $args, $search_criteria );

		do_action( 'gravityview_log_debug', '[get_view_entries] Search Criteria after date params: ', $search_criteria );


		// Sorting
		$sorting = array();
		if( !empty( $args['sort_field'] ) ) {
			$sorting = array( 'key' => $args['sort_field'], 'direction' => $args['sort_direction'] );
		}

		do_action( 'gravityview_log_debug', '[get_view_entries] Sort Criteria : ', $sorting );


		// Paging & offset
		$page_size = !empty( $args['page_size'] ) ? $args['page_size'] : 25;

		if( isset( $args['offset'] ) ) {
			$offset = $args['offset'];
		} else {
			$curr_page = empty( $_GET['pagenum'] ) ? 1 : intval( $_GET['pagenum'] );
			$offset = ( $curr_page - 1 ) * $page_size;
		}
		$paging = array( 'offset' => $offset, 'page_size' => $page_size );

		do_action( 'gravityview_log_debug', '[get_view_entries] Paging: ', $paging );


		// remove not approved entries
		if( !empty( $args['show_only_approved'] ) ) {
			$search_criteria['field_filters'][] = array( 'key' => 'is_approved', 'value' => 'Approved' );
			$search_criteria['field_filters']['mode'] = 'all'; // force all the criterias to be met

			do_action( 'gravityview_log_debug', '[get_view_entries] Search Criteria if show only approved: ', $search_criteria );
		}

		// Only show active listings
		$search_criteria['status'] = apply_filters( 'gravityview_status', 'active', $args );

		/**
		 * Filter get entries criteria
		 *
		 * Passes and returns array with `search_criteria`, `sorting` and `paging` keys.
		 *
		 * @var array
		 */
		$parameters = apply_filters( 'gravityview_get_entries', apply_filters( 'gravityview_get_entries_'.$args['id'], compact( 'search_criteria', 'sorting', 'paging' ) ) );

		do_action( 'gravityview_log_debug', '[get_view_entries] $parameters passed to gravityview_get_entries(): ', $parameters );

		//fetch entries
		$count = 0;
		$entries = gravityview_get_entries( $form_id, $parameters, $count );

		do_action( 'gravityview_log_debug', sprintf( '[get_view_entries] Get Entries. Found: %s entries', $count ) );

		return compact( 'count', 'entries', 'paging' );
	}




	// helper functions

	/**
	 * Filter area fields based on specified conditions
	 *
	 * @access public
	 * @param array $dir_fields
	 * @return void
	 */
	public static function filter_fields( $dir_fields ) {

		if( empty( $dir_fields ) || !is_array( $dir_fields ) ) {
			return $dir_fields;
		}

		foreach( $dir_fields as $area => $fields ) {
			foreach( $fields as $uniqid => $properties ) {

				if( self::hide_field_check_conditions( $properties ) ) {
					unset( $dir_fields[ $area ][ $uniqid ] );
				}

			}
		}

		return $dir_fields;

	}


	/**
	 * Check wether a certain field should not be presented based on its own properties.
	 *
	 * @access public
	 * @param array $properties
	 * @return true (field should be hidden) or false (field should be presented)
	 */
	public static function hide_field_check_conditions( $properties ) {

		// logged-in visibility
		if( !empty( $properties['only_loggedin'] ) && !current_user_can( $properties['only_loggedin_cap'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Verify if user requested a single entry view
	 * @return boolean|string false if not, single entry id if true
	 */
	public static function is_single_entry() {
		$single_entry = get_query_var( GravityView_Post_Types::get_entry_var_name() );
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
		if( !empty( $this->gv_output_data ) ) {

			$views = $this->gv_output_data->get_views();

			foreach ( $views as $view_id => $data ) {

				// By default, no thickbox
				$js_dependencies = array( 'jquery', 'gravityview-jquery-cookie' );
				$css_dependencies = array();

				// If the thickbox is enqueued, add dependencies
				if( !empty( $data['atts']['lightbox'] ) ) {
					$js_dependencies[] = apply_filters( 'gravity_view_lightbox_script', 'thickbox' );
					$css_dependencies[] = apply_filters( 'gravity_view_lightbox_style', 'thickbox' );
				}

				wp_register_script( 'gravityview-jquery-cookie', plugins_url('includes/lib/jquery-cookie/jquery.cookie.js', GRAVITYVIEW_FILE), array( 'jquery' ), GravityView_Plugin::version, true );

				$script_debug = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
				wp_enqueue_script( 'gravityview-fe-view', plugins_url('includes/js/fe-views'.$script_debug.'.js', GRAVITYVIEW_FILE), $js_dependencies, GravityView_Plugin::version, true );

				wp_enqueue_style( 'gravityview_default_style', plugins_url('templates/css/gv-default-styles.css', GRAVITYVIEW_FILE), $css_dependencies, GravityView_Plugin::version, 'all' );

				self::add_style( $data['template_id'] );

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


}

new GravityView_frontend;


/**
 * Theme function to get a GravityView view
 *
 * @access public
 * @param string $view_id (default: '')
 * @param array $atts (default: array())
 * @return void
 */
function get_gravityview( $view_id = '', $atts = array() ) {
	if( !empty( $view_id ) ) {
		$atts['id'] = $view_id;
		$args = wp_parse_args( $atts, GravityView_View_Data::get_default_args() );
		$GravityView_frontend = new GravityView_frontend;
		return $GravityView_frontend->render_view( $args );
	}
	return '';
}

/**
 * Theme function to render a GravityView view
 *
 * @access public
 * @param string $view_id (default: '')
 * @param array $atts (default: array())
 * @return void
 */
function the_gravityview( $view_id = '', $atts = array() ) {
	echo get_gravityview( $view_id, $atts );
}



