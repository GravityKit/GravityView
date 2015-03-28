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
	 * @param array $field GravityView field array
	 * @param array $entry Gravity Forms entry array
	 * @param boolean $force_show_label Whether to always show the label, regardless of field settings
	 * @return string
	 */
	public static function field_label( $field, $entry = array(), $force_show_label = false ) {
		$gravityview_view = GravityView_View::getInstance();

		$form = $gravityview_view->getForm();

		$label = '';

		if( !empty( $field['show_label'] ) || $force_show_label ) {

			$label = $field['label'];

			// Support Gravity Forms 1.9+
			if( class_exists( 'GF_Field' ) ) {

				$field_object = RGFormsModel::get_field( $form, $field['id'] );

				if( $field_object ) {

					$input = GFFormsModel::get_input( $field_object, $field['id'] );

					// This is a complex field, with lables on a per-input basis
					if( $input ) {

						// Does the input have a custom label on a per-input basis? Otherwise, default label.
						$label = ! empty( $input['customLabel'] ) ? $input['customLabel'] : $input['label'];

					} else {

						// This is a field with one label
						$label = $field_object->get_field_label( true, $field['label'] );

					}

				}

			}

			// Use Gravity Forms label by default, but if a custom label is defined in GV, use it.
			if ( !empty( $field['custom_label'] ) ) {

				$label = self::replace_variables( $field['custom_label'], $form, $entry );

			}

			$label .= apply_filters( 'gravityview_render_after_label', '', $field );

		} // End $field['show_label']

		/**
		 * @since 1.7
		 */
		$label = apply_filters( 'gravityview/template/field_label', $label, $field, $form, $entry );

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
			if( !preg_match( '/\{(all_fields(:(.*?))?|pricing_fields|form_title|entry_url|ip|post_id|admin_email|post_edit_url|form_id|entry_id|embed_url|date_mdy|date_dmy|embed_post:(.*?)|custom_field:(.*?)|user_agent|referer|gv:(.*?)|user:(.*?))\}/ism', $text ) ) {
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
		$gravityview_view = GravityView_View::getInstance();

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
				$form_id = $gravityview_view->getFormId() ? '-'. $gravityview_view->getFormId() : '';
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

		if( empty( $entry['form_id'] ) || empty( $field_settings['id'] ) ) {
			return NULL;
		}

		$gravityview_view = GravityView_View::getInstance();

		if( class_exists( 'GFCache' ) ) {
			/**
			 * Gravity Forms' GFCache function was thrashing the database, causing double the amount of time for the field_value() method to run.
			 *
			 * The reason is that the cache was checking against a field value stored in a transient every time `GFFormsModel::get_lead_field_value()` is called.
			 *
			 * What we're doing here is telling the GFCache that it's already checked the transient and the value is false, forcing it to just use the non-cached data, which is actually faster.
			 *
			 * @hack
			 * @since  1.3
			 * @param  string $cache_key Field Value transient key used by Gravity Forms
			 * @param mixed false Setting the value of the cache to false so that it's not used by Gravity Forms' GFFormsModel::get_lead_field_value() method
			 * @param boolean false Tell Gravity Forms not to store this as a transient
			 * @param  int 0 Time to store the value. 0 is maximum amount of time possible.
			 */
			GFCache::set( "GFFormsModel::get_lead_field_value_" . $entry["id"] . "_" . $field_settings["id"], false, false, 0 );
		}

		$field_id = $field_settings['id'];

		$output = '';

		$form = $gravityview_view->getForm();

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

		// Prevent any PHP warnings that may be generated
		ob_start();

		$display_value = GFCommon::get_lead_field_display($field, $value, $entry["currency"], false, $format);

		if( $errors = ob_get_clean() ) {
			do_action( 'gravityview_log_error', 'GravityView_API[field_value] Errors when calling GFCommon::get_lead_field_display()', $errors );
		}

		$display_value = apply_filters("gform_entry_field_value", $display_value, $field, $entry, $form);

		// prevent the use of merge_tags for non-admin fields
		if( !empty( $field['adminOnly'] ) ) {
			$display_value = self::replace_variables( $display_value, $form, $entry );
		}

		// Check whether the field exists in /includes/fields/{$field_type}.php
		// This can be overridden by user template files.
		$field_exists = $gravityview_view->locate_template("fields/{$field_type}.php");

		// Set the field data to be available in the templates
		$gravityview_view->setCurrentField( array(
			'form' => $form,
			'field_id' => $field_id,
			'field' => $field,
			'field_settings' => $field_settings,
			'value' => $value,
			'display_value' => $display_value,
			'format' => $format,
			'entry' => $entry,
			'field_type' => $field_type, /** {@since 1.6} **/
		));

		if( $field_exists ) {

			do_action( 'gravityview_log_debug', sprintf('[field_value] Rendering %s Field', $field_type ), $field_exists );

			ob_start();

			load_template( $field_exists, false );

			$output = ob_get_clean();

		} else {

			// Backup; the field template doesn't exist.
			$output = $display_value;

		}

		$field_settings = $gravityview_view->getCurrentField('field_settings');

		/**
		 * Link to the single entry by wrapping the output in an anchor tag
		 *
		 * Fields can override this by modifying the field data variable inside the field. See /templates/fields/post_image.php for an example.
		 *
		 */
		if( !empty( $field_settings['show_as_link'] ) ) {

			$output = self::entry_link_html( $entry, $output, array(), $field_settings );

		}

		/**
		 * Modify the field value output for a field type
		 *
		 * @since 1.6
		 * @param string $output HTML value output
		 * @param array  $entry The GF entry array
		 * @param  array $field_settings Settings for the particular GV field
		 */
		$output = apply_filters( 'gravityview_field_entry_value_'.$field_type, $output, $entry, $field_settings, $gravityview_view->getCurrentField() );

		/**
		 * Modify the field value output
		 * @param string $output HTML value output
		 * @param array  $entry The GF entry array
		 * @param  array $field_settings Settings for the particular GV field
		 * @param array $field_data  {@since 1.6}
		 */
		$output = apply_filters( 'gravityview_field_entry_value', $output, $entry, $field_settings, $gravityview_view->getCurrentField() );

		return $output;
	}

	/**
	 * Generate an anchor tag that links to an entry.
	 *
	 * @since 1.6
	 *
	 * @param string $anchor_text The text or HTML inside the link
	 * @param array $entry Gravity Forms entry array
	 * @param array $field_settings Array of field settings. Optional, but passed to the `gravityview_field_entry_link` filter
	 */
	public static function entry_link_html( $entry = array(), $anchor_text = '', $passed_tag_atts = array(), $field_settings = array() ) {

		if ( empty( $entry ) || ! is_array( $entry ) || ! isset( $entry['id'] ) ) {

			do_action( 'gravityview_log_debug', 'GravityView_API[entry_link_tag] Entry not defined; returning null', $entry );

			return NULL;
		}

		$href = self::entry_link( $entry );

		$link = gravityview_get_link( $href, $anchor_text, $passed_tag_atts );

		/**
		 * Modify the link format
		 * @param string $link HTML output of the link
		 * @param string $href URL of the link
		 * @param array  $entry The GF entry array
		 * @param  array $field_settings Settings for the particular GV field
		 */
		$output = apply_filters( 'gravityview_field_entry_link', $link, $href, $entry, $field_settings );

		return $output;
	}

	/**
	 * Get the "No Results" text depending on whether there were results.
	 * @param  boolean     $wpautop Apply wpautop() to the output?
	 * @return string               HTML of "no results" text
	 */
	public static function no_results($wpautop = true) {
		$gravityview_view = GravityView_View::getInstance();

		$is_search = false;

		if( $gravityview_view && ( $gravityview_view->curr_start || $gravityview_view->curr_end || $gravityview_view->curr_search ) ) {
			$is_search = true;
		}

		if($is_search) {
			$output = __('This search returned no results.', 'gravityview');
		} else {
			$output = __('No entries match your request.', 'gravityview');
		}

		/**
		 * Modify the text displayed when there are no entries.
		 *
		 * @param string $output The existing "No Entries" text
		 * @param boolean $is_search Is the current page a search result, or just a multiple entries screen?
		 */
		$output = apply_filters( 'gravitview_no_entries_text', $output, $is_search);

		return $wpautop ? wpautop($output) : $output;
	}

	/**
	 * Generate a link to the Directory view
	 *
	 * Uses `wp_cache_get` and `wp_cache_get` (since 1.3) to speed up repeated requests to get permalink, which improves load time. Since we may be doing this hundreds of times per request, it adds up!
	 *
	 * @param int $post_id Post ID
	 * @param boolean $add_query_args Add pagination and sorting arguments
	 * @return string      Permalink to multiple entries view
	 */
	public static function directory_link( $post_id = NULL, $add_query_args = true ) {
		global $post;

		$gravityview_view = GravityView_View::getInstance();

		if( empty( $post_id ) ) {

			$post_id = false;

			// DataTables passes the Post ID
			if( defined('DOING_AJAX') && DOING_AJAX ) {

				$post_id = isset( $_POST['post_id'] ) ? (int)$_POST['post_id'] : false;

			} else {

				// The Post ID has been passed via the shortcode
				if( !empty( $gravityview_view ) && $gravityview_view->getPostId() ) {

					$post_id = $gravityview_view->getPostId();

				} else {

					// This is a GravityView post type
					if( GravityView_frontend::getInstance()->is_gravityview_post_type ) {

						$post_id = isset( $gravityview_view ) ? $gravityview_view->getViewId() : $post->ID;

					} else {

						// This is an embedded GravityView; use the embedded post's ID as the base.
						if( GravityView_frontend::getInstance()->post_has_shortcode && is_a( $post, 'WP_Post' ) ) {

							$post_id = $post->ID;

						} elseif( $gravityview_view->getViewId() ) {

							// The GravityView has been embedded in a widget or in a template, and
							// is not in the current content. Thus, we defer to the View's own ID.
							$post_id = $gravityview_view->getViewId();

						}

					}

				}
			}
		}

		// No post ID, get outta here.
		if( empty( $post_id ) ) {
			return NULL;
		}

		// If we've saved the permalink in memory, use it
		// @since 1.3
		$link = wp_cache_get( 'gv_directory_link_'.$post_id );

		if( empty( $link ) ) {

			$link = get_permalink( $post_id );

			// If not yet saved, cache the permalink.
			// @since 1.3
			wp_cache_set( 'gv_directory_link_'.$post_id, $link );

		}

		// Deal with returning to proper pagination for embedded views
		if( $add_query_args ) {

			$args = array();

			if( $pagenum = rgget('pagenum') ) {
				$args['pagenum'] = intval( $pagenum );
			}

			if( $sort = rgget('sort') ) {
				$args['sort'] = $sort;
				$args['dir'] = rgget('dir');
			}

			$link = add_query_arg( $args, $link );
		}

		return $link;
	}

	/**
	 * Calculate an *unique* hash for an entry based on the entry ID
	 *
	 * This allows you to be more discrete as to the number of the entry - if you don't want users to know that you have made a certain number of sales, for example, or that their entry in the giveaway is entry #3.
	 *
	 * The hashed value MUST be unique, otherwise multiple entries will share the same URL, which leads to obvious problems.
	 *
	 * @param  int|string $id Entry ID to generate the hash for.
	 * @param  array  $entry        Entry data passed to provide additional information when generating the hash. Optional - don't rely on it being available.
	 * @return string               Hashed unique value for entry
	 */
	private static function get_custom_entry_slug( $id, $entry = array() ) {

		// Generate an unique hash to use as the default value
		$slug = substr( wp_hash( $id, 'gravityview'.$id ), 0, 8 );

		/**
		 * Modify the unique hash ID generated, if you want to improve usability or change the format. This will allow for custom URLs, such as `{entryid}-{first-name}` or even, if unique, `{first-name}-{last-name}`
		 * @param string $hash Existing hash generated by GravityView
		 * @param  string $id The entry ID
		 * @param  array $entry Entry data array. May be empty.
		 * @var string
		 */
		$slug = apply_filters( 'gravityview_entry_slug', $slug, $id, $entry );

		// Make sure we have something - use the original ID as backup.
		if( empty( $slug ) ) {
			$slug = $id;
		}

		return sanitize_title( $slug );
	}

	/**
	 * Get the entry slug for the entry. By default, it is the entry ID.
	 *
	 *
	 * @see gravityview_get_entry()
	 * @uses GravityView_API::get_custom_entry_slug() If using custom slug, gets the custom slug value
	 * @since 1.4
	 * @param  int|string $id_or_string ID of the entry, or custom slug string
	 * @param  array  $entry        Gravity Forms Entry array, optional. Used only to provide data to customize the `gravityview_entry_slug` filter
	 * @return string               Unique slug ID, passed through `sanitize_title()`
	 */
	public static function get_entry_slug( $id_or_string, $entry = array() ) {

		/**
		 * Default: use the entry ID as the unique identifier
		 */
		$slug = $id_or_string;

		/**
		 * Use custom entry slug
		 * @var boolean
		 */
		$custom = apply_filters('gravityview_custom_entry_slug', false );

		// If we're using custom slug...
		if ( $custom ) {

			// Get the entry hash
			$hash = self::get_custom_entry_slug( $id_or_string, $entry );

			// See if the entry already has a hash set
			$value = gform_get_meta( $id_or_string, 'gravityview_unique_id' );

			// If it does have a hash set, and the hash is expected, use it.
			// This check allows users to change the hash structure using the
			// gravityview_entry_hash filter and have the old hashes expire.
			if( empty( $value ) || $value !== $hash ) {

				gform_update_meta( $id_or_string, 'gravityview_unique_id', $hash );

			}

			$slug = $hash;

			unset( $value, $hash );
		}

		return sanitize_title( $slug );
	}

	/**
	 * return href for single entry
	 * @param  array|int $entry   Entry array or entry ID
	 * @param  int|null $post_id If wanting to define the parent post, pass a post ID
	 * @param boolean $add_directory_args True: Add args to help return to directory; False: only include args required to get to entry {@since 1.7.3}
	 * @return string          Link to the entry with the directory parent slug
	 */
	public static function entry_link( $entry, $post_id = NULL, $add_directory_args = true ) {

		if( ! empty( $entry ) && ! is_array( $entry ) ) {
			$entry = GVCommon::get_entry( $entry );
		} else if( empty( $entry ) ) {
			$entry = GravityView_frontend::getInstance()->getEntry();
		}

		// Second parameter used to be passed as $field; this makes sure it's not an array
		if( !is_numeric( $post_id ) ) {
			$post_id = NULL;
		}

		// Get the permalink to the View
		$directory_link = self::directory_link( $post_id, false );

		// No post ID? Get outta here.
		if( empty( $directory_link ) ) {
			return '';
		}

		$query_arg_name = GravityView_Post_Types::get_entry_var_name();

		$entry_slug = self::get_entry_slug( $entry['id'], $entry );

		if( get_option('permalink_structure') && !is_preview() ) {

			$args = array();

			$directory_link = trailingslashit( $directory_link ) . $query_arg_name . '/'. $entry_slug .'/';

		} else {

			$args = array( $query_arg_name => $entry_slug );
		}

		/**
		 * @since 1.7.3
		 */
		if( $add_directory_args ) {

			if( !empty( $_GET['pagenum'] ) ) {
				$args['pagenum'] = intval( $_GET['pagenum'] );
			}

			/**
			 * @since 1.7
			 */
			if( $sort = rgget('sort') ) {
				$args['sort'] = $sort;
				$args['dir'] = rgget('dir');
			}

		}

		/**
		 * Check if we have multiple views embedded in the same page and in that case make sure the single entry link
		 * has the view id so that Advanced Filters can be applied correctly when rendering the single view
		 * @see GravityView_frontend::get_context_view_id()
		 */
		if( class_exists( 'GravityView_View_Data' ) && GravityView_View_Data::getInstance()->has_multiple_views() ) {
			$args['gvid'] = gravityview_get_view_id();
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

function gv_container_class( $class = '' ) {

	$default = ' gv-container';

	if( GravityView_View::getInstance()->isHideUntilSearched() ) {
		$default .= ' hidden';
	}

	$class = apply_filters( 'gravityview/render/container/class', $class . $default );

	$class = gravityview_sanitize_html_class( $class );

	echo $class;
}


/**
 * sanitize_html_class doesn't handle spaces (multiple classes). We remedy that.
 * @uses sanitize_html_class
 * @param  string|array      $classes Text or arrray of classes to sanitize
 * @return string            Sanitized CSS string
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

function gv_directory_link( $post = NULL, $add_pagination = true ) {
	return GravityView_API::directory_link( $post, $add_pagination );
}

function gv_entry_link( $entry, $post_id = NULL ) {
	return GravityView_API::entry_link( $entry, $post_id );
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
	$gravityview_view = GravityView_View::getInstance();

	$label = $gravityview_view->getBackLinkLabel() ? $gravityview_view->getBackLinkLabel() : __( '&larr; Go back', 'gravityview' );

	// filter link label
	$label = apply_filters( 'gravityview_go_back_label', $label );

	$link = gravityview_get_link( $href, esc_html( $label ), array(
		'data-viewid' => $gravityview_view->getViewId()
	));

	return $link;
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
		return isset( $entry[ $field_id ] ) ? $entry[ $field_id ] : '';

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

			$output[] = gravityview_get_link( $term_link, esc_html( $term->name ) );
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

/**
 * Do a _very_ basic match for second-level TLD domains, like `.co.uk`
 *
 * Ideally, we'd use https://github.com/jeremykendall/php-domain-parser to check for this, but it's too much work for such a basic functionality. Maybe if it's needed more in the future.
 *
 * @link http://stackoverflow.com/a/12372310 Basic matching regex
 * @param  string $domain Domain to check if it's a TLD or subdomain
 * @return string         Extracted domain if it has a subdomain
 */
function _gravityview_strip_subdomain( $string_maybe_has_subdomain ) {

    if( preg_match("/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.(?:com\.|co\.|net\.|org\.|firm\.|me\.|school\.|law\.|gov\.|mod\.|msk\.|irkutsks\.|sa\.|act\.|police\.|plc\.|ac\.|tm\.|asso\.|biz\.|pro\.|cg\.|telememo\.)?[a-z\.]{2,6})$/i", $string_maybe_has_subdomain, $matches ) ) {
        return $matches['domain'];
    } else {
        return $string_maybe_has_subdomain;
    }
}

if( !function_exists( 'gravityview_format_link' ) ) {

/**
 * Convert a whole link into a shorter link for display
 * @param  [type] $value [description]
 * @return [type]        [description]
 */
function gravityview_format_link( $value = null ) {


	$parts = parse_url( $value );

	// No domain? Strange...show the original text.
	if( empty( $parts['host'] ) ) {
		return $value;
	}

	// Start with empty value for the return URL
	$return = '';

	// Add in the scheme
	if( false === apply_filters('gravityview_anchor_text_striphttp', true) ) {

		if( isset( $parts['scheme'] ) ) {
			$return .= $parts['scheme'];
		}

	}

	// The domain, which may contain a subdomain
	$domain = $parts['host'];

	/**
	 * Strip www from the domain
	 *
	 * http://www.example.com => example.com
	 *
	 * @param boolean $enable Whether to strip www. Return false to show www.
	 */
	$strip_www = apply_filters('gravityview_anchor_text_stripwww', true );

	if( $strip_www ) {
		$domain = str_replace('www.', '', $domain );
	}

	/**
	 * Strip subdomains from the domain
	 *
	 * Enabled:
	 * http://demo.example.com => example.com
	 *
	 * Disabled:
	 * http://demo.example.com => demo.example.com
	 *
	 * @param boolean $enable Whether to strip subdomains. Return false to show subdomains.
	 */
	$strip_subdomains = apply_filters('gravityview_anchor_text_nosubdomain', true);

	if( $strip_subdomains ) {

		$domain = _gravityview_strip_subdomain( $parts['host'] );

	}

	// Add the domain
	$return .= $domain;

	/**
	 * Display link path going only to the base directory, not a sub-directory or file.
	 *
	 * When enabled:
	 * http://example.com/sub/directory/page.html => example.com
	 *
	 * When disabled:
	 * http://example.com/sub/directory/page.html => example.com/sub/directory/page.html
	 *
	 * @param boolean $enable Whether to enable "root only". Return false to show full path.
	 */
	$root_only = apply_filters('gravityview_anchor_text_rootonly', true);

	if( empty( $root_only ) ) {

		if( isset( $parts['path'] ) ) {
			$return .= $parts['path'];
		}
	}

	/**
	 * Whether to strip the query string from the end of the URL
	 *
	 * http://example.com/?query=example => example.com
	 *
	 * @param boolean $enable Whether to enable "root only". Return false to show full path.
	 */
	$strip_query_string = apply_filters('gravityview_anchor_text_noquerystring', true );

	if( empty( $strip_query_string ) ) {

		if( isset( $parts['query'] ) ) {
			$return .= '?'.$parts['query'];
		}

	}

	return $return;
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
	if( ! $fe->getGvOutputData() ) {

		do_action( 'gravityview_log_debug', '[gravityview_get_current_views] gv_output_data not defined; parsing content.' );

		$fe->parse_content();
	}

	// Make 100% sure that we're dealing with a properly called situation
	if( !is_a( $fe->getGvOutputData(), 'GravityView_View_Data' ) ) {

		do_action( 'gravityview_log_debug', '[gravityview_get_current_views] gv_output_data not an object or get_view not callable.', $fe->getGvOutputData() );

		return array();
	}

	return $fe->getGvOutputData()->get_views();
}

/**
 * Get data for a specific view
 *
 * @see  GravityView_View_Data::get_view()
 * @return array View data with `id`, `view_id`, `form_id`, `template_id`, `atts`, `fields`, `widgets`, `form` keys.
 */
function gravityview_get_current_view_data( $view_id = 0 ) {

	$fe = GravityView_frontend::getInstance();

	if( ! $fe->getGvOutputData() ) { return array(); }

	// If not set, grab the current view ID
	if( empty( $view_id ) ) {
		$view_id = $fe->get_context_view_id();
	}

	return $fe->getGvOutputData()->get_view( $view_id );
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

/**
 * Get the current View ID being rendered
 *
 * @global GravityView_View $gravityview_view
 * @return string View context "directory" or "single"
 */
function gravityview_get_view_id() {
	return GravityView_View::getInstance()->getViewId();
}

/**
 * @global GravityView_View $gravityview_view
 * @return string View context "directory" or "single"
 */
function gravityview_get_context() {
	return GravityView_View::getInstance()->getContext();
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

	$link_text = esc_html__( 'Map It', 'gravityview' );

	$link = gravityview_get_link( $url, $link_text, 'class=map-it-link' );

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
 * @param  array $passed_args Associative array with field data. `field` and `form` are required.
 * @return string
 */
function gravityview_field_output( $passed_args ) {

	$defaults = array(
		'entry' => NULL,
		'field' => NULL,
		'form' => NULL,
		'hide_empty' => true,
		'markup' => '<div class="{{class}}">{{label}}{{value}}</div>',
		'label_markup' => '',
		'wpautop' => false,
		'zone_id' => NULL,
	);

	$args = wp_parse_args( $passed_args, $defaults );

	/**
	 * Modify the args before generation begins
	 *
	 * @since 1.7
	 *
	 * @param array $args Associative array; `field` and `form` is required.
	 * @param array $passed_args Original associative array with field data. `field` and `form` are required.
	 *
	 */
	$args = apply_filters( 'gravityview/field_output/args', $args, $passed_args );

	// Required fields.
	if( empty( $args['field'] ) || empty( $args['form'] ) ) {
		do_action( 'gravityview_log_error', '[gravityview_field_output] Field or form are empty.', $args );
		return '';
	}

	$entry = empty( $args['entry'] ) ? array() : $args['entry'];

	$value = gv_value( $entry, $args['field'] );

	// If the value is empty and we're hiding empty, return empty.
	if( $value === '' && !empty( $args['hide_empty'] ) ) { return ''; }

	if( $value !== '' && !empty( $args['wpautop'] ) ) {
		$value = wpautop( $value );
	}

	$class = gv_class( $args['field'], $args['form'], $entry );

	// get field label if needed
	if( !empty( $args['label_markup'] ) || false !== strpos( $args['markup'], '{{label}}' ) ) {
		$label = gv_label( $args['field'], $entry );
	} else {
		$label = '';
	}

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

/**
 * Similar to the WordPress `selected()`, `checked()`, and `disabled()` functions, except it allows arrays to be passed as current value
 *
 * @see selected() WordPress core function
 *
 * @param string $value One of the values to compare
 * @param mixed $current (true) The other value to compare if not just true
 * @param bool $echo Whether to echo or just return the string
 * @param string $type The type of checked|selected|disabled we are doing
 *
 * @return string html attribute or empty string
 */
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
	}

	return $output;
}

