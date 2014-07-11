<?php
/**
 * GravityView template tags API
 *
 * @package   GravityView
 * @license   GPL2+
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
	public static function field_label( $field, $entry = NULL ) {
		global $gravityview_view;

		$form = $gravityview_view->form;

		if( !empty( $field['show_label'] ) ) {
			$label = empty( $field['custom_label'] ) ? $field['label'] : $field['custom_label'];
			$label = self::replace_variables( $label, $form, $entry );
			$label .= apply_filters( 'gravityview_render_after_label', '', $field );
		} else {
			$label = '';
		}

		return $label .' ';
	}

	/**
	 * Check for merge tags before passing to Gravity Forms to improve speed.
	 *
	 * GF doesn't check for {} before diving in. They not only replace fields, they do `str_replace()` on
	 * things like ip address, which is a lot of work just to check if there's any hint of a replacement variable.
	 *
	 * We check for the basics first.
	 *
	 * @param  string      $text       Text to replace variables in
	 * @param  array      $form        GF Form array
	 * @param  array      $entry        GF Entry array
	 * @param  boolean     $url_encode URL encode the output using `GFCommon::format_variable_value()`?
	 * @param  boolean     $esc_html   [description]
	 * @param  boolean     $nl2br      [description]
	 * @param  string      $format     [description]
	 * @return [type]                  [description]
	 */
	public static function replace_variables($text, $form, $entry ) {

		if( strpos( $text, '{') === false ) {
			return $text;
		}

		preg_match_all('/{[^{]*?:(\d+(\.\d+)?)(:(.*?))?}/mi', $text, $matches, PREG_SET_ORDER);

		if( empty( $matches ) ) {
			return $text;
		}

		return GFCommon::replace_variables( $text, $form, $entry, false, false, false, "html");
	}


	/**
	 * Fetch Field class
	 *
	 * @access public
	 * @static
	 * @param mixed $field
	 * @return string
	 */
	public static function field_class( $field, $form = NULL, $entry = NULL ) {
		global $gravityview_view;

		$classes = array();

		if( !empty( $field['custom_class'] ) ) {

			// We want the merge tag to be formatted as a class. The merge tag may be
			// replaced by a multiple-word value that should be output as a single class.
			// "Office Manager" should be formatted as `.office-manager`, not `.office` and `.manager`
			add_filter('gform_merge_tag_filter', 'sanitize_title');

			$custom_class = self::replace_variables( $field['custom_class'], $form, $entry );

			// And then we want life to return to normal
			remove_filter('gform_merge_tag_filter', 'sanitize_title');

			// And now we want the spaces to be handled nicely.
			$classes[] = gravityview_sanitize_html_class( $custom_class );

		}

		if(!empty($field['id'])) {
			if( !empty( $form ) && !empty( $form['id'] ) ) {
				$form_id = '-'.$form['id'];
			} else {
				$form_id = empty( $gravityview_view->form_id ) ? '' : '-'. $gravityview_view->form_id;
			}

			$classes[] = 'gv-field'.$form_id.'-'.$field['id'];
		}

		return esc_attr(implode(' ', $classes));
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

		$form = $gravityview_view->form;
		$field = gravityview_get_field( $form, $field_id );


		if( $field_type = RGFormsModel::get_input_type($field) ) {
			$field_type = $field['type'];
			$value = RGFormsModel::get_lead_field_value($entry, $field);
		} else {
			// For non-integer field types (`id`, `date_created`, etc.)
			$field_type = $field_id;
			$field['type'] = $field_id;
			$value = isset($entry[$field_type]) ? $entry[$field_type] : NULL;
		}

		$display_value = GFCommon::get_lead_field_display($field, $value, $entry["currency"], false, $format);
		$display_value = apply_filters("gform_entry_field_value", $display_value, $field, $entry, $form);
		$display_value = self::replace_variables( $display_value, $form, $entry );

		// Check whether the field exists in /includes/fields/{$field_type}.php
		// This can be overridden by user template files.
		$field_exists = $gravityview_view->locate_template("fields/{$field_type}.php");

		if( $field_exists ) {

			GravityView_Plugin::log_debug( sprintf('[field_value] Using template at %s', $field_exists) );

			// Set the field data to be available in the templates
			$gravityview_view->field_data = array(
				'form' => $form,
				'field_id' => $field_id,
				'field' => $field,
				'field_settings' => $field_settings,
				'value' => $value,
				'display_value' => $display_value,
				'format' => $format,
				'entry' => $entry,
			);

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
			$output = '<a href="'. $href .'">'. $output . '</a>';
		}

		$output = apply_filters( 'gravityview_field_entry_value', $output, $entry, $field_settings );

		// Free up the memory
		unset( $gravityview_view->field_data );

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

		if($gravityview_view->curr_start || $gravityview_view->curr_end || $gravityview_view->curr_search) {
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

		if( defined('DOING_AJAX') && DOING_AJAX ) {
			global $gravityview_view;
			$post_id = $gravityview_view->post_id;
		} else {
			global $post;
			$post_id = isset( $post->ID ) ? $post->ID : null;
		}

		if( !empty( $post_id ) ) {

			$query_arg_name = GravityView_frontend::get_entry_var_name();

			if( get_option('permalink_structure') ) {
				$href = trailingslashit( get_permalink( $post_id ) ) . $query_arg_name . '/'. $entry['id'] .'/';
			} else {
				$href = add_query_arg( $query_arg_name, $entry['id'], self::directory_link() );
			}

			return $href;
		}

		return '';
	}


}


// inside loop functions

function gv_label( $field, $entry = NULL ) {
	return GravityView_API::field_label( $field, $entry );
}

function gv_class( $field, $form = NULL, $entry = array() ) {
	return GravityView_API::field_class( $field, $form, $entry  );
}

/**
 * sanitize_html_class doesn't handle spaces (multiple classes). We remedy that.
 * @uses sanitize_html_class
 * @param  string      $string Text to sanitize
 * @return [type]              [description]
 */
function gravityview_sanitize_html_class( $classes ) {

	if( is_string( $classes ) ) {
		$classes = explode(' ', $classes );
	}

	// If someone passes something not string or array, we get outta here.
	if( !is_array( $classes ) ) { return $classes; }

	$classes = array_map( 'sanitize_title_with_dashes' , $classes );

	return implode( ' ', $classes );

}

function gv_value( $entry, $field) {
	$value = GravityView_API::field_value( $entry, $field );

	if( $value === '') {
		return apply_filters( 'gravityview_empty_value', '' );
	}

	return $value;
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

	if( empty($post) ) { return NULL; }

	$href = gv_directory_link();

	// calculate link label
	global $gravityview_view;
	$label = !empty( $gravityview_view->back_link_label ) ? $gravityview_view->back_link_label : __( '&larr; Go back', 'gravity-view' );

	// filter link label
	$label = apply_filters( 'gravityview_go_back_label', $label, $post );

	return '<a href="'. $href .'" id="gravityview_back_link">'. esc_html( $label ) . '</a>';

}

/**
 * Handle getting values for complex Gravity Forms fields
 *
 * If the field is complex, like a product, the field ID, for example, 11, won't exist. Instead,
 * it will be 11.1, 11.2, and 11.3. This handles being passed 11 and 11.2 with the same function.
 *
 * @param  array      $entry    GF entry array
 * @param  [type]      $field_id [description]
 * @return [type]                [description]
 */
function gravityview_get_field_value( $entry, $field_id, $display_value ) {

	if( floatval( $field_id ) === floor( floatval( $field_id ) ) ) {

		// For the complete field value as generated by Gravity Forms
		return $display_value;

	} else {

		// For one part of the address (City, ZIP, etc.)
		return $entry[ $field_id ];

	}

}

/**
 * Take a passed CSV of terms and generate a linked list of terms
 *
 * Gravity Forms passes categories as "Name:ID" so we handle that using the ID, which
 * is more accurate than checking the name, which is more likely to change.
 *
 * @param  string      $value    Existing value
 * @param  string      $taxonomy Type of term (`post_tag` or `category`)
 * @return string                CSV of linked terms
 */
function gravityview_convert_value_to_term_list( $value, $taxonomy = 'post_tag' ) {

	$output = array();

	$terms = explode( ', ', $value );

	foreach ($terms as $term_name ) {

		// If we're processing a category,
		if( $taxonomy === 'category' ) {

			// Use rgexplode to prevent errors if : doesn't exist
			list( $term_name, $term_id ) = rgexplode( ':', $value, 2 );

			// The explode was succesful; we have the category ID
			if( !empty( $term_id )) {
				$term = get_term_by( 'id', $term_id, $taxonomy );
			} else {
			// We have to fall back to the name
				$term = get_term_by( 'name', $term_name, $taxonomy );
			}

		} else {
			// Use the name of the tag to get the full term information
			$term = get_term_by( 'name', $term_name, $taxonomy );
		}

		// There's still a tag/category here.
		if( $term ) {

			$term_link = get_term_link( $term, $taxonomy );

			// If there was an error, continue to the next term.
			if ( is_wp_error( $term_link ) ) {
			    continue;
			}

			$output[] = '<a href="' . esc_url( $term_link ) . '">' . esc_html( $term->name ) . '</a>';
		}
	}

	return implode(', ', $output );
}

/**
 * Get the links for post_tags and post_category output based on post ID
 * @param  int      $post_id  The ID of the post
 * @param  boolean     $link     Add links or no?
 * @param  string      $taxonomy Taxonomy of term to fetch.
 * @return string                String with terms
 */
function gravityview_get_the_term_list( $post_id, $link = true, $taxonomy = 'post_tag' ) {

	$output = get_the_term_list( $post_id, $taxonomy, NULL, ', ' );

	if( empty( $link ) ) {
		return strip_tags( $output);
	}

	return $output;

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
