<?php
/**
 * Set of functions to separate main plugin from Gravity Forms API and other methods
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2013, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */


if( !function_exists('gravityview_get_form') ) { 
	
	
	/**
	 * Returns the form object for a given Form ID.
	 * 
	 * @access public
	 * @param mixed $form_id
	 * @return void
	 */
	function gravityview_get_form( $form_id ) {
		if( class_exists( 'GFAPI' ) && !empty( $form_id ) ) {
			return GFAPI::get_form( $form_id );
		}
		return false;
	}

}

if( !function_exists('gravityview_get_forms') ) { 
	
	
	/**
	 * Returns the form object for a given Form ID.
	 * 
	 * @access public
	 * @param mixed $form_id
	 * @return void
	 */
	function gravityview_get_forms() {
			
		if( class_exists( 'RGFormsModel' ) ) {
			$gf_forms = RGFormsModel::get_forms( null, 'title' );
			$forms = array();
			foreach( $gf_forms as $form ) {
				$forms[] = array( 'id' => $form->id, 'title' => $form->title );
			}
		}
		return $forms;
	}

}




	
	/**
	 * Return array of fields' id and label, for a given Form ID
	 * 
	 * @access public
	 * @param string $form_id (default: '')
	 * @return array
	 */
	function gravityview_get_form_fields( $form_id = '' ) {
		
		$form = gravityview_get_form( $form_id ); 
		$fields = array();
		
		if( $form ) {
			foreach( $form['fields'] as $field ) {
				$fields[ $field['id'] ] = array( 'label' => $field['label'] );
			}
		}
		
		return $fields;
		
	}
	
	
if( !function_exists('gravityview_get_entries') ) { 
	
	
	
	function gravityview_get_entries( $form_ids, $criteria = null, &$total = null ) {
		
		extract( wp_parse_args( $criteria, array( 'search_criteria' => null, 'sorting' => null, 'paging' => null ) ) );
		
		if( class_exists( 'GFAPI' ) && !empty( $form_ids ) ) {
			if( !is_null( $total ) ) {
				return GFAPI::get_entries( $form_ids, $search_criteria, $sorting, $paging, $total);
			} else {
				return GFAPI::get_entries( $form_ids, $search_criteria, $sorting, $paging );
			}
		}
		return false;
	}

}	
	
	
if( !function_exists('gravityview_get_field_label') ) { 
	
	
	
	function gravityview_get_field_label( $form, $field_id ) {
		
		if( empty($form) || empty( $field_id ) ) {
			return '';
		}
		
		foreach( $form['fields'] as $field ) {
			if( $field_id == $field['id'] ) {
				return $field['label'];
			}
		}
		return '';
	}

}	
	
	
	
	
	
	