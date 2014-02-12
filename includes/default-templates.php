<?php
/**
 * Registers the default templates
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2013, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */





/**
 * GravityView_Default_Template_Table class.
 * Defines Table(default) template
 */
class GravityView_Default_Template_Table {

	function __construct() {
		// register template into the valid templates list
		add_filter( 'gravityview_register_directory_template', array( $this, 'register_template' ) );
		
		// assign the active areas for the template
		add_filter( 'gravityview_template_active_areas', array( $this, 'assign_active_areas' ), 10, 2 );
		
		//todo: css definition for active areas
		
		// render view
		add_filter( 'gravityview_template_slug_default_table', array( $this, 'assign_view_slug' ), 10, 1 );
	}
	
	function register_template( $templates ) {
		$templates[] = array( 'id' => 'default_table', 'label' => __( 'Table (default)', 'gravity-view') );
		return $templates;
	}
	
	function assign_active_areas( $areas, $template = '' ) {
		if( 'default_table' === $template ) {
			$areas = array( array( 'id' => 'gv-table-columns', 'areaid' => 'table-columns', 'label' => __( 'Visible Table Columns', 'gravity-view') ) );
		}
		return $areas;
	}
	
	function assign_view_slug( $default ) {
		return 'table';
	}
	
}



/**
 * GravityView_Default_Template_List class.
 * Defines List (default) template
 */
class GravityView_Default_Template_List {

	function __construct() {
		// register template into the valid templates list
		add_filter( 'gravityview_register_directory_template', array( $this, 'register_template' ) );
		
		// assign the active areas for the template
		add_filter( 'gravityview_template_active_areas', array( $this, 'assign_active_areas' ), 10, 2 );
		
		//todo: css definition for active areas
		
		// render view
		add_filter( 'gravityview_template_slug_default_list', array( $this, 'assign_view_slug' ), 10, 1 );
	}
	
	function register_template( $templates ) {
		$templates[] = array( 'id' => 'default_list', 'label' => __( 'List (default)', 'gravity-view') );
		return $templates;
	}
	
	function assign_active_areas( $areas, $template = '' ) {
		if( 'default_list' === $template ) {
			$areas = array( 
				array( 'id' => 'gv-list-title', 'areaid' => 'list-title', 'label' => __( 'Entry title', 'gravity-view') ), 
				array( 'id' => 'gv-list-content', 'areaid' => 'list-content', 'label' => __( 'Entry Content', 'gravity-view') ), 
				array( 'id' => 'gv-list-footer', 'areaid' => 'list-footer', 'label' => __( 'Entry Footer', 'gravity-view') ),
			);
		}
		return $areas;
	}
	
	function assign_view_slug( $default ) {
		return 'list';
	}
	
	
}




