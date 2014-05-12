<?php
/**
 * GravityView template tags API
 *
 * @package   GravityView
 * @license   GPL3+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */



class GravityView_API {



	/**
	 * Fetch Field Label
	 *
	 * @access public
	 * @static
	 * @param mixed $field
	 * @return string
	 */
	public static function field_label( $field ) {

		if( !empty( $field['show_label'] ) ) {
			$label = empty( $field['custom_label'] ) ? $field['label'] : $field['custom_label'];
			$label .= apply_filters( 'gravityview_render_after_label', '', $field );
		} else {
			$label = '';
		}

		return $label .' ';

	}


	/**
	 * Fetch Field class
	 *
	 * @access public
	 * @static
	 * @param mixed $field
	 * @return string
	 */
	public static function field_class( $field ) {

		if( !empty( $field['custom_class'] ) ) {
			return sanitize_html_class($field['custom_class'], esc_attr($field['custom_class']));
		}

		return '';
	}


	/**
	 * Given an entry and a form field id, calculate the entry value for that field.
	 *
	 * @access public
	 * @param array $entry
	 * @param integer $field
	 * @return null|string
	 */
	public static function field_value( $entry, $field_settings, $format = 'html') {
		global $gravityview_view;

		if( empty( $entry['form_id'] ) || empty( $field_settings['id'] ) ) {
			return NULL;
		}

		$field_id = $field_settings['id'];

		$output = '';

		$form = gravityview_get_form( $entry['form_id'] );
		$field = gravityview_get_field( $form, $field_id );


		if( $field_type = RGFormsModel::get_input_type($field) ) {
			$value = RGFormsModel::get_lead_field_value($entry, $field);
		} else {
			// For non-integer field types (`id`, `date_created`, etc.)
			$field_type = $field_id;
			$field['type'] = $field_id;
			$value = isset($entry[$field_type]) ? $entry[$field_type] : NULL;
		}

		$display_value = GFCommon::get_lead_field_display($field, $value, $entry["currency"], false, $format);
		$display_value = apply_filters("gform_entry_field_value", $display_value, $field, $entry, $form);


		// Check whether the field exists in /includes/fields/{$field_type}.php
		// This can be overridden by user template files.
		$field_exists = $gravityview_view->locate_template("fields/{$field_type}.php");

		if($field_exists) {

			GravityView_Plugin::log_debug( sprintf('[field_value] Using template at %s', $field_exists) );

			// Set the field data to be available in the templates
			$gravityview_view->__set('field_data', array(
				'form' => $form,
				'field_id' => $field_id,
				'field' => $field,
				'field_settings' => $field_settings,
				'value' => $value,
				'display_value' => $display_value,
				'format' => $format,
			));

			ob_start();

			load_template( $field_exists, false );

			$output = ob_get_clean();

		} else {

			// Backup; the field template doesn't exist.
			$output = $display_value;

		}


		//if show as single entry link is active
		if( !empty( $field_settings['show_as_link'] ) ) {
			$href = self::entry_link($entry, $field);
			$output = '<a href="'. $href .'">'. $value . '</a>';
		}

		$output = apply_filters( 'gravityview_field_entry_value', $output, $entry, $field_settings );

		// Free up the memory
		$gravityview_view->__unset('field_data');

		return $output;
	}

	/**
	 * Get the "No Results" text depending on whether there were results.
	 * @param  boolean     $wpautop Apply wpautop() to the output?
	 * @return string               HTML of "no results" text
	 */
	public static function no_results($wpautop = true) {
		global $gravityview_view;

		$is_search = false;

		if($gravityview_view->__get('curr_start') || $gravityview_view->__get('curr_end') || $gravityview_view->__get('curr_search')) {
			$is_search = true;
		}

		if($is_search) {
			$output = __("This search returned no results.", "gravity-view");
		} else {
			$output = __("No entries match your request.", "gravity-view");
		}

		$output = apply_filters( 'gravitview_no_entries_text', $output, $is_search);

		return $wpautop ? wpautop($output) : $output;
	}

	/**
	 * Generate a link to the Directory view
	 *
	 * @return string      Permalink to multiple entries view
	 */
	public static function directory_link($post = NULL) {

		if(empty($post)) {
			$post = get_post();
		}

		if( empty( $post ) ) {
			return NULL;
		}

		return trailingslashit( get_permalink( $post->ID ) );
	}


	// return href for single entry
	public static function entry_link( $entry, $field ) {

		$post = get_post();

		if( !empty( $post ) ) {

			$query_arg_name = GravityView_frontend::get_entry_var_name();

			if( get_option('permalink_structure') ) {
				$href = trailingslashit( get_permalink( $post->ID ) ) . $query_arg_name . '/'. $entry['id'] .'/';
			} else {
				$href = add_query_arg( $query_arg_name, $entry['id'], self::directory_link() );
			}

			return $href;
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

function gv_directory_link() {
	return GravityView_API::directory_link();
}

function gv_entry_link(  $entry, $field ) {
	return GravityView_API::entry_link( $entry, $field );
}

function gv_no_results($wpautop = true) {
	return GravityView_API::no_results( $wpautop );
}

/**
 * Generate HTML for the back link from single entry view
 * @filter gravityview_go_back_label Modify the back label text
 * @return string|null      If no GV post exists, null. Otherwise, HTML string of back link.
 */
function gravityview_back_link() {

	$post = get_post();

	if(empty($post)) { return NULL; }

	$href = gv_directory_link();

	$label = apply_filters( 'gravityview_go_back_label', __( '&larr; Go back', 'gravity-view' ), $post );

	return '<a href="'. $href .'" id="gravityview_back_link">'. esc_html( $label ) . '</a>';

}



// Templates' hooks
function gravityview_before() {
	do_action( 'gravityview_before', gravityview_get_view_id() );
}

function gravityview_header() {

	do_action( 'gravityview_header', gravityview_get_view_id() );
}

function gravityview_footer() {
	do_action( 'gravityview_footer', gravityview_get_view_id() );
}

function gravityview_after() {
	do_action( 'gravityview_after', gravityview_get_view_id() );
}

function gravityview_get_view_id() {
	global $gravityview_view;
	return $gravityview_view->view_id;
}

function gravityview_get_context() {
	global $gravityview_view;
	return $gravityview_view->context;
}
