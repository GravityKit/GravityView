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
	
	
	
	function render_directory_view( $html = '', $form_id, $dir_fields, $entries, $atts = '' ) {
		
		$html .= '<div id="" class="">';
		error_log(' $dir_fields: '. print_r( $dir_fields, true) );
		error_log(' $atts: '. print_r( $atts, true) );
		
		foreach( $entries as $entry ) {
			error_log(' $entry: '. print_r( $entry, true) );
			$html .= '<div id="gv_list_'.$entry['id'].'" class="">';
			
			if( !empty( $dir_fields['list-title'] ) ) {
				$html .= $this->render_row( $dir_fields['list-title'], $entry, array( 'before' => '<div class="list-row-title">', 'after' => '</div>' ), array( 'before' => '<h3>', 'after' => '</h3>', 'sep' => '' ) );
			}
			
			if( !empty( $dir_fields['list-content'] ) ) {
				$html .= $this->render_row( $dir_fields['list-content'], $entry, array( 'before' => '<div class="list-row-content">', 'after' => '</div>' ), array( 'before' => '<p>', 'after' => '</p>', 'sep' => '' ) );
			}
			
			if( !empty( $dir_fields['list-footer'] ) ) {
				$html .= $this->render_row( $dir_fields['list-footer'], $entry, array( 'before' => '<div class="list-row-footer"><ul>', 'after' => '</ul></div>' ), array( 'before' => '<li>', 'after' => '</li>', 'sep' => '' ) );
			}
			
			$html .= '</div>';
		}
		
		$html .= '</div>';
		
		return $html;
	
	}
	
	
	// !!! this function will be migrated to another class.
	function render_row( $fields, $entry, $row_wrap, $element_tags ) {
		
		$row_wrap = wp_parse_args( $row_wrap, array( 'before' => '', 'after' => '') );
		$element_tags = wp_parse_args( $element_tags, array( 'before' => '', 'after' => '', 'sep' => '' ) );
		
		foreach( $fields as $field ) {
			
			// show label
			if( !empty( $field['show_label'] ) ) {
				$label = empty( $field['custom_label'] ) ? $field['label'] : $field['custom_label'];
				$label_sep = empty( $element_tags['label_sep'] ) ? ': ' : $element_tags['label_sep'];
				$label .= apply_filters( 'gravityview_render_after_label', $label_sep, $field );
			} else {
				$label = '';
			}
			
			// custom class
			if( !empty( $field['custom_class'] ) ) {
				$element_tags['before'] = '<'. str_replace( array( '<' , '>' ), '', $element_tags['before'] ) . ' class="'. $field['custom_class'] .'">';
			}
			
			// content
			$content = $this->get_field_entry_value( $entry, $field['id'] ); 
			
			// link to single entry
			if( !empty( $field['show_as_link'] ) ) {
				$content = '<a href="">'. $content . '</a>';
			}
			
			// join element parts
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
		error_log('$field: '. print_r( $field, true) );
		if( !empty( $field['type'] ) ) {
		// possible values: html, hidden, section, captcha , , ,, , , , post_title, , , post_tags, post_category, post_image, post_custom_field, 
		
		// covered: checkbox, radio, name, address, fileupload, email, textarea, post_content, post_excerpt, text, website, select
			//default
			$value = isset( $entry[ $field_id ] ) ? $entry[ $field_id ] : '' ;
		
			switch( $field['type'] ){

				case 'address':
				case 'radio':
				case 'checkbox':
				case 'name':
					$value = '';
					$value = RGFormsModel::get_lead_field_value( $entry, $field );
					$value = GFCommon::get_lead_field_display( $field, $value, $entry['currency'] );
				
					break;
				
				case 'email':
					$value = '<a href="mailto:'. esc_attr( $value ) . '">'. esc_html( $value ) .'</a>';
					break;
				
				case 'website':
					$value = '<a href="'. esc_url( $value ) . '">'. esc_html( $value ) .'</a>';
					break;
				
				case 'fileupload':

					$url = $value;
					if( !class_exists( 'GFEntryList' ) ) { require_once( WP_PLUGIN_DIR . '/gravityforms/entry_list.php' ); }
					$thumb = GFEntryList::get_icon_url( $url );
					$value = '<a href="'. esc_url( $url ) .'" target="_blank" title="' . __( 'Click to view', 'gravity-view') . '"><img src="'. esc_url( $thumb ) .'"/></a>';
					
					break;
					
				case 'post_image':
					//todo
					break;
				
				
				case 'textarea' :
				case 'post_content' :
				case 'post_excerpt' :
					if( apply_filters( 'gravityview_show_fulltext', true, $entry, $field_id ) ) {
						$long_text = $value = '';

						if( isset( $entry[ $field_id ] ) && strlen( $entry[ $field_id ] ) >= GFORMS_MAX_FIELD_LENGTH ) {
						   $long_text = RGFormsModel::get_lead_field_value( $entry, RGFormsModel::get_field( $form, $field_id ));
						}
						if( isset( $entry[ $field_id ] ) ) {
							$value = !empty( $long_text ) ? $long_text : $entry[ $field_id ];
						}
					}
					
					$value = esc_html( $value );
					
					if( apply_filters( 'gravityview_entry_value_wpautop', true, $entry, $field_id ) ) { 
						$value = wpautop( $value ); 
					};
					
					break;
				
				case 'date_created':
					$value = GFCommon::format_date( $entry['date_created'], true, apply_filters( 'gravityview_date_format', '' ) );
					break;
					
				
				case 'date':
					$value = GFCommon::date_display( $value, apply_filters( 'gravityview_date_format', $field['dateFormat'] ) );
					break;

				
				case 'list':
					$value = GFCommon::get_lead_field_display( $field, $value );
					break;
					
				
				case 'post_category':
					//todo
					break;
				
				case 'id':
					//todo
					break;
				
				case 'source_url':
					// entry link
				
					break;
				
				default:
					$value = esc_html( $value );
					break;
				
			} //switch
		} // if
		
		return apply_filters( 'gravityview_field_entry_value', $value, $entry, $field_id );
	}
	
	
	
}




