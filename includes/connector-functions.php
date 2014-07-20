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

if( !function_exists('gravityview_get_form_from_entry_id') ) {

	function gravityview_get_form_from_entry_id( $entry_id ) {

		$entry = gravityview_get_entry( $entry );

		$form = gravityview_get_form( $entry['form_id'] );

		return $form;
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
			return $forms;
		}
		return false;
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

		if( !is_array( $form ) ) {
			$form = gravityview_get_form( $form );
		}

		$fields = array();

		if( $add_default_properties ) {
			$form = RGFormsModel::add_default_properties( $form );
		}

		if( $form ) {
			foreach( $form['fields'] as $field ) {

				if( $include_parent_field || empty( $field['inputs'] ) ) {
					$fields[ $field['id'] ] = array( 'label' => $field['label'], 'type' => $field['type'] );
				}

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

		// Prepare date formats to be in Gravity Forms DB format
		foreach( array('start_date', 'end_date' ) as $key ) {

			if( !empty( $criteria['search_criteria'][ $key ] ) ) {

				// Use date_create instead of new DateTime so it returns false if invalid date format.
				$date = date_create( $criteria['search_criteria'][ $key ] );

				if( $date ) {
					$criteria['search_criteria'][ $key ] = $date->format('Y-m-d H:i:s');
				} else {
					do_action( 'gravityview_log_error', '[gravityview_get_entries] '.$key.' Date format not valid:', $criteria['search_criteria'][ $key ] );
				}
			}
		}

		do_action( 'gravityview_log_debug', '[gravityview_get_entries] Final Parameters', $criteria );

		if( class_exists( 'GFAPI' ) && !empty( $form_ids ) ) {
			return GFAPI::get_entries( $form_ids, $criteria['search_criteria'], $criteria['sorting'], $criteria['paging'], $total );
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

		if( !is_a( $post, 'WP_Post' ) ) {
			return false;
		}

		if( 'gravityview' === get_post_type( $post ) ) {
			return true;
		}

		if( $shortcode = gravityview_has_shortcode_r( $post->post_content, 'gravityview') ) {
			return $shortcode;
		}

		return false;
	}
}

if( !function_exists( 'gravityview_has_shortcode_r') ) {
	/**
	 * Placeholder until the recursive has_shortcode() patch is merged
	 * @link https://core.trac.wordpress.org/ticket/26343#comment:10
	 */
	function gravityview_has_shortcode_r( $content, $tag ) {
		if ( false === strpos( $content, '[' ) ) {
			return false;
		}

		if ( shortcode_exists( $tag ) ) {

			$shortcodes = array();

			preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );
			if ( empty( $matches ) )
				return false;

			foreach ( $matches as $shortcode ) {
				if ( $tag === $shortcode[2] ) {

					// Changed this to $shortcode instead of true so we get the parsed atts.
					$shortcodes[] = $shortcode;

				} else if ( isset( $shortcode[5] ) && $result = gravityview_has_shortcode_r( $shortcode[5], $tag ) ) {
					$shortcodes[] = $result;
				}
			}
			return $shortcodes;
		}
		return false;
	}
}

/**
 * Get the views for a particular form
 * @param  int $form_id Gravity Forms form ID
 * @return array          Array with view details
 */
function gravityview_get_connected_views( $form_id ) {

	$views = get_posts(array(
		'post_type' => 'gravityview',
		'posts_per_page' => -1,
		'meta_key' => '_gravityview_form_id',
		'meta_value' => (int)$form_id,
	));

	return $views;
}

function gravityview_get_form_id( $post_id ) {
	return get_post_meta( $post_id, '_gravityview_form_id', true );
}

function gravityview_get_template_id( $post_id ) {
	return get_post_meta( $post_id, '_gravityview_directory_template', true );
}

function gravityview_get_template_settings( $post_id ) {
	return get_post_meta( $post_id, '_gravityview_template_settings', true );
}

function gravityview_get_directory_fields( $post_id ) {
	return get_post_meta( $post_id, '_gravityview_directory_fields', true );
}

/**
 * Get all the basic information about a View (id, form_id, settings)
 *
 * If the passed post ID is not a view, it handles the shortcode parsing and merging.
 *
 * @filter default text
 * @action default text
 * @param  int|WP_Post      $post Pass either a View ID or a WP_Post View object
 * @return array            Associative array with `id`, `form_id`, `template_id` and `atts` (template settings) keys
 */
function gravityview_get_view_meta( $post ) {

	// You can pass a post ID if you want to
	if( is_numeric( $post ) ) {
		$post = get_post( $post );
	}

	$output = array();

	// Shortcode or direct View
	if( 'gravityview' === get_post_type( $post ) ) {
		$post_id = $post->ID;
	} else if( $shortcodes = has_gravityview_shortcode( $post ) ) {

		foreach ($shortcodes as $key => $shortcode) {

			// GravityView_frontend may not always be available, since connector-functions.php is loaded before the GravityView_Plugin class is defined.
			$defaults = class_exists('GravityView_View_Data') ? GravityView_View_Data::get_default_args() : array();

			// Get the settings from the shortcode and merge them with defaults.
			$shortcode_atts = wp_parse_args( shortcode_parse_atts( $shortcode[3] ), $defaults );

			if( empty( $shortcode_atts['id'] ) ) {
				do_action('gravityview_log_error', sprintf( '[gravityview_get_view_meta] Returning; no ID defined in shortcode atts for Post #%s (Atts)', $post->ID ), $shortcode_atts );
				return false;
			}

			$post_id = $shortcode_atts['id'];

			// The passed args were always winning, even if they were NULL.
			// This prevents that. Filters NULL, FALSE, and empty strings.
			$item['shortcode_atts'] = array_filter( $shortcode_atts, 'strlen' );
			$item['id'] = $post_id;
			$item['form_id'] = gravityview_get_form_id( $post_id );
			$item['template_id'] = gravityview_get_template_id( $post_id );
			$item['atts'] = gravityview_get_template_settings( $post_id );
			$output[] = $item;
		}

	} else {
		do_action('gravityview_log_error', '[gravityview_get_view_meta] Not GravityView type and no shortcode found.');
		return false;
	}

	$output['id'] = $post_id;
	$output['form_id'] = gravityview_get_form_id( $post_id );
	$output['template_id'] = gravityview_get_template_id( $post_id );
	$output['atts'] = gravityview_get_template_settings( $post_id );

	// The shortcode settings should overrule the default View settings
	if( !empty( $output['shortcode_atts'] ) ) {
		$output['atts'] = wp_parse_args( $output['shortcode_atts'], $output['atts'] );
	}

	return $output;
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

		$output = '<option value="" '. selected( '', $current, false ).'>'. esc_html__( 'Default', 'gravity-view') .'</option>';

		if( empty( $formid ) ) {
			return $output;
		}

		// Get fields with sub-inputs and no parent
		$fields = gravityview_get_form_fields( $formid, true, false );

		if( !empty( $fields ) ) {

			$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array( 'list', 'textarea' ) );

			$output .= '<option value="date_created" '. selected( 'date_created', $current, false ).'>'. esc_html__( 'Date Created', 'gravity-view' ) .'</option>';

			foreach( $fields as $id => $field ) {

				if( in_array( $field['type'], $blacklist_field_types ) ) { continue; }

				$output .= '<option value="'. $id .'" '. selected( $id, $current, false ).'>'. esc_attr( $field['label'] ) .'</option>';
			}

		}
		return $output;
	}

}






