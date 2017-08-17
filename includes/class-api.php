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

					// This is a complex field, with labels on a per-input basis
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

			/**
			 * @filter `gravityview_render_after_label` Append content to a field label
			 * @param[in,out] string $appended_content Content you can add after a label. Empty by default.
			 * @param[in] array $field GravityView field array
			 */
			$label .= apply_filters( 'gravityview_render_after_label', '', $field );

		} // End $field['show_label']

		/**
		 * @filter `gravityview/template/field_label` Modify field label output
		 * @since 1.7
		 * @param[in,out] string $label Field label HTML
		 * @param[in] array $field GravityView field array
		 * @param[in] array $form Gravity Forms form array
		 * @param[in] array $entry Gravity Forms entry array
		 */
		$label = apply_filters( 'gravityview/template/field_label', $label, $field, $form, $entry );

		return $label;
	}

	/**
	 * Alias for GravityView_Merge_Tags::replace_variables()
	 *
	 * @see GravityView_Merge_Tags::replace_variables() Moved in 1.8.4
	 *
	 * @param  string      $text       Text to replace variables in
	 * @param  array      $form        GF Form array
	 * @param  array      $entry        GF Entry array
	 * @return string                  Text with variables maybe replaced
	 */
	public static function replace_variables( $text, $form = array(), $entry = array() ) {
		return GravityView_Merge_Tags::replace_variables( $text, $form, $entry );
	}

	/**
	 * Get column width from the field setting
	 *
	 * @since 1.9
	 *
	 * @param array $field Array of settings for the field
	 * @param string $format Format for width. "%" (default) will return
	 *
	 * @return string|null If not empty, string in $format format. Otherwise, null.
	 */
	public static function field_width( $field, $format = '%d%%' ) {

		$width = NULL;

		if( !empty( $field['width'] ) ) {
			$width = absint( $field['width'] );

			// If using percentages, limit to 100%
			if( '%d%%' === $format && $width > 100 ) {
				$width = 100;
			}

			$width = sprintf( $format, $width );
		}

		return $width;
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

		if( !empty( $field['custom_class'] ) ) {

            $custom_class = $field['custom_class'];

            if( !empty( $entry ) ) {

                // We want the merge tag to be formatted as a class. The merge tag may be
                // replaced by a multiple-word value that should be output as a single class.
                // "Office Manager" will be formatted as `.OfficeManager`, not `.Office` and `.Manager`
                add_filter('gform_merge_tag_filter', 'sanitize_html_class');

                $custom_class = self::replace_variables( $custom_class, $form, $entry);

                // And then we want life to return to normal
                remove_filter('gform_merge_tag_filter', 'sanitize_html_class');
            }

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
	 * Fetch Field HTML ID
	 *
	 * @since 1.11
	 *
	 * @access public
	 * @static
	 * @param array $field GravityView field array passed to gravityview_field_output()
	 * @param array $form Gravity Forms form array, if set.
	 * @param array $entry Gravity Forms entry array
	 * @return string Sanitized unique HTML `id` attribute for the field
	 */
	public static function field_html_attr_id( $field, $form = array(), $entry = array() ) {
		$gravityview_view = GravityView_View::getInstance();
		$id = $field['id'];

		if ( ! empty( $id ) ) {
			if ( ! empty( $form ) && ! empty( $form['id'] ) ) {
				$form_id = '-' . $form['id'];
			} else {
				$form_id = $gravityview_view->getFormId() ? '-' . $gravityview_view->getFormId() : '';
			}

			$id = 'gv-field' . $form_id . '-' . $field['id'];
		}

		return esc_attr( $id );
	}


	/**
	 * Given an entry and a form field id, calculate the entry value for that field.
	 *
	 * @access public
	 * @param array $entry
	 * @param array $field
	 * @return null|string
	 */
	public static function field_value( $entry, $field_settings, $format = 'html' ) {

		if( empty( $entry['form_id'] ) || empty( $field_settings['id'] ) ) {
			return NULL;
		}

		$gravityview_view = GravityView_View::getInstance();

		$field_id = $field_settings['id'];
		$form = $gravityview_view->getForm();
		$field = gravityview_get_field( $form, $field_id );

		if( $field && is_numeric( $field_id ) ) {
			// Used as file name of field template in GV.
			// Don't use RGFormsModel::get_input_type( $field ); we don't care if it's a radio input; we want to know it's a 'quiz' field
			$field_type = $field->type;
			$value = RGFormsModel::get_lead_field_value( $entry, $field );
		} else {
			$field = GravityView_Fields::get_associated_field( $field_id );
			$field_type = $field_id; // Used as file name of field template in GV
		}

		// If a Gravity Forms Field is found, get the field display
		if( $field ) {

			// Prevent any PHP warnings that may be generated
			ob_start();

			$display_value = GFCommon::get_lead_field_display( $field, $value, $entry["currency"], false, $format );

			if ( $errors = ob_get_clean() ) {
				do_action( 'gravityview_log_error', 'GravityView_API[field_value] Errors when calling GFCommon::get_lead_field_display()', $errors );
			}

			$display_value = apply_filters( "gform_entry_field_value", $display_value, $field, $entry, $form );

			// prevent the use of merge_tags for non-admin fields
			if( !empty( $field->adminOnly ) ) {
				$display_value = self::replace_variables( $display_value, $form, $entry );
			}
		} else {
			$value = $display_value = rgar( $entry, $field_id );
			$display_value = $value;
		}

		// Check whether the field exists in /includes/fields/{$field_type}.php
		// This can be overridden by user template files.
		$field_path = $gravityview_view->locate_template("fields/{$field_type}.php");

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
			'field_type' => $field_type, /** {@since 1.6} */
		    'field_path' => $field_path, /** {@since 1.16} */
		));

		if( ! empty( $field_path ) ) {

			do_action( 'gravityview_log_debug', sprintf('[field_value] Rendering %s', $field_path ) );

			ob_start();

			load_template( $field_path, false );

			$output = ob_get_clean();

		} else {

			// Backup; the field template doesn't exist.
			$output = $display_value;

		}

		// Get the field settings again so that the field template can override the settings
		$field_settings = $gravityview_view->getCurrentField('field_settings');

		/**
		 * @filter `gravityview_field_entry_value_{$field_type}_pre_link` Modify the field value output for a field type before Show As Link setting is applied. Example: `gravityview_field_entry_value_number_pre_link`
		 * @since 1.16
		 * @param string $output HTML value output
		 * @param array  $entry The GF entry array
		 * @param array  $field_settings Settings for the particular GV field
		 * @param array  $field Field array, as fetched from GravityView_View::getCurrentField()
		 */
		$output = apply_filters( 'gravityview_field_entry_value_' . $field_type . '_pre_link', $output, $entry, $field_settings, $gravityview_view->getCurrentField() );

		/**
		 * @filter `gravityview_field_entry_value_pre_link` Modify the field value output for a field before Show As Link setting is applied. Example: `gravityview_field_entry_value_pre_link`
		 * @since 1.21.4
		 * @used-by GV_Inline_Edit
		 * @param string $output HTML value output
		 * @param array  $entry The GF entry array
		 * @param array  $field_settings Settings for the particular GV field
		 * @param array  $field Field array, as fetched from GravityView_View::getCurrentField()
		 */
		$output = apply_filters( 'gravityview_field_entry_value_pre_link', $output, $entry, $field_settings, $gravityview_view->getCurrentField() );

		/**
		 * Link to the single entry by wrapping the output in an anchor tag
		 *
		 * Fields can override this by modifying the field data variable inside the field. See /templates/fields/post_image.php for an example.
		 *
		 */
		if( !empty( $field_settings['show_as_link'] ) && ! gv_empty( $output, false, false ) ) {

			$link_atts = empty( $field_settings['new_window'] ) ? array() : array( 'target' => '_blank' );

			$output = self::entry_link_html( $entry, $output, $link_atts, $field_settings );

		}

		/**
		 * @filter `gravityview_field_entry_value_{$field_type}` Modify the field value output for a field type. Example: `gravityview_field_entry_value_number`
		 * @since 1.6
		 * @param string $output HTML value output
		 * @param array  $entry The GF entry array
		 * @param  array $field_settings Settings for the particular GV field
		 * @param array $field Current field being displayed
		 */
		$output = apply_filters( 'gravityview_field_entry_value_'.$field_type, $output, $entry, $field_settings, $gravityview_view->getCurrentField() );

		/**
		 * @filter `gravityview_field_entry_value` Modify the field value output for all field types
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
	 * @see GVCommon::get_link_html()
	 *
	 * @param string $anchor_text The text or HTML inside the link
	 * @param array $entry Gravity Forms entry array
	 * @param array|string $passed_tag_atts Attributes to be added to the anchor tag, such as `title` or `rel`.
	 * @param array $field_settings Array of field settings. Optional, but passed to the `gravityview_field_entry_link` filter
	 *
	 * @return string|null Returns HTML for an anchor link. Null if $entry isn't defined or is missing an ID.
	 */
	public static function entry_link_html( $entry = array(), $anchor_text = '', $passed_tag_atts = array(), $field_settings = array() ) {

		if ( empty( $entry ) || ! is_array( $entry ) || ! isset( $entry['id'] ) ) {
			do_action( 'gravityview_log_debug', 'GravityView_API[entry_link_tag] Entry not defined; returning null', $entry );
			return NULL;
		}

		$href = self::entry_link( $entry );

		if( '' === $href ) {
			return NULL;
		}

		$link = gravityview_get_link( $href, $anchor_text, $passed_tag_atts );

		/**
		 * @filter `gravityview_field_entry_link` Modify the link HTML
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
		 * @filter `gravitview_no_entries_text` Modify the text displayed when there are no entries.
		 * @param string $output The existing "No Entries" text
		 * @param boolean $is_search Is the current page a search result, or just a multiple entries screen?
		 */
		$output = apply_filters( 'gravitview_no_entries_text', $output, $is_search);

		return $wpautop ? wpautop($output) : $output;
	}

	/**
	 * Generate a URL to the Directory context
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
					if( GravityView_frontend::getInstance()->isGravityviewPostType() ) {

						$post_id = isset( $gravityview_view ) ? $gravityview_view->getViewId() : $post->ID;

					} else {

						// This is an embedded GravityView; use the embedded post's ID as the base.
						if( GravityView_frontend::getInstance()->isPostHasShortcode() && is_a( $post, 'WP_Post' ) ) {

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

		if( (int) $post_id === (int) get_option( 'page_on_front' ) ) {
			$link = home_url();
		}

		if( empty( $link ) ) {

			$link = get_permalink( $post_id );

			// If not yet saved, cache the permalink.
			// @since 1.3
			wp_cache_set( 'gv_directory_link_'.$post_id, $link );

		}

		// Deal with returning to proper pagination for embedded views
		if( $link && $add_query_args ) {

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

		/**
		 * @filter `gravityview_directory_link` Modify the URL to the View "directory" context
		 * @since 1.19.4
		 * @param string $link URL to the View's "directory" context (Multiple Entries screen)
		 * @param int $post_id ID of the post to link to. If the View is embedded, it is the post or page ID
		 */
		$link = apply_filters( 'gravityview_directory_link', $link, $post_id );

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
		 * @filter `gravityview_entry_slug` Modify the unique hash ID generated, if you want to improve usability or change the format. This will allow for custom URLs, such as `{entryid}-{first-name}` or even, if unique, `{first-name}-{last-name}`
		 * @param string $hash Existing hash generated by GravityView
		 * @param  string $id The entry ID
		 * @param  array $entry Entry data array. May be empty.
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
		 * @filter `gravityview_custom_entry_slug` Whether to enable and use custom entry slugs.
		 * @param boolean True: Allow for slugs based on entry values. False: always use entry IDs (default)
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
				do_action( 'gravityview_log_debug', __METHOD__ . ' - Setting hash for entry "'.$id_or_string.'": ' . $hash );
				gform_update_meta( $id_or_string, 'gravityview_unique_id', $hash, rgar( $entry, 'form_id' ) );
			}

			$slug = $hash;

			unset( $value, $hash );
		}

		return sanitize_title( $slug );
	}

    /**
     * If using the entry custom slug feature, make sure the new entries have the custom slug created and saved as meta
     *
     * Triggered by add_action( 'gform_entry_created', array( 'GravityView_API', 'entry_create_custom_slug' ), 10, 2 );
     *
     * @param $entry array Gravity Forms entry object
     * @param $form array Gravity Forms form object
     */
    public static function entry_create_custom_slug( $entry, $form ) {
        /**
         * @filter `gravityview_custom_entry_slug` On entry creation, check if we are using the custom entry slug feature and update the meta
         * @param boolean $custom Should we process the custom entry slug?
         */
        $custom = apply_filters( 'gravityview_custom_entry_slug', false );
        if( $custom ) {
            // create the gravityview_unique_id and save it

            // Get the entry hash
            $hash = self::get_custom_entry_slug( $entry['id'], $entry );

	        do_action( 'gravityview_log_debug', __METHOD__ . ' - Setting hash for entry "'.$entry['id'].'": ' . $hash );

            gform_update_meta( $entry['id'], 'gravityview_unique_id', $hash, rgar( $entry, 'form_id' ) );

        }
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

		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			$query_arg_name = \GV\Entry::get_endpoint_name();
		} else {
			/** Deprecated. Use \GV\Entry::get_endpoint_name instead. */
			$query_arg_name = GravityView_Post_Types::get_entry_var_name();
		}

		$entry_slug = self::get_entry_slug( $entry['id'], $entry );

		if( get_option('permalink_structure') && !is_preview() ) {

			$args = array();

			/**
			 * Make sure the $directory_link doesn't contain any query otherwise it will break when adding the entry slug.
			 * @since 1.16.5
			 */
			$link_parts = explode( '?', $directory_link );

			$query = !empty( $link_parts[1] ) ? '?'.$link_parts[1] : '';

			$directory_link = trailingslashit( $link_parts[0] ) . $query_arg_name . '/'. $entry_slug .'/' . $query;

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
		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
			if ( gravityview()->views->count() > 1 ) {
				$args['gvid'] = gravityview_get_view_id();
			}
		} else {
			/** Deprecated, do not use has_multiple_views(), please. */
			if ( class_exists( 'GravityView_View_Data' ) && GravityView_View_Data::getInstance()->has_multiple_views() ) {
				$args['gvid'] = gravityview_get_view_id();
			}
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
 * Generate a CSS class to be added to the wrapper <div> of a View
 *
 * @since 1.5.4
 * @since 1.16 Added $echo param
 *
 * @param string $passed_css_class Default: `gv-container gv-container-{view id}`. If View is hidden until search, adds ` hidden`
 * @param boolean $echo Whether to echo the output. Default: true
 *
 * @return string CSS class, sanitized by gravityview_sanitize_html_class()
 */
function gv_container_class( $passed_css_class = '', $echo = true ) {

	$passed_css_class = trim( $passed_css_class );

	$view_id = GravityView_View::getInstance()->getViewId();

	$default_css_class = ! empty( $view_id ) ? sprintf( 'gv-container gv-container-%d', $view_id ) : 'gv-container';

	if( GravityView_View::getInstance()->isHideUntilSearched() ) {
		$default_css_class .= ' hidden';
	}

	if( 0 === GravityView_View::getInstance()->getTotalEntries() ) {
		$default_css_class .= ' gv-container-no-results';
	}

	$css_class = trim( $passed_css_class . ' '. $default_css_class );

	/**
	 * @filter `gravityview/render/container/class` Modify the CSS class to be added to the wrapper <div> of a View
	 * @since 1.5.4
	 * @param[in,out] string $css_class Default: `gv-container gv-container-{view id}`. If View is hidden until search, adds ` hidden`. If the View has no results, adds `gv-container-no-results`
	 */
	$css_class = apply_filters( 'gravityview/render/container/class', $css_class );

	$css_class = gravityview_sanitize_html_class( $css_class );

	if( $echo ) {
		echo $css_class;
	}

	return $css_class;
}

function gv_value( $entry, $field ) {

	$value = GravityView_API::field_value( $entry, $field );

	if( $value === '' ) {
		/**
		 * @filter `gravityview_empty_value` What to display when a field is empty
		 * @param string $value (empty string)
		 */
		$value = apply_filters( 'gravityview_empty_value', '' );
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
 * @since 1.0.1
 * @return string|null      If no GV post exists, null. Otherwise, HTML string of back link.
 */
function gravityview_back_link() {

	$href = gv_directory_link();

	/**
	 * @filter `gravityview_go_back_url` Modify the back link URL
	 * @since 1.17.5
	 * @see gv_directory_link() Generated the original back link
	 * @param string $href Existing label URL
	 */
	$href = apply_filters( 'gravityview_go_back_url', $href );

	if( empty( $href ) ) { return NULL; }

	// calculate link label
	$gravityview_view = GravityView_View::getInstance();

	$label = $gravityview_view->getBackLinkLabel() ? $gravityview_view->getBackLinkLabel() : __( '&larr; Go back', 'gravityview' );

	/**
	 * @filter `gravityview_go_back_label` Modify the back link text
	 * @since 1.0.9
	 * @param string $label Existing label text
	 */
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
 * @since 1.0.4
 * @param  array      $entry    GF entry array
 * @param  string      $field_id [description]
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

	if ( is_array( $value ) ) {
		$terms = array_filter( array_values( $value ), 'strlen' );
	} else {
		$terms = explode( ', ', $value );
	}

	foreach ($terms as $term_name ) {

		// If we're processing a category,
		if( $taxonomy === 'category' ) {

			// Use rgexplode to prevent errors if : doesn't exist
			list( $term_name, $term_id ) = rgexplode( ':', $term_name, 2 );

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

	if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
		if ( ! gravityview()->views->count() ) {
			return array();
		}
		return array_combine(
			array_map( function ( $view ) { return $view->ID; }, gravityview()->views->all() ),
			array_map( function ( $view ) { return $view->as_data(); }, gravityview()->views->all() )
		);
	}
	/** \GravityView_View_Data::get_views is deprecated. */
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

	// If not set, grab the current view ID
	if ( empty( $view_id ) ) {
		$view_id = $fe->get_context_view_id();
	}

	if ( defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ) {
		$view = gravityview()->views->get( $view_id );
		if ( ! $view ) {
			/** Emulate the weird behavior of \GravityView_View_Data::get_view adding a view which wasn't there to begin with. */
			gravityview()->views->add( \GV\View::by_id( $view_id ) );
			$view = gravityview()->views->get( $view_id );
		}
		return $view ? $view->as_data() : array();
	} else {
		if ( ! $fe->getGvOutputData() ) { return array(); }

		return $fe->getGvOutputData()->get_view( $view_id );
	}
}

// Templates' hooks
function gravityview_before() {
	/**
	 * @action `gravityview_before` Display content before a View. Used to render widget areas. Rendered outside the View container `<div>`
	 * @param int $view_id The ID of the View being displayed
	 */
	do_action( 'gravityview_before', gravityview_get_view_id() );
}

function gravityview_header() {
	/**
	 * @action `gravityview_header` Prepend content to the View container `<div>`
	 * @param int $view_id The ID of the View being displayed
	 */
	do_action( 'gravityview_header', gravityview_get_view_id() );
}

function gravityview_footer() {
	/**
	 * @action `gravityview_after` Display content after a View. Used to render footer widget areas. Rendered outside the View container `<div>`
	 * @param int $view_id The ID of the View being displayed
	 */
	do_action( 'gravityview_footer', gravityview_get_view_id() );
}

function gravityview_after() {
	/**
	 * @action `gravityview_after` Append content to the View container `<div>`
	 * @param int $view_id The ID of the View being displayed
	 */
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
 * @return string View context "directory", "single", or "edit"
 */
function gravityview_get_context() {

	$context = '';

	/**
	 * @filter `gravityview_is_edit_entry` Whether we're currently on the Edit Entry screen \n
	 * The Edit Entry functionality overrides this value.
	 * @param boolean $is_edit_entry
	 */
	$is_edit_entry = apply_filters( 'gravityview_is_edit_entry', false );

	if( $is_edit_entry ) {
		$context = 'edit';
	} else if( class_exists( 'GravityView_frontend' ) && $single = GravityView_frontend::is_single_entry() ) {
		$context = 'single';
	} else if( class_exists( 'GravityView_View' ) ) {
		$context = GravityView_View::getInstance()->getContext();
	}

	return $context;
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
	/** @define "GRAVITYVIEW_DIR" "../" */

	if( !class_exists( 'GravityView_Field' ) ) {
		include_once( GRAVITYVIEW_DIR .'includes/fields/class-gravityview-field.php' );
	}

	if( !class_exists( 'GravityView_Field_FileUpload' ) ) {
		include_once( GRAVITYVIEW_DIR .'includes/fields/fileupload.php' );
	}

	return GravityView_Field_FileUpload::get_files_array( $value, $gv_class );
}

/**
 * Generate a mapping link from an address
 *
 * The address should be plain text with new line (`\n`) or `<br />` line breaks separating sections
 *
 * @todo use GF's field get_export_value() instead
 *
 * @see https://gravityview.co/support/documentation/201608159 Read how to modify the link
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
	 * @filter `gravityview_map_link` Modify the map link generated. You can use a different mapping service, for example.
	 * @param[in,out]  string $link Map link
	 * @param[in] string $address Address to generate link for
	 * @param[in] string $url URL generated by the function
	 */
	$link = apply_filters( 'gravityview_map_link', $link, $address, $url );

	return $link;
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
 * @return string Field output. If empty value and hide empty is true, return empty.
 */
function gravityview_field_output( $passed_args ) {
	$defaults = array(
		'entry' => null,
		'field' => null,
		'form' => null,
		'hide_empty' => true,
		'markup' => '<div id="{{ field_id }}" class="{{ class }}">{{label}}{{value}}</div>',
		'label_markup' => '',
		'wpautop' => false,
		'zone_id' => null,
	);

	$args = wp_parse_args( $passed_args, $defaults );

	/**
	 * @filter `gravityview/field_output/args` Modify the args before generation begins
	 * @since 1.7
	 * @param array $args Associative array; `field` and `form` is required.
	 * @param array $passed_args Original associative array with field data. `field` and `form` are required.
	 */
	$args = apply_filters( 'gravityview/field_output/args', $args, $passed_args );

	// Required fields.
	if ( empty( $args['field'] ) || empty( $args['form'] ) ) {
		do_action( 'gravityview_log_error', '[gravityview_field_output] Field or form are empty.', $args );
		return '';
	}

	$entry = empty( $args['entry'] ) ? array() : $args['entry'];

	/**
	 * Create the content variables for replacing.
	 * @since 1.11
	 */
	$context = array(
		'value' => '',
		'width' => '',
		'width:style' => '',
		'label' => '',
		'label_value' => '',
		'class' => '',
		'field_id' => '',
	);

	$context['value'] = gv_value( $entry, $args['field'] );

	// If the value is empty and we're hiding empty, return empty.
	if ( $context['value'] === '' && ! empty( $args['hide_empty'] ) ) {
		return '';
	}

	if ( $context['value'] !== '' && ! empty( $args['wpautop'] ) ) {
		$context['value'] = wpautop( $context['value'] );
	}

	// Get width setting, if exists
	$context['width'] = GravityView_API::field_width( $args['field'] );

	// If replacing with CSS inline formatting, let's do it.
	$context['width:style'] = GravityView_API::field_width( $args['field'], 'width:' . $context['width'] . '%;' );

	// Grab the Class using `gv_class`
	$context['class'] = gv_class( $args['field'], $args['form'], $entry );
	$context['field_id'] = GravityView_API::field_html_attr_id( $args['field'], $args['form'], $entry );

	// Get field label if needed
	if ( ! empty( $args['label_markup'] ) && ! empty( $args['field']['show_label'] ) ) {
		$context['label'] = str_replace( array( '{{label}}', '{{ label }}' ), '<span class="gv-field-label">{{ label_value }}</span>', $args['label_markup'] );
	}

	// Default Label value
	$context['label_value'] = gv_label( $args['field'], $entry );

	if ( empty( $context['label'] ) && ! empty( $context['label_value'] ) ){
		$context['label'] = '<span class="gv-field-label">{{ label_value }}</span>';
	}

	/**
	 * @filter `gravityview/field_output/pre_html` Allow Pre filtering of the HTML
	 * @since 1.11
	 * @param string $markup The HTML for the markup
	 * @param array $args All args for the field output
	 */
	$html = apply_filters( 'gravityview/field_output/pre_html', $args['markup'], $args );

	/**
	 * @filter `gravityview/field_output/open_tag` Modify the opening tags for the template content placeholders
	 * @since 1.11
	 * @param string $open_tag Open tag for template content placeholders. Default: `{{`
	 */
	$open_tag = apply_filters( 'gravityview/field_output/open_tag', '{{', $args );

	/**
	 * @filter `gravityview/field_output/close_tag` Modify the closing tags for the template content placeholders
	 * @since 1.11
	 * @param string $close_tag Close tag for template content placeholders. Default: `}}`
	 */
	$close_tag = apply_filters( 'gravityview/field_output/close_tag', '}}', $args );

	/**
	 * Loop through each of the tags to replace and replace both `{{tag}}` and `{{ tag }}` with the values
	 * @since 1.11
	 */
	foreach ( $context as $tag => $value ) {

		// If the tag doesn't exist just skip it
		if ( false === strpos( $html, $open_tag . $tag . $close_tag ) && false === strpos( $html, $open_tag . ' ' . $tag . ' ' . $close_tag ) ){
			continue;
		}

		// Array to search
		$search = array(
			$open_tag . $tag . $close_tag,
			$open_tag . ' ' . $tag . ' ' . $close_tag,
		);

		/**
		 * `gravityview/field_output/context/{$tag}` Allow users to filter content on context
		 * @since 1.11
		 * @param string $value The content to be shown instead of the {{tag}} placeholder
		 * @param array $args Arguments passed to the function
		 */
		$value = apply_filters( 'gravityview/field_output/context/' . $tag, $value, $args );

		// Finally do the replace
		$html = str_replace( $search, $value, $html );
	}

	/**
	 * @todo  Depricate `gravityview_field_output`
	 */
	$html = apply_filters( 'gravityview_field_output', $html, $args );

	/**
	 * @filter `gravityview/field_output/html` Modify field HTML output
	 * @param string $html Existing HTML output
	 * @param array $args Arguments passed to the function
	 */
	$html = apply_filters( 'gravityview/field_output/html', $html, $args );

	// Just free up a tiny amount of memory
	unset( $value, $args, $passed_args, $entry, $context, $search, $open_tag, $tag, $close_tag );

	return $html;
}
