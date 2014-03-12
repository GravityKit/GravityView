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
	
	

	

	public static function render_view_shortcode( $atts ) {

		//confront attributes with defaults
		extract( shortcode_atts( array( 'id' => '', 'page_size' => '', 'sort_field' => '', 'sort_direction' => 'ASC', 'start_date' => '', 'end_date' => '', 'class' => '' ), $atts ) );
		
		// validate attributes
		if( empty( $id ) ) {
			return;
		}
		
		// get form assign to this view
		$form_id = get_post_meta( $id, '_gravityview_form_id', true );
		
		
		
		$dir_fields = get_post_meta( $id, '_gravityview_directory_fields', true );
		
		// remove fields according to visitor visibility permissions (if logged-in)
		$dir_fields = self::filter_fields( $dir_fields );
		
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
			
			// start filters and sorting
			// Search Criteria
			$search_criteria = apply_filters( 'gravityview_fe_search_criteria', array( 'field_filters' => array() ) );
			
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
			
			// Sorting
			$sorting = array();
			if( !empty( $sort_field ) ) {
				$sorting = array( 'key' => $sort_field, 'direction' => $sort_direction );
			}
			
			// Paging
			if( empty( $page_size ) ) {
				$page_size = get_post_meta( $id, '_gravityview_page_size', true );
			}
			$curr_page = empty( $_GET['pagenum'] ) ? 1 : intval( $_GET['pagenum'] );
			$paging = array( 'offset' => ( $curr_page - 1 ) * $page_size, 'page_size' => $page_size );
			
			
			// remove not approved entries
			$only_approved = get_post_meta( $id, '_gravityview_only_approved', true );
			if( !empty( $only_approved ) ) {
				$search_criteria['field_filters'][] = array( 'key' => 'is_approved', 'value' => 'Approved' );
				$search_criteria['field_filters']['mode'] = 'all'; // force all the criterias to be met
			}
			
			//fetch template and slug
			$dir_template = get_post_meta( $id, '_gravityview_directory_template', true );
			$view_slug =  apply_filters( 'gravityview_template_slug_'. $dir_template, 'table' );
			
			//fetch entries
			$count = 0;
			$entries = gravityview_get_entries( $form_id, compact( 'search_criteria', 'sorting', 'paging' ), $count );
		
		} else {
			// user requested Single Entry View
			
			//fetch template and slug
			$single_template = get_post_meta( $id, '_gravityview_single_template', true );
			$view_slug =  apply_filters( 'gravityview_template_slug_'. $single_template, 'table' );
			
			//fetch entry detail
			$count = 1;
			$entries[] = gravityview_get_entry( $single_entry );
			
		}

		
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
		wp_register_script( 'gravityview-jquery-cookie', GRAVITYVIEW_URL . 'includes/lib/jquery-cookie/jquery.cookie.js', array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( 'gravityview-jquery-cookie' );
		wp_register_script( 'gravityview-fe-view', GRAVITYVIEW_URL . 'includes/js/fe-views.js', array( 'jquery', 'gravityview-jquery-cookie' ), '1.0.0', true );
		wp_enqueue_script( 'gravityview-fe-view' );
		
	}
	
	
}






