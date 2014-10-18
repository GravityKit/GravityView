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

		return $label;
	}

	/**
	 * Check for merge tags before passing to Gravity Forms to improve speed.
	 *
	 * GF doesn't check for whether `{` exists before it starts diving in. They not only replace fields, they do `str_replace()` on things like ip address, which is a lot of work just to check if there's any hint of a replacement variable.
	 *
	 * We check for the basics first, which is more efficient.
	 *
	 * @param  string      $text       Text to replace variables in
	 * @param  array      $form        GF Form array
	 * @param  array      $entry        GF Entry array
	 * @return string                  Text with variables maybe replaced
	 */
	public static function replace_variables($text, $form, $entry ) {

		if( strpos( $text, '{') === false ) {
			return $text;
		}

		// Check for fields
		preg_match_all('/{[^{]*?:(\d+(\.\d+)?)(:(.*?))?}/mi', $text, $matches, PREG_SET_ORDER);
		if( empty( $matches ) ) {

			// Check for form variables
			if( !preg_match( '/{(all_fields|pricing_fields|form_title|entry_url|ip|post_id|admin_email|post_edit_url|form_id|entry_id)}/ism', $text ) ) {
				return $text;
			}
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

		if( !empty( $field['custom_class'] ) && !empty( $entry ) ) {

			// We want the merge tag to be formatted as a class. The merge tag may be
			// replaced by a multiple-word value that should be output as a single class.
			// "Office Manager" will be formatted as `.OfficeManager`, not `.Office` and `.Manager`
			add_filter('gform_merge_tag_filter', 'sanitize_html_class');

			$custom_class = self::replace_variables( $field['custom_class'], $form, $entry );

			// And then we want life to return to normal
			remove_filter('gform_merge_tag_filter', 'sanitize_html_class');

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

		/**
		 * Gravity Forms' GFCache function was thrashing the database, causing double the amount of time for the field_value() method to run.
		 *
		 * The reason is that the cache was checking against a field value stored in a transient every time `GFFormsModel::get_lead_field_value()` is called.
		 *
		 * What we're doing here is telling the GFCache that it's already checked the transient and the value is false, forcing it to just use the non-cached data, which is actually faster.
		 *
		 * @hack
		 * @param  string $cache_key Field Value transient key used by Gravity Forms
		 * @param mixed false Setting the value of the cache to false so that it's not used by Gravity Forms' GFFormsModel::get_lead_field_value() method
		 * @param boolean false Tell Gravity Forms not to store this as a transient
		 * @param  int 0 Time to store the value. 0 is maximum amount of time possible.
		 */
		GFCache::set( "GFFormsModel::get_lead_field_value_" . $entry["id"] . "_" . $field_settings["id"], false, false, 0 );

		$field_id = $field_settings['id'];

		$output = '';

		$form = $gravityview_view->form;

		$field = gravityview_get_field( $form, $field_id );

		$field_type = RGFormsModel::get_input_type($field);

		if( $field_type ) {
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

		if( $field_exists ) {

			do_action( 'gravityview_log_debug', sprintf('[field_value] Rendering %s Field', $field_type ), $field_exists );

			ob_start();

			load_template( $field_exists, false );

			$output = ob_get_clean();

		} else {

			// Backup; the field template doesn't exist.
			$output = $display_value;

		}

		/**
		 * Link to the single entry by wrapping the output in an anchor tag
		 *
		 * Fields can override this by modifying the field data variable inside the field. See /templates/fields/post_image.php for an example.
		 *
		 * @todo Move into its own function usingt `gravityview_field_entry_value` to tap in
		 */
		if( !empty( $gravityview_view->field_data['field_settings']['show_as_link'] ) ) {

			$href = self::entry_link( $entry, $field );

			$link = '<a href="'. $href .'">'. $output . '</a>';

			/**
			 * Modify the link format
			 * @param string $link HTML output of the link
			 * @param string $href URL of the link
			 * @param array  $entry The GF entry array
			 * @param  array $field_settings Settings for the particular GV field
			 */
			$output = apply_filters( 'gravityview_field_entry_link', $link, $href, $entry, $field_settings );
		}

		/**
		 * Modify the field value output
		 * @param string $output HTML value output
		 * @param array  $entry The GF entry array
		 * @param  array $field_settings Settings for the particular GV field
		 */
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
			$output = __('This search returned no results.', 'gravityview');
		} else {
			$output = __('No entries match your request.', 'gravityview');
		}

		$output = apply_filters( 'gravitview_no_entries_text', $output, $is_search);

		return $wpautop ? wpautop($output) : $output;
	}

	/**
	 * Generate a link to the Directory view
	 *
	 * Uses `wp_cache_get` and `wp_cache_get` (since 1.2.1) to speed up repeated requests to get permalink, which improves load time. Since we may be doing this hundreds of times per request, it adds up!
	 *
	 * @param int $post_id Post ID
	 * @return string      Permalink to multiple entries view
	 */
	public static function directory_link( $post_id = NULL ) {
		global $post;

		if( empty( $post_id ) ) {
			$post_id = is_a( $post, 'WP_Post' ) ? $post->ID : NULL;
		}

		if( empty( $post_id ) ) {
			return NULL;
		}

		// If we've saved the permalink in memory, use it
		// @since 1.2.1
		if( $link = wp_cache_get( 'gv_directory_link_'.$post_id ) ) {
			return $link;
		}

		$link = get_permalink( $post_id );

		// Deal with returning to proper pagination for embedded views
		if( !empty( $_GET['pagenum'] ) && is_numeric( $_GET['pagenum'] ) ) {
			$link = add_query_arg('pagenum', $_GET['pagenum'], $link );
		}

		// If not yet saved, cache the permalink.
		// @since 1.2.1
		wp_cache_set( 'gv_directory_link_'.$post_id, $link );

		return $link;
	}


	// return href for single entry
	public static function entry_link( $entry ) {

		if( defined('DOING_AJAX') && DOING_AJAX ) {
			global $gravityview_view;
			$post_id = isset( $_POST['post_id'] ) ? (int)$_POST['post_id'] : '';
		} else {
			global $post;
			$post_id = isset( $post->ID ) ? $post->ID : null;
		}

		// No post ID, get outta here.
		if( empty( $post_id ) ) { return ''; }

		$query_arg_name = GravityView_Post_Types::get_entry_var_name();

		// Get the permalink to the View
		$directory_link = self::directory_link( $post_id );

		if( get_option('permalink_structure') ) {

			$args = array();

			$directory_link = trailingslashit( $directory_link ) . $query_arg_name . '/'. $entry['id'] .'/';

		} else {

			$args = array( $query_arg_name => $entry['id'] );
		}

		return add_query_arg( $args, $directory_link );

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

	$classes = array_map( 'sanitize_html_class' , $classes );

	return implode( ' ', $classes );

}

function gv_value( $entry, $field ) {

	$value = GravityView_API::field_value( $entry, $field );

	if( $value === '') {
		return apply_filters( 'gravityview_empty_value', '' );
	}

	return $value;
}

function gv_directory_link( $post = NULL ) {
	return GravityView_API::directory_link( $post = NULL );
}

function gv_entry_link( $entry ) {
	return GravityView_API::entry_link( $entry );
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

	$href = gv_directory_link();

	if( empty( $href ) ) { return NULL; }

	// calculate link label
	global $gravityview_view;
	$label = !empty( $gravityview_view->back_link_label ) ? $gravityview_view->back_link_label : __( '&larr; Go back', 'gravityview' );

	// filter link label
	$label = apply_filters( 'gravityview_go_back_label', $label );

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
 * @param  string 	$display_value The value generated by Gravity Forms
 * @return string                Value
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

if( !function_exists( 'gravityview_format_link' ) ) {

/**
 * Convert a whole link into a shorter link for display
 * @param  [type] $value [description]
 * @return [type]        [description]
 */
function gravityview_format_link($value = null) {

	if(apply_filters('gravityview_anchor_text_striphttp', true)) {
		$value = str_replace('http://', '', $value);
		$value = str_replace('https://', '', $value);
	}

	if(apply_filters('gravityview_anchor_text_stripwww', true)) {
		$value = str_replace('www.', '', $value);
	}
	if(apply_filters('gravityview_anchor_text_rootonly', true)) {
		$value = preg_replace('/(.*?)\/(.+)/ism', '$1', $value);
	}
	if(apply_filters('gravityview_anchor_text_nosubdomain', true)) {
		$value = preg_replace('/((.*?)\.)+(.*?)\.(.*?)/ism', '$3.$4', $value);
	}
	if(apply_filters('gravityview_anchor_text_noquerystring', true)) {
		$ary = explode("?", $value);
		$value = $ary[0];
	}
	return $value;
}

}

/**
 * Get all views processed so far for the current page load
 *
 * @see  GravityView_View_Data::add_view()
 * @return array Array of View data, each View data with `id`, `view_id`, `form_id`, `template_id`, `atts`, `fields`, `widgets`, `form` keys.
 */
function gravityview_get_current_views() {

	$fe = GravityView_frontend::getInstance();

	// Solve problem when loading content via admin-ajax.php
	if( empty( $fe->gv_output_data ) ) {

		do_action( 'gravityview_log_debug', '[gravityview_get_current_views] gv_output_data not defined; parsing content.' );

		$fe->parse_content();
	}

	// Make 100% sure that we're dealing with a properly called situation
	if( !isset( $fe->gv_output_data ) || !is_a( $fe->gv_output_data, 'GravityView_View_Data' ) ) {

		do_action( 'gravityview_log_debug', '[gravityview_get_current_views] gv_output_data not an object or get_view not callable.', $this->gv_output_data );

		return array();
	}

	return $fe->gv_output_data->get_views();
}

/**
 * Get data for a specific view
 *
 * @see  GravityView_View_Data::get_view()
 * @return array View data with `id`, `view_id`, `form_id`, `template_id`, `atts`, `fields`, `widgets`, `form` keys.
 */
function gravityview_get_current_view_data( $view_id ) {

	$fe = GravityView_frontend::getInstance();

	if( empty( $fe->gv_output_data ) ) { return array(); }

	return $fe->gv_output_data->get_view( $view_id );
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


/**
 * Return an array of files prepared for output. Wrapper for GravityView_Field_FileUpload::get_files_array()
 *
 * Processes files by file type and generates unique output for each.
 *
 * Returns array for each file, with the following keys:
 *
 * `file_path` => The file path of the file, with a line break
 * `html` => The file output HTML formatted
 *
 * @see GravityView_Field_FileUpload::get_files_array()
 *
 * @since  1.2
 * @param  string $value    Field value passed by Gravity Forms. String of file URL, or serialized string of file URL array
 * @param  string $gv_class Field class to add to the output HTML
 * @return array           Array of file output, with `file_path` and `html` keys (see comments above)
 */
function gravityview_get_files_array( $value, $gv_class = '' ) {

	if( !class_exists( 'GravityView_Field ' ) ) {
		include_once( GRAVITYVIEW_DIR .'includes/fields/class.field.php' );
	}

	if( !class_exists( 'GravityView_Field_FileUpload ' ) ) {
		include_once( GRAVITYVIEW_DIR .'includes/fields/fileupload.php' );
	}

	return GravityView_Field_FileUpload::get_files_array( $value, $gv_class );
}

if( !function_exists( 'gravityview_get_map_link' ) ) {

/**
 * Generate a mapping link from an address
 *
 * The address should be plain text with new line (`\n`) or `<br />` line breaks separating sections
 *
 * @link https://gravityview.co/support/documentation/201608159 Read how to modify the link
 * @param  string $address Address
 * @return string          URL of link to map of address
 */
function gravityview_get_map_link( $address ) {

	$address_qs = str_replace( array( '<br />', "\n" ), ' ', $address ); // Replace \n with spaces
	$address_qs = urlencode( $address_qs );

	$url = "https://maps.google.com/maps?q={$address_qs}";

	// Generate HTML tag
	$link = sprintf( '<a href="%s" class="map-it-link">%s</a>', esc_url( $url ), esc_html__( 'Map It', 'gravityview' ) );

	/**
	 * Modify the map link generated. You can use a different mapping service, for example.
	 *
	 * @param  string $link Map link
	 * @param string $address Address to generate link for
	 * @param string $url URL generated by the function
	 * @var string
	 */
	$link = apply_filters( 'gravityview_map_link', $link, $address, $url );

	return $link;
}

}

/**
 * Output field based on a certain html markup
 *
 *   markup - string to be used on a sprintf statement.
 *      Use:
 *         {{label}} - field label
 *         {{value}} - entry field value
 *         {{class}} - field class
 *
 *   wpautop - true will filter the value using wpautop function
 *
 * @since  1.1.5
 * @param  array $args Associative array with field data. `entry`, `field` and `form` are required.
 * @return string
 */
function gravityview_field_output( $args ) {

	$args = wp_parse_args( $args, array(
		'entry' => NULL,
		'field' => NULL,
		'form' => NULL,
		'hide_empty' => true,
		'markup' => '<div class="{{class}}">{{label}}{{value}}</div>',
		'label_markup' => '',
		'wpautop' => false
	) );

	// Required fields.
	if( empty( $args['entry'] ) || empty( $args['field'] ) || empty( $args['form'] ) ) {
		do_action( 'gravityview_log_error', '[gravityview_field_output] Entry, field, or form are empty.', $args );
		return '';
	}

	$value = gv_value( $args['entry'], $args['field'] );

	// If the value is empty and we're hiding empty, return empty.
	if( $value === '' && !empty( $args['hide_empty'] ) ) { return ''; }

	if( !empty( $args['wpautop'] ) ) {
		$value = wpautop( $value );
	}

	$class = gv_class( $args['field'], $args['form'], $args['entry'] );

	$label = esc_html( gv_label( $args['field'], $args['entry'] ) );

	if( !empty( $label ) ) {
		// If the label markup is overridden
		if( !empty( $args['label_markup'] ) ) {
			$label = str_replace( '{{label}}', '<span class="gv-field-label">' . $label . '</span>', $args['label_markup'] );
		} else {
			$args['markup'] =  str_replace( '{{label}}', '<span class="gv-field-label">{{label}}</span>', $args['markup'] );
		}
	}

	$html = $args['markup'];
	$html = str_replace( '{{class}}', $class, $html );
	$html = str_replace( '{{label}}', $label, $html );
	$html = str_replace( '{{value}}', $value, $html );

	/**
	 * Modify the output
	 * @param string $html Existing HTML output
	 * @param array $args Arguments passed to the function
	 */
	$html = apply_filters( 'gravityview_field_output', $html, $args );

	return $html;
}


function gv_selected( $value, $current, $echo = true, $type = 'selected' ) {

	$output = '';
	if( is_array( $current ) ) {
		if( in_array( $value, $current ) ) {
			$output = __checked_selected_helper( true, true, false, $type );
		}
	} else {
		$output = __checked_selected_helper( $value, $current, false, $type );
	}

	if( $echo ) {
		echo $output;
	} else {
		return $output;
	}

}
