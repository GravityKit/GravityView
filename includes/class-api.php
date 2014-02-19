<?php 
/**
 * GravityView template tags API
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2013, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */



class GravityView_API {
	
	
	
	public static function field_label( $field ) {
	
		if( !empty( $field['show_label'] ) ) {
			$label = empty( $field['custom_label'] ) ? $field['label'] : $field['custom_label'];
			$label .= apply_filters( 'gravityview_render_after_label', '', $field );
		} else {
			$label = '';
		}
		
		return $label;
		
	}
	
	public static function field_class( $field ) {
		
		if( !empty( $field['custom_class'] ) ) {
			return $field['custom_class'];
		}
		
		return '';
	}
	
	
	/**
	 * Given an entry and a form field id, calculate the entry value for that field.
	 * 
	 * @access public
	 * @param array $entry
	 * @param integer $field
	 * @return string
	 */
	public static function field_value( $entry, $field_settings ) {
		
		if( empty( $entry['form_id'] ) || empty( $field_settings['id'] ) ) {
			return '';
		}

		$field_id = $field_settings['id'];
		error_log('$field_settings : '. print_r($field_settings, true) );
		$value = '';
		
		$form = gravityview_get_form( $entry['form_id'] );
		$field = gravityview_get_field( $form, $field_id );
		
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
		
		
		
		//if show as single entry link
		if( !empty( $field_settings['show_as_link'] ) ) {
			$post = get_post();
			if( !empty( $post->ID ) ) {
				$href = trailingslashit( get_permalink( $post->ID ) ) . sanitize_title( apply_filters( 'gravityview_directory_endpoint', 'entry' ) ) . '/'. $entry['id'] .'/';
				
				$value = '<a href="'. $href .'">'. $value . '</a>';
			}
			
		}
		
		
		return apply_filters( 'gravityview_field_entry_value', $value, $entry, $field_settings );
	}
	
	
	// return href for single entry
	public static function field_link( $entry, $field ) {
		
		if( !empty( $field['show_as_link'] ) ) {
			return ;
		}
		
		return false;
	}
	
	
}


// inside loop functions

function gv_label( $field ) {
	return GravityView_API::field_label( $field );
}

function gv_class( $field ) {
	return GravityView_API::field_class( $field );
}

function gv_value( $entry, $field ) {
	return GravityView_API::field_value( $entry, $field );
}

function gv_link(  $entry, $field ) {
	return GravityView_API::field_link( $entry, $field );
}



// Templates' hooks
function gravityview_before() {
	do_action( 'gravityview_before' );
}

function gravityview_header() {
	do_action( 'gravityview_header' );
}

function gravityview_footer() {
	do_action( 'gravityview_footer' );
}

function gravityview_after() {
	do_action( 'gravityview_after' );
}
