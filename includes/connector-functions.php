<?php
/**
 * Set of functions to separate main plugin from Gravity Forms API and other methods
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
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
		if(empty( $form_id ) ) {
			return false;
		}

		if(class_exists( 'GFAPI' )) {
			return GFAPI::get_form( $form_id );
		}

		if(class_exists( 'RGFormsModel' )) {
			return RGFormsModel::get_form( $form_id );
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
	 * @param string|array $form_id (default: '') or $form object
	 * @return array
	 */
	function gravityview_get_form_fields( $form = '', $add_default_properties = false ) {

		if( !is_array( $form ) ) {
			$form = gravityview_get_form( $form );
		}

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

	    return $fields;
	}
}


if( !function_exists('gravityview_get_entries') ) {
	/**
	 * Retrieve entries given search, sort, paging criteria
	 *
	 * @access public
	 * @param mixed $form_ids
	 * @param mixed $passed_criteria (default: null)
	 * @param mixed &$total (default: null)
	 * @return void
	 */
	function gravityview_get_entries( $form_ids, $passed_criteria = null, &$total = null ) {

		$search_criteria_defaults = array(
			'search_criteria' => null,
			'sorting' => null,
			'paging' => null
		);

		$criteria = wp_parse_args( $passed_criteria, $search_criteria_defaults );

		extract( $criteria );

		if( class_exists( 'GFAPI' ) && !empty( $form_ids ) ) {
			return GFAPI::get_entries( $form_ids, $search_criteria, $sorting, $paging, $total);
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
		return GFFormsModel::get_field($form, $field_id);
	}

}



if( !function_exists('gravityview_get_sortable_fields') ) {

	/**
	 * Render dropdown (select) with the list of sortable fields from a form ID
	 *
	 * @access public
	 * @param  int $formid Form ID
	 * @return string         html
	 */
	function gravityview_get_sortable_fields( $formid ) {

		if( empty( $formid ) ) {
			return '';
		}

		$fields = gravityview_get_form_fields( $formid );
		$output = '';

		if( !empty( $fields ) ) {

			$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array() );

			$output .= '<option value="">'. esc_html__( 'Default', 'gravity-view') .'</option>';
			$output .= '<option value="date_created">'. esc_html__( 'Date Created', 'gravity-view' ) .'</option>';
			foreach( $fields as $id => $field ) {
				if( in_array( $field['type'], $blacklist_field_types ) ) {
					continue;
				}
				$output .= '<option value="'. $id .'">'. $field['label'] .'</option>';
			}

		}
		return $output;
	}

}






