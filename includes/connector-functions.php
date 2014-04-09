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
	 * Returns the list of available forms
	 *
	 * @access public
	 * @param mixed $form_id
	 * @return array (id, title)
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



if( !function_exists('gravityview_get_form_fields') ) {
	/**
	 * Return array of fields' id and label, for a given Form ID
	 *
	 * @access public
	 * @param string $form_id (default: '')
	 * @return array
	 */
	function gravityview_get_form_fields( $form_id = '', $add_default_properties = false ) {

		$form = gravityview_get_form( $form_id );
		$fields = array();

		if( $add_default_properties ) {
			$form = RGFormsModel::add_default_properties( $form );
		}

		if( $form ) {
			foreach( $form['fields'] as $field ) {
				$fields[ $field['id'] ] = array( 'label' => $field['label'], 'type' => $field['type'] );

				if( $add_default_properties && !empty( $field['inputs'] ) ) {
					foreach( $field['inputs'] as $input ) {
						$fields[ (string)$input['id'] ] = array( 'label' => $input['label'].' ('.$field['label'].')', 'type' => $field['type'] );
					}

				}

			}
		}

		return $fields;

	}
}


if( !function_exists( 'gravityview_get_entry_meta' ) ) {
	/**
	 * get extra fields from entry meta
	 * @param  string $form_id (default: '')
	 * @return array
	 */
	function gravityview_get_entry_meta( $form_id, $only_default_column = true ) {

		$extra_fields = GFFormsModel::get_entry_meta( $form_id );

		$fields = array();

		foreach( $extra_fields as $key => $field ){
			if( !empty( $only_default_column ) && !empty( $field['is_default_column'] ) ) {
				$fields[ $key ] = array( 'label' => $field['label'], 'type' => 'entry_meta' );
			}
	    }
	}
}


if( !function_exists('gravityview_get_entries') ) {
	/**
	 * Retrieve entries given search, sort, paging criteria
	 *
	 * @access public
	 * @param mixed $form_ids
	 * @param mixed $criteria (default: null)
	 * @param mixed &$total (default: null)
	 * @return void
	 */
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



if( !function_exists('gravityview_get_entry') ) {
	/**
	 * Return a single entry object
	 *
	 * @access public
	 * @param mixed $entry_id
	 * @return object or false
	 */
	function gravityview_get_entry( $entry_id ) {
		if( class_exists( 'GFAPI' ) && !empty( $entry_id ) ) {
			return GFAPI::get_entry( $entry_id );
		}
		return false;
	}

}



if( !function_exists('gravityview_get_field_label') ) {
	/**
	 * Retrieve the label of a given field id (for a specific form)
	 *
	 * @access public
	 * @param mixed $form
	 * @param mixed $field_id
	 * @return string
	 */
	function gravityview_get_field_label( $form, $field_id ) {

		if( empty($form) || empty( $field_id ) ) {
			return '';
		}

		$field = gravityview_get_field( $form, $field_id );
		return isset( $field['label'] ) ?  $field['label'] : '';

	}

}



if( !function_exists('gravityview_get_field') ) {
	/**
	 * Returns the field details array of a specific form given the field id
	 *
	 * @access public
	 * @param mixed $form
	 * @param mixed $field_id
	 * @return void
	 */
	function gravityview_get_field( $form, $field_id ) {

		if( empty($form) || empty( $field_id ) ) {
			return '';
		}

		foreach( $form['fields'] as $field ) {
			if( $field_id == $field['id'] ) {
				return $field;
			}
		}
		return '';
	}

}



