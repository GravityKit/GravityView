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