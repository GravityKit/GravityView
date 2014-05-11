<?php
/**
 * GravityView Frontend functions
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */



class GravityView_frontend {

	/**
	 * Register rewrite rules to capture the single entry view
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function init_rewrite() {

		global $wp_rewrite;

		if( !$wp_rewrite->using_permalinks() ) {
			return;
		}

		$endpoint = self::get_entry_var_name();

		//add_permastruct( "{$endpoint}", $endpoint.'/%'.$endpoint.'%/?', true);
		add_rewrite_endpoint( "{$endpoint}", EP_ALL );


	}

	/**
	 * Make the entry query var public to become available at WP_Query
	 *
	 * @access public
	 * @static
	 * @param array $vars
	 * @return $vars
	 */
	public static function add_query_vars_filter( $vars ){
		$vars[] = self::get_entry_var_name();
		return $vars;
	}


	/**
	 * Return the query var / end point name for the entry
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function get_entry_var_name() {
		return sanitize_title( apply_filters( 'gravityview_directory_endpoint', 'entry' ) );
	}


	/**
	 * Retrieve the default args for shortcode and theme function
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function get_default_args() {
		$defaults = array( 'id' => '', 'page_size' => '', 'sort_field' => '', 'sort_direction' => 'ASC', 'start_date' => '', 'end_date' => '', 'class' => '' );
		return $defaults;
	}


	/**
	 * Callback function for add_shortcode()
	 *
	 * @access public
	 * @static
	 * @param mixed $atts
	 * @return void
	 */
	public static function render_view_shortcode( $atts ) {
		GravityView_Plugin::log_debug( '[render_view_shortcode] Init Shortcode. Attributes: ' . print_r( $atts, true ) );
		//confront attributes with defaults
		$args = shortcode_atts( self::get_default_args() , $atts, 'gravityview' );
		GravityView_Plugin::log_debug( '[render_view_shortcode] Init Shortcode. Merged Attributes: ' . print_r( $args, true ) );
		return self::render_view( $args );
	}


	/**
	 * In case View post is called directly, insert the view in the post content
	 *
	 * @access public
	 * @static
	 * @param mixed $content
	 * @return void
	 */
	public static function insert_view_in_content( $content ) {
		$post = get_post();

		if( 'gravityview' == get_post_type( $post ) ) {
			$content .= self::render_view( array( 'id' => $post->ID ) );
		}

		return $content;
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
	 *
	 * @access public
	 * @static
	 * @param mixed $args
	 * @return void
	 */
	public static function render_view( $args ) {

		GravityView_Plugin::log_debug( '[render_view] Init View. Arguments: ' . print_r( $args, true ) );

		extract( $args );

		// validate attributes
		if( empty( $id ) ) {
			return;
		}

		// get form, fields and settings assign to this view
		$form_id = get_post_meta( $id, '_gravityview_form_id', true );
		GravityView_Plugin::log_debug( '[render_view] Form ID: ' . print_r( $form_id, true ) );

		$template_id  = get_post_meta( $id, '_gravityview_directory_template', true );
		GravityView_Plugin::log_debug( '[render_view] Template ID: ' . print_r( $template_id, true ) );

		$dir_fields = get_post_meta( $id, '_gravityview_directory_fields', true );
		GravityView_Plugin::log_debug( '[render_view] Fields: ' . print_r( $dir_fields, true ) );

		$template_settings = get_post_meta( $id, '_gravityview_template_settings', true );
		GravityView_Plugin::log_debug( '[render_view] Template Settings: ' . print_r( $template_settings, true ) );

		// remove fields according to visitor visibility permissions (if logged-in)
		$dir_fields = self::filter_fields( $dir_fields );
		GravityView_Plugin::log_debug( '[render_view] Fields after visibility filter: ' . print_r( $dir_fields, true ) );

		// set globals for templating
		global $gravityview_view;
		$gravityview_view = new GravityView_View();
		$gravityview_view->form_id = $form_id;
		$gravityview_view->view_id = $id;
		$gravityview_view->fields = $dir_fields;

		// check if user requests single entry
		$single_entry = get_query_var( self::get_entry_var_name() );

		if( empty( $single_entry ) ) {
			// user requested Directory View
			GravityView_Plugin::log_debug( '[render_view] Executing Directory View' );

			// start filters and sorting
			// Search Criteria
			$search_criteria = apply_filters( 'gravityview_fe_search_criteria', array( 'field_filters' => array() ) );

			GravityView_Plugin::log_debug( '[render_view] Search Criteria after hook gravityview_fe_search_criteria: ' . print_r( $search_criteria, true ) );

			//start date & end date - Override values defined in shortcode (if needed)
			if( !empty( $start_date ) ) {
				if( empty( $search_criteria['start_date'] ) || ( !empty( $search_criteria['start_date'] ) && strtotime( $start_date ) > strtotime( $search_criteria['start_date'] ) ) ) {
					$search_criteria['start_date'] = $start_date;
				}
			}

			if( !empty( $end_date ) ) {
				if( empty( $search_criteria['end_date'] ) || ( !empty( $search_criteria['end_date'] ) && strtotime( $end_date ) < strtotime( $search_criteria['end_date'] ) ) ) {
					$search_criteria['start_date'] = $end_date;
				}
			}

			GravityView_Plugin::log_debug( '[render_view] Search Criteria after date params: ' . print_r( $search_criteria, true ) );

			// Sorting
			$sorting = array();
			if( !empty( $sort_field ) ) {
				$sorting = array( 'key' => $sort_field, 'direction' => $sort_direction );
			}

			GravityView_Plugin::log_debug( '[render_view] Sort Criteria : ' . print_r( $sorting, true ) );


			// Paging
			if( empty( $page_size ) ) {
				$page_size = empty( $template_settings['page_size'] ) ? 25 : $template_settings['page_size'];
			}
			$curr_page = empty( $_GET['pagenum'] ) ? 1 : intval( $_GET['pagenum'] );
			$paging = array( 'offset' => ( $curr_page - 1 ) * $page_size, 'page_size' => $page_size );

			GravityView_Plugin::log_debug( '[render_view] Paging: ' . print_r( $paging, true ) );


			// remove not approved entries
			if( !empty( $template_settings['show_only_approved'] ) ) {
				$search_criteria['field_filters'][] = array( 'key' => 'is_approved', 'value' => 'Approved' );
				$search_criteria['field_filters']['mode'] = 'all'; // force all the criterias to be met

				GravityView_Plugin::log_debug( '[render_view] Search Criteria if show only approved: ' . print_r( $search_criteria, true ) );
			}


			//fetch template and slug
			$view_slug =  apply_filters( 'gravityview_template_slug_'. $template_id, 'table', 'directory' );

			GravityView_Plugin::log_debug( '[render_view] View template slug: ' . print_r( $view_slug, true ) );

			//fetch entries
			$count = 0;
			$entries = gravityview_get_entries( $form_id, compact( 'search_criteria', 'sorting', 'paging' ), $count );

			GravityView_Plugin::log_debug( '[render_view] Get Entries. Found: ' . print_r( $count, true ) .' entries');

		} else {
			// user requested Single Entry View
			GravityView_Plugin::log_debug( '[render_view] Executing Single View' );

			//fetch template and slug
			$view_slug =  apply_filters( 'gravityview_template_slug_'. $template_id, 'table', 'single' );

			GravityView_Plugin::log_debug( '[render_view] View single template slug: ' . print_r( $view_slug, true ) );

			//fetch entry detail
			$count = 1;
			$entries[] = gravityview_get_entry( $single_entry );
			GravityView_Plugin::log_debug( '[render_view] Get single entry: ' . print_r( $entries, true ) );

		}

		// add template style
		self::add_style( $template_id );

		// Prepare to render view and set vars
		$gravityview_view->entries = $entries;
		$gravityview_view->total_entries = $count;


		ob_start();
		if( empty( $single_entry ) ) {
			$gravityview_view->paging = $paging;
			$gravityview_view->context = 'directory';
			$gravityview_view->render( $view_slug, 'header' );
			$gravityview_view->render( $view_slug, 'body' );
			$gravityview_view->render( $view_slug, 'footer' );
		} else {
			$gravityview_view->context = 'single';
			$gravityview_view->render( $view_slug, 'single' );
		}

		// print the view-id so it can be grabbed by the cookie mechanism  ?>
		<input type="hidden" id="gravityview-view-id" value="<?php echo $id; ?>">
		<?php
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
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
	 * Register styles and scripts
	 *
	 * @access public
	 * @return void
	 */
	public static function add_scripts_and_styles() {
		wp_enqueue_script( 'gravityview-jquery-cookie', plugins_url('includes/lib/jquery-cookie/jquery.cookie.js', GRAVITYVIEW_FILE), array( 'jquery' ), GRAVITYVIEW_VERSION, true );

		wp_enqueue_script( 'gravityview-fe-view', plugins_url('includes/js/fe-views.js', GRAVITYVIEW_FILE), array( 'jquery', 'gravityview-jquery-cookie' ), GRAVITYVIEW_VERSION, true );

		wp_enqueue_style( 'gravityview_default_style', plugins_url('templates/css/gv-default-styles.css', GRAVITYVIEW_FILE), array(), GRAVITYVIEW_VERSION, 'all' );
	}

	/**
	 * Add template extra style if exists
	 * @param string $template_id
	 */
	public static function add_style( $template_id ) {

		GravityView_Plugin::log_debug( '[add_style] Adding extra template style for: ' . print_r( $template_id, true ) );

		if( !empty( $template_id ) && wp_style_is( 'gravityview_style_' . $template_id, 'registered' ) ) {
			wp_enqueue_style( 'gravityview_style_' . $template_id );
		}

	}


}



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
		$args = wp_parse_args( GravityView_frontend::get_default_args() , $atts );
		return GravityView_frontend::render_view( $args );
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




