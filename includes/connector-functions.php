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




/**
 * Returns the form object for a given Form ID.
 * @see GVCommon::get_form()
 * @access public
 * @param mixed $form_id
 * @return mixed False: no form ID specified or Gravity Forms isn't active. Array: Form returned from Gravity Forms
 */
function gravityview_get_form( $form_id ) {
	return GVCommon::get_form( $form_id );
}


/**
 * Get the form array for an entry based only on the entry ID
 * @see GVCommon::get_form_from_entry_id
 * @param  int|string $entry_slug Entry slug
 * @return array           Gravity Forms form array
 */
function gravityview_get_form_from_entry_id( $entry_slug ) {
	return GVCommon::get_form_from_entry_id( $entry_slug );
}


/**
 * Alias of GFAPI::get_forms()
 *
 * @see GFAPI::get_forms()
 *
 * @since 1.19 Allow "any" $active status option
 *
 * @param bool|string $active Status of forms. Use `any` to get array of forms with any status. Default: `true`
 * @param bool $trash Include forms in trash? Default: `false`
 *
 * @return array Empty array if GFAPI class isn't available or no forms. Otherwise, the array of Forms
 */
function gravityview_get_forms( $active = true, $trash = false ) {
	return GVCommon::get_forms( $active, $trash );
}

/**
 * Return array of fields' id and label, for a given Form ID
 *
 * @see GVCommon::get_form_fields()
 * @access public
 * @param string|array $form_id (default: '') or $form object
 * @return array
 */
function gravityview_get_form_fields( $form = '', $add_default_properties = false, $include_parent_field = true ) {
	return GVCommon::get_form_fields( $form, $add_default_properties, $include_parent_field );
}

/**
 * get extra fields from entry meta
 * @param  string $form_id (default: '')
 * @return array
 */
