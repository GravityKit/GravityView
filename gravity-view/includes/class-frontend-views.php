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
		
	
	
	}
	

	public static function render_view_shortcode( $atts ) {
		error_log('shortcode atts: '. print_r( $atts, true) );
		//confront attributes with defaults
		extract( shortcode_atts( array( 'id' => '', 'page_size' => '', 'sort_field' => '', 'sort_direction' => '', 'start_date' => '', 'end_date' => '', 'class' => '' ), $atts ) );
		
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
		
		// Sorting
		$sorting = '';
		
		// paging
		if( empty( $page_size ) ) {
			$page_size = get_post_meta( $id, '_gravityview_page_size', true );
		}
		
		
		$paging = array('offset' => 0, 'page_size' => $page_size );
		
		$entries = gravityview_get_entries( $form_id, compact( 'search_criteria', 'sorting', 'paging' ), $count );
		
		// remove hidden fields
		
		// remove not approved entries
		
		
		
		
		
		$output = apply_filters( 'gravityview_render_template_'. $dir_template, '', $form_id, $dir_fields, $entries, $atts );
		
		return $output;
	}
	
	
	
	
	
	
}