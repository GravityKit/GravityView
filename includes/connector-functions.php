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
	 * @return mixed False: no form ID specified or Gravity Forms isn't active. Array: Form returned from Gravity Forms
	 */
	function gravityview_get_form( $form_id ) {
		return GVCommon::get_form( $form_id );
	}

}


if( !function_exists('gravityview_get_form_from_entry_id') ) {
	/**
	 * Get the form array for an entry based only on the entry ID
	 * @param  int|string $entry_slug Entry slug
	 * @return array           Gravity Forms form array
	 */
	function gravityview_get_form_from_entry_id( $entry_slug ) {
		return GVCommon::get_form_from_entry_id( $entry_slug );
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
		return GVCommon::get_forms();
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
	function gravityview_get_form_fields( $form = '', $add_default_properties = false, $include_parent_field = true ) {
		return GVCommon::get_form_fields( $form, $add_default_properties, $include_parent_field );
	}
}


if( !function_exists( 'gravityview_get_entry_meta' ) ) {
	/**
	 * get extra fields from entry meta
	 * @param  string $form_id (default: '')
	 * @return array
	 */
	function gravityview_get_entry_meta( $form_id, $only_default_column = true ) {
		return GVCommon::get_entry_meta( $form_id, $only_default_column );
	}
}

/**
 * Wrapper for the Gravity Forms GFFormsModel::search_lead_ids() method
 *
 * @see  GFEntryList::leads_page()
 * @param  int $form_id ID of the Gravity Forms form
 * @since  1.1.6
 * @return array          Array of entry IDs
 */
function gravityview_get_entry_ids( $form_id, $search_criteria = array() ) {
	return GVCommon::get_entry_ids( $form_id, $search_criteria );
}


if( !function_exists('gravityview_get_entries') ) {
	/**
	 * Retrieve entries given search, sort, paging criteria
	 *
	 * @see  GFAPI::get_entries()
	 * @see GFFormsModel::get_field_filters_where()
	 * @access public
	 * @param int|array $form_ids The ID of the form or an array IDs of the Forms. Zero for all forms.
	 * @param mixed $passed_criteria (default: null)
	 * @param mixed &$total (default: null)
	 * @return mixed False: Error fetching entries. Array: Multi-dimensional array of Gravity Forms entry arrays
	 */
	function gravityview_get_entries( $form_ids = null, $passed_criteria = null, &$total = null ) {
		return GVCommon::get_entries( $form_ids, $passed_criteria, $total );
	}

}


if( !function_exists('gravityview_get_entry') ) {

	/**
	 * Return a single entry object
	 *
	 * Since 1.4, supports custom entry slugs. The way that GravityView fetches an entry based on the custom slug is by searching `gravityview_unique_id` meta. The `$entry_slug` is fetched by getting the current query var set by `is_single_entry()`
	 *
	 * @access public
	 * @param mixed $entry_id
	 * @param boolean $force_allow_ids Force the get_entry() method to allow passed entry IDs, even if the `gravityview_custom_entry_slug_allow_id` filter returns false.
	 * @return object or false
	 */
	function gravityview_get_entry( $entry_slug, $force_allow_ids = false ) {
		return GVCommon::get_entry( $entry_slug, $force_allow_ids );
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
		return GVCommon::get_field_label( $form, $field_id );
	}

}



if( !function_exists('gravityview_get_field') ) {
	/**
	 * Returns the field details array of a specific form given the field id
	 *
	 * @access public
	 * @param mixed $form
	 * @param mixed $field_id
	 * @return array
	 */
	function gravityview_get_field( $form, $field_id ) {
		return GVCommon::get_field( $form, $field_id );
	}

}


if( !function_exists('has_gravityview_shortcode') ) {

	/**
	 * Check whether the post is GravityView
	 *
	 * - Check post type. Is it `gravityview`?
	 * - Check shortcode
	 *
	 * @param  WP_Post      $post WordPress post object
	 * @return boolean           True: yep, GravityView; No: not!
	 */
	function has_gravityview_shortcode( $post = NULL ) {
		return GVCommon::has_gravityview_shortcode( $post );
	}
}

if( !function_exists( 'gravityview_has_shortcode_r') ) {
	/**
	 * Placeholder until the recursive has_shortcode() patch is merged
	 * @link https://core.trac.wordpress.org/ticket/26343#comment:10
	 */
	function gravityview_has_shortcode_r( $content, $tag = 'gravityview' ) {
		return GVCommon::has_shortcode_r( $content, $tag );
	}
}

/**
 * Get the views for a particular form
 * @param  int $form_id Gravity Forms form ID
 * @return array          Array with view details
 */
function gravityview_get_connected_views( $form_id ) {
	return GVCommon::get_connected_views( $form_id );
}

/**
 * Get the connected form ID from a View ID
 *
 * @param int $view_id ID of the View you want the form of
 *
 * @return int
 */
function gravityview_get_form_id( $view_id ) {
	return GVCommon::get_meta_form_id( $view_id );
}

function gravityview_get_template_id( $post_id ) {
	return GVCommon::get_meta_template_id( $post_id );
}

/**
 * Get all the settings for a View
 *
 * @uses  GravityView_View_Data::get_default_args() Parses the settings with the plugin defaults as backups.
 * @param  int $post_id View ID
 * @return array          Associative array of settings with plugin defaults used if not set by the View
 */
function gravityview_get_template_settings( $post_id ) {
	return GVCommon::get_template_settings( $post_id );
}

/**
 * Get the setting for a View
 *
 * If the setting isn't set by the View, it returns the plugin default.
 *
 * @param  int $post_id View ID
 * @param  string $key     Key for the setting
 * @return mixed|null          Setting value, or NULL if not set.
 */
function gravityview_get_template_setting( $post_id, $key ) {
	return GVCommon::get_template_setting( $post_id, $key );
}

/**
 * Get the field configuration for the View
 *
 * array(
 *
 * 	[other zones]
 *
 * 	'directory_list-title' => array(
 *
 *   	[other fields]
 *
 *  	'5372653f25d44' => array(
 *  		'id' => string '9' (length=1)
 *  		'label' => string 'Screenshots' (length=11)
 *			'show_label' => string '1' (length=1)
 *			'custom_label' => string '' (length=0)
 *			'custom_class' => string 'gv-gallery' (length=10)
 * 			'only_loggedin' => string '0' (length=1)
 *			'only_loggedin_cap' => string 'read' (length=4)
 *  	)
 *
 * 		[other fields]
 *  )
 *
 * 	[other zones]
 * )
 *
 * @param  int $post_id View ID
 * @return array          Multi-array of fields with first level being the field zones. See code comment.
 */
function gravityview_get_directory_fields( $post_id ) {
	return GVCommon::get_directory_fields( $post_id );
}

if( !function_exists('gravityview_get_sortable_fields') ) {

	/**
	 * Render dropdown (select) with the list of sortable fields from a form ID
	 *
	 * @access public
	 * @param  int $formid Form ID
	 * @return string         html
	 */
	function gravityview_get_sortable_fields( $formid, $current = '' ) {
		return GVCommon::get_sortable_fields( $formid, $current );
	}

}

if( !function_exists('gravityview_get_field_type') ) {

	/**
	 * Returns the GF Form field type for a certain field(id) of a form
	 * @param  object $form     Gravity Forms form
	 * @param  mixed $field_id Field ID or Field array
	 * @return string field type
	 */
	function gravityview_get_field_type(  $form = null , $field_id = '' ) {

		return GVCommon::get_field_type(  $form, $field_id );

	}


}