function gravityview_get_entry_meta( $form_id, $only_default_column = true ) {
	return GVCommon::get_entry_meta( $form_id, $only_default_column );
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

/**
 * Return a single entry object
 *
 * Since 1.4, supports custom entry slugs. The way that GravityView fetches an entry based on the custom slug is by searching `gravityview_unique_id` meta. The `$entry_slug` is fetched by getting the current query var set by `is_single_entry()`
 *
 * @access public
 * @param mixed $entry_id
 * @param boolean $force_allow_ids Force the get_entry() method to allow passed entry IDs, even if the `gravityview_custom_entry_slug_allow_id` filter returns false.
 * @param boolean $check_entry_display Check whether the entry is visible for the current View configuration. Default: true {@since 1.14}
 * @return array|boolean
 */
function gravityview_get_entry( $entry_slug, $force_allow_ids = false, $check_entry_display = true ) {
	return GVCommon::get_entry( $entry_slug, $force_allow_ids, $check_entry_display );
}

/**
 * Retrieve the label of a given field id (for a specific form)
 *
 * @access public
 * @param mixed $form
 * @param mixed $field_id
 * @return string
 */
function gravityview_get_field_label( $form, $field_id, $field_value = '' ) {
	return GVCommon::get_field_label( $form, $field_id, $field_value );
}


/**
 * Returns the field details array of a specific form given the field id
 *
 * Alias of GFFormsModel::get_field
 *
 * @since 1.19 Allow passing form ID as well as form array
 *
 * @uses GVCommon::get_field
 * @see GFFormsModel::get_field
 * @access public
 * @param array|int $form Form array or ID
 * @param string|int $field_id
 * @return GF_Field|null Returns NULL if field with ID $field_id doesn't exist.
 */
function gravityview_get_field( $form, $field_id ) {
	return GVCommon::get_field( $form, $field_id );
}


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

/**
 * Placeholder until the recursive has_shortcode() patch is merged
 * @see https://core.trac.wordpress.org/ticket/26343#comment:10
 */
function gravityview_has_shortcode_r( $content, $tag = 'gravityview' ) {
	return GVCommon::has_shortcode_r( $content, $tag );
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
 * @see GVCommon::get_meta_form_id
 *
 * @param int $view_id ID of the View you want the form of
 *
 * @return false|string ID of the connected Form, if exists. Empty string if not. False if not the View ID isn't valid.
 */
function gravityview_get_form_id( $view_id ) {
	return GVCommon::get_meta_form_id( $view_id );
}

/**
 * Get the template ID (`list`, `table`, `datatables`, `map`) for a View
 *
 * @see GravityView_Template::template_id
 *
 * @param int $view_id The ID of the View to get the layout of
 *
 * @return string GravityView_Template::template_id value. Empty string if not.
 */
function gravityview_get_template_id( $post_id ) {
	return GVCommon::get_meta_template_id( $post_id );
}

/**
 * Get all the settings for a View
 *
 * @uses  \GV\View_Settings::defaults() Parses the settings with the plugin defaults as backups.
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
 * Get all available preset templates
 * @since 1.13.2
 * @return array Templates
 */
function gravityview_get_registered_templates() {

	/**
	 * @filter `gravityview_register_directory_template` Fetch available View templates
	 * @param array $templates Templates to show
	 */
	$templates = apply_filters( 'gravityview_register_directory_template', array() );

	return $templates;
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
 * @since 1.17.4 Added $apply_filter parameter
 *
 * @param  int $post_id View ID
 * @param  bool $apply_filter Whether to apply the `gravityview/configuration/fields` filter [Default: true]
 * @return array          Multi-array of fields with first level being the field zones. See code comment.
 */
function gravityview_get_directory_fields( $post_id, $apply_filter = true ) {
	return GVCommon::get_directory_fields( $post_id, $apply_filter );
}

/**
 * Get the widgets, as configured for a View
 *
 * @since 1.17.4
 *
 * @param int $post_id
 *
 * @return array
 */
function gravityview_get_directory_widgets( $post_id ) {
	return get_post_meta( $post_id, '_gravityview_directory_widgets', true );
}

/**
 * Set the widgets, as configured for a View
 *
 * @since 1.17.4
 *
 * @param int $post_id
 * @param array $widgets array of widgets
 *
 * @return int|bool
 */
function gravityview_set_directory_widgets( $post_id, $widgets = array() ) {
	return update_post_meta( $post_id, '_gravityview_directory_widgets', $widgets );
}

/**
 * Render dropdown (select) with the list of sortable fields from a form ID
 *
 * @access public
 * @param  int $formid Form ID
 * @param string $current Field ID of field used to sort
 * @return string         html
 */
function gravityview_get_sortable_fields( $formid, $current = '' ) {
	return GVCommon::get_sortable_fields( $formid, $current );
}


/**
 * Returns the GF Form field type for a certain field(id) of a form
 * @param  object $form     Gravity Forms form
 * @param  mixed $field_id Field ID or Field array
 * @return string field type
 */
function gravityview_get_field_type(  $form = null , $field_id = '' ) {
	return GVCommon::get_field_type(  $form, $field_id );
}


/**
 * Theme function to get a GravityView view
 *
 * @access public
 * @param string $view_id (default: '')
 * @param array $atts (default: array())
 * @return string HTML of the output. Empty string if $view_id is empty.
 */
function get_gravityview( $view_id = '', $atts = array() ) {
	if( !empty( $view_id ) ) {
		$atts['id'] = $view_id;
		$args = wp_parse_args( $atts, defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ? \GV\View_Settings::defaults() : GravityView_View_Data::get_default_args() );
		$GravityView_frontend = GravityView_frontend::getInstance();
		$GravityView_frontend->setGvOutputData( GravityView_View_Data::getInstance( $view_id ) );
		$GravityView_frontend->set_context_view_id( $view_id );
		$GravityView_frontend->set_entry_data();
		return $GravityView_frontend->render_view( $args );
	}
	return '';
}

/**
 * Theme function to render a GravityView view
 *
 * @access public
 * @param string $view_id (default: '')
 * @param array $atts (default: array())
 * @return void
 */
function the_gravityview( $view_id = '', $atts = array() ) {
	echo get_gravityview( $view_id, $atts );
}


/**
 * Theme function to identify if it is a Single Entry View
 *
 * @since  1.5.4
 * @return bool|string False if not, single entry slug if true
 */
function gravityview_is_single_entry() {
	return GravityView_frontend::is_single_entry();
}

/**
 * Determine whether a View has a single checkbox or single radio input
 * @see GravityView_frontend::add_scripts_and_styles()
 * @since 1.15
 * @param array $form Gravity Forms form
 * @param array $view_fields GravityView fields array
 */
function gravityview_view_has_single_checkbox_or_radio( $form, $view_fields ) {

	if( class_exists('GFFormsModel') && $form_fields = GFFormsModel::get_fields_by_type( $form, array( 'checkbox', 'radio' ) ) ) {

		/** @var GF_Field_Radio|GF_Field_Checkbox $form_field */
		foreach( $form_fields as $form_field ) {
			$field_id = $form_field->id;
			foreach( $view_fields as $zone ) {

				// ACF compatibility; ACF-added fields aren't arrays
				if ( ! is_array( $zone ) ) { continue; }

				foreach( $zone as $field ) {
					// If it's an input, not the parent and the parent ID matches a checkbox or radio
					if( ( strpos( $field['id'], '.' ) > 0 ) && floor( $field['id'] ) === floor( $field_id ) ) {
						return true;
					}
				}
			}
		}
	}

	return false;
}