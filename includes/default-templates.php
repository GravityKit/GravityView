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
		
		
		//$form = gravityview_get_form( $form_id );

		
		// table header
		$html = '<table class="">';
		$html .= '<thead>';
		
		$header_row = '';
		foreach( $dir_fields['table-columns'] as $key => $column ) {
			//$label = gravityview_get_field_label( $form, $column['id'] );
			$header_row .= '<th>' . esc_html( $column['label'] ) . '</th>';
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
		if( 'default_list' === $template ) {
			$areas = array( 
				array( 'id' => 'gv-list-title', 'areaid' => 'list-title', 'label' => __( 'Entry title', 'gravity-view') ), 
				array( 'id' => 'gv-list-content', 'areaid' => 'list-content', 'label' => __( 'Entry Content', 'gravity-view') ), 
				array( 'id' => 'gv-list-footer', 'areaid' => 'list-footer', 'label' => __( 'Entry Footer', 'gravity-view') ),
			);
		}
		return $areas;
	}
	
	function render_directory_view( $html = '', $form_id, $dir_fields, $entries, $atts = '' ) {
		
		$html .= '<div id="" class="">';
		error_log(' $dir_fields: '. print_r( $dir_fields, true) );
		error_log(' $atts: '. print_r( $atts, true) );
		
		foreach( $entries as $entry ) {
			error_log(' $entry: '. print_r( $entry, true) );
			$html .= '<div id="gv_list_'.$entry['id'].'" class="">';
			$html .= $this->render_row( $dir_fields['list-title'], $entry, array( 'before' => '<div class="list-row-title">', 'after' => '</div>' ), array( 'before' => '<h3>', 'after' => '</h3>', 'sep' => '' ) );
			$html .= $this->render_row( $dir_fields['list-content'], $entry, array( 'before' => '<div class="list-row-content">', 'after' => '</div>' ), array( 'before' => '<p>', 'after' => '</p>', 'sep' => '' ) );
			$html .= $this->render_row( $dir_fields['list-footer'], $entry, array( 'before' => '<div class="list-row-footer"><ul>', 'after' => '</ul></div>' ), array( 'before' => '<li>', 'after' => '</li>', 'sep' => '' ) );
			
			$html .= '</div>';
		}
		
		$html .= '</div>';
		
		return $html;
	
	}
	
	
	function render_row( $fields, $entry, $row_wrap, $element_tags ) {
		
		foreach( $fields as $field ) {
			
			if( !empty( $field['show_label'] ) ) {
				$label = empty( $field['custom_label'] ) ? $field['label'] : $field['custom_label'];
				$label_sep = empty( $element_tags['label_sep'] ) ? ': ' : $element_tags['label_sep'];
				$label .= apply_filters( 'gravityview_render_after_label', $label_sep, $field );
			} else {
				$label = '';
			}
			$content = $this->get_field_entry_value( $entry, $field['id'] ); //isset( $entry[ $field['id'] ] ) ? $entry[ $field['id'] ] : '';
			$elements[] = $element_tags['before'] . $label . $content  . $element_tags['after'];
		}
		
		$element_sep = empty( $element_tags['sep'] ) ? ' ' : $element_tags['sep'];
		
		return $row_wrap['before'] . implode( $element_sep , $elements ) . $row_wrap['after'];
		
	}
	
	// !!! this function will be migrated to another class.
	/**
	 * Given an entry and a form field id, calculate the entry value for that field.
	 * 
	 * @access public
	 * @param array $entry
	 * @param integer $field_id
	 * @return string
	 */
	function get_field_entry_value( $entry, $field_id ) {
		
		if( empty( $entry['form_id'] ) || empty( $field_id ) ) {
			return '';
		}
		
		$value = '';
		
		$form = gravityview_get_form( $entry['form_id'] );
		$field = gravityview_get_field( $form, $field_id );
		
		if( !empty( $field['type'] ) ) {
		
			switch( $field['type'] ){

				case 'address':
				case 'radio':
				case 'checkbox':
				case 'name':
					$value = RGFormsModel::get_lead_field_value( $entry, $field );
					$value = GFCommon::get_lead_field_display( $field, $value, $lead['currency'] );
				
					break;
				
				default:
					$value = $entry[ $field_id ];
					break;
				
			} //switch
		} // if
		
		return $value;
	}
	
	
	
}
