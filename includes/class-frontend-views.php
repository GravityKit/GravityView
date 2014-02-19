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
	
	function __construct() {
		// 
		
		// init - register rewrite
		//add_action( 'init', array( $this, 'init_rewrite' ) );
		//add_filter( 'query_vars', array( $this, 'add_query_vars_filter' ) );
		
	
	
	}
	
	
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
		
		// check if user requests single entry
		$single_entry = get_query_var('entry');

		//confront attributes with defaults
		extract( shortcode_atts( array( 'id' => '', 'page_size' => '', 'sort_field' => '', 'sort_direction' => 'ASC', 'start_date' => '', 'end_date' => '', 'class' => '' ), $atts ) );
		
		
		
		// validate attributes
		if( empty( $id ) ) {
			return;
		}
		
		// get form assign to this view
		$form_id = get_post_meta( $id, '_gravityview_form_id', true );
		
		$dir_template = get_post_meta( $id, '_gravityview_directory_template', true );
		
		$dir_fields = get_post_meta( $id, '_gravityview_directory_fields', true );
		
		// Search Criteria
		$search_criteria = '';
		
		//start date & end date
		if( !empty( $start_date ) && !empty( $end_date ) ) {
			$search_criteria['start_date'] = $start_date;
			$search_criteria['end_date'] = $end_date;
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
		
		$paging = array('offset' => 0, 'page_size' => $page_size );
		
		
		//get entry or entries
		if( !empty( $single_entry ) ) {
			$entries[] = gravityview_get_entry( $single_entry );
			
		} else {
			$entries = gravityview_get_entries( $form_id, compact( 'search_criteria', 'sorting', 'paging' ), $count );
			
		}
		
		// remove hidden fields
		
		// remove not approved entries
		
		
		
		// Get the template slug
		$view_slug =  apply_filters( 'gravityview_template_slug_'. $dir_template, 'table' );
		
		// Prepare to render view and set vars
		global $gravity_view;
		$gravity_view = new GravityView_Template();
		
		$gravity_view->entries = $entries;
		$gravity_view->fields = $dir_fields;
		
		ob_start();
		
		if( empty( $single_entry ) ) {
			$gravity_view->render( $view_slug, 'header' );
			$gravity_view->render( $view_slug, 'body' );
			$gravity_view->render( $view_slug, 'footer' );
		} else {
			$gravity_view->render( $view_slug, 'single' );
		}
		
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	
	
	
	
	
}






