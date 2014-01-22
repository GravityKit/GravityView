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
		add_filter( 'gravityview_render_template_default_table', array( $this, 'render_directory_view' ), 10, 5 );
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
	
	function render_directory_view( $html = '', $form_id, $dir_fields, $entries, $atts = '' ) {
		
		
		$form = gravityview_get_form( $form_id );

		
		// table header
		$html = '<table class="">';
		$html .= '<thead>';
		
		$header_row = '';
		foreach( $dir_fields['table-columns'] as $key => $column ) {
			$label = gravityview_get_field_label( $form, $column['id'] );
			$header_row .= '<th>' . esc_html( $label ) . '</th>';
		}
		$html .= '<tr>' . $header_row . '</tr>';
		$html .= '</thead>';
		
		// table body
		$html .= '<tbody>';
		foreach( $entries as $entry ) {
			$html .= '<tr>';
			foreach( $dir_fields['table-columns'] as $column ) {
				$content = empty( $entry[ $column['id'] ] ) ? '' : $entry[ $column['id'] ];
				$html .= '<td>'. $content .'</td>';
			}
			$html .= '</tr>';
		}
		
		$html .= '</tbody>';
		
		// table footer
		$html .= '<tfoot>';
		$html .= '<tr>' . $header_row . '</tr>';
		
		$html .= '</tfoot>';
		$html .= '</table>';
		
		return $html;
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
		add_filter( 'gravityview_render_template_default_list', array( $this, 'render_directory_view' ), 10, 5 );
	}
	
	function register_template( $templates ) {
		$templates[] = array( 'id' => 'default_list', 'label' => __( 'List (default)', 'gravity-view') );
		return $templates;
	}
	
	function assign_active_areas( $areas, $template = '' ) {
		if( 'default_table' === $template ) {
			$areas = array( array( 'id' => 'gv-list-columns', 'areaid' => 'table-columns', 'label' => __( 'Visible Table Columns', 'gravity-view') ) );
		}
		return $areas;
	}
	
	function render_directory_view( $html = '', $form_id, $dir_fields, $entries, $atts = '' ) {
		
		
		
	}
	
	
}
