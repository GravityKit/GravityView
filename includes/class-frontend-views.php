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
	
	
	}
	
	
	public static function init_rewrite() {
		
		global $wp_rewrite;
		
		if( !$wp_rewrite->using_permalinks() ) { 
			return; 
		}
		
		$endpoint = sanitize_title( apply_filters( 'gravityview_directory_endpoint', 'entry' ) );

		# @TODO: Make sure this works in MU
		$wp_rewrite->add_permastruct("{$endpoint}", $endpoint.'/%'.$endpoint.'%/?', true);
		$wp_rewrite->add_endpoint("{$endpoint}",EP_ALL);
	
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
		
		
		//get entries
		$entries = gravityview_get_entries( $form_id, compact( 'search_criteria', 'sorting', 'paging' ), $count );
		
		// remove hidden fields
		
		// remove not approved entries
		
		
		
		// Get the template slug
		$view_slug =  apply_filters( 'gravityview_template_slug_'. $dir_template, 'table' );
		
		// Prepare to render view and set vars
		$view = new GravityView_Template();
		$view->entries = $entries;
		$view->fields = $dir_fields;
		
		ob_start();
		
		$view->render( $view_slug, 'header' );
		$view->render( $view_slug, 'body' );
		$view->render( $view_slug, 'footer' );
		
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	
	
	
	
	
}