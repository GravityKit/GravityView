<?php
/**
 * GravityView template tags API
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */

class GravityView_API {

	/**
	 * Fetch Field Label
	 *
	 * @deprecated Use \GV\Field::get_label()
	 *
	 * @static
	 * @param array   $field GravityView field array
	 * @param array   $entry Gravity Forms entry array
	 * @param boolean $force_show_label Whether to always show the label, regardless of field settings
	 * @return string
	 */
	public static function field_label( $field, $entry = array(), $force_show_label = false ) {

		$gravityview_view = GravityView_View::getInstance();

		$form = $gravityview_view->getForm();

		if ( defined( 'DOING_GRAVITYVIEW_TESTS' ) && ! empty( $GLOBALS['GravityView_API_field_label_override'] ) ) {
			/** Allow to fall through for back compatibility testing purposes. */
		} else {
			return \GV\Mocks\GravityView_API_field_label( $form, $field, $entry, $force_show_label );
		}

		$label = '';

		if ( ! empty( $field['show_label'] ) || $force_show_label ) {

			$label = $field['label'];

			// Support Gravity Forms 1.9+
			if ( class_exists( 'GF_Field' ) ) {

				$field_object = RGFormsModel::get_field( $form, $field['id'] );

				if ( $field_object ) {

					$input = GFFormsModel::get_input( $field_object, $field['id'] );

					// This is a complex field, with labels on a per-input basis
					if ( $input ) {

						// Does the input have a custom label on a per-input basis? Otherwise, default label.
						$label = ! empty( $input['customLabel'] ) ? $input['customLabel'] : $input['label'];

					} else {

						// This is a field with one label
						$label = $field_object->get_field_label( true, $field['label'] );

					}
				}
			}

			// Use Gravity Forms label by default, but if a custom label is defined in GV, use it.
			if ( ! empty( $field['custom_label'] ) ) {

				$label = self::replace_variables( $field['custom_label'], $form, $entry );

			}

			/**
			 * Append content to a field label.
			 *
			 * @param string $appended_content Content you can add after a label. Empty by default.
			 * @param array $field GravityView field array
			 */
			$label .= apply_filters( 'gravityview_render_after_label', '', $field );

		} // End $field['show_label']

		/**
		 * Modify field label output.
		 *
		 * @since 1.7
		 * @param string $label Field label HTML
		 * @param array $field GravityView field array
		 * @param array $form Gravity Forms form array
		 * @param array $entry Gravity Forms entry array
		 *
		 * @deprecated Use the context-aware version `gravityview/template/field/label`
		 */
		$label = apply_filters( 'gravityview/template/field_label', $label, $field, $form, $entry );

		return $label;
	}

	/**
	 * Alias for GravityView_Merge_Tags::replace_variables()
	 *
	 * @see GravityView_Merge_Tags::replace_variables() Moved in 1.8.4
	 * @since 1.22.4 - Added $nl2br, $format, $aux_data args
	 *
	 * @param  string $text         Text to replace variables in
	 * @param  array  $form         GF Form array
	 * @param  array  $entry        GF Entry array
	 * @param  bool   $url_encode   Pass return value through `url_encode()`
	 * @param  bool   $esc_html     Pass return value through `esc_html()`
	 * @param  bool   $nl2br        Convert newlines to <br> HTML tags
	 * @param  string $format       The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param  array  $aux_data     Additional data to be used to replace merge tags {@see https://www.gravityhelp.com/documentation/article/gform_merge_tag_data/}
	 * @return string                   Text with variables maybe replaced
	 */
	public static function replace_variables( $text, $form = array(), $entry = array(), $url_encode = false, $esc_html = true, $nl2br = true, $format = 'html', $aux_data = array() ) {
		return GravityView_Merge_Tags::replace_variables( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format, $aux_data );
	}

	/**
	 * Get column width from the field setting
	 *
	 * @since 1.9
	 *
	 * @param array  $field Array of settings for the field
	 * @param string $format Format for width. "%" (default) will return
	 *
	 * @return string|null If not empty, string in $format format. Otherwise, null.
	 */
	public static function field_width( $field, $format = '%d%%' ) {

		$width = null;

		if ( ! empty( $field['width'] ) ) {
			$width = absint( $field['width'] );

			// If using percentages, limit to 100%
			if ( '%d%%' === $format && $width > 100 ) {
				$width = 100;
			}

			$width = sprintf( $format, $width );
		}

		return $width;
	}

	/**
	 * Fetch Field class
	 *
	 * @static
	 * @param mixed $field
	 * @return string
	 */
	public static function field_class( $field, $form = null, $entry = null ) {
		$classes = array();

		if ( ! empty( $field['custom_class'] ) ) {

            $custom_class = $field['custom_class'];

            if ( ! empty( $entry ) ) {

                // We want the merge tag to be formatted as a class. The merge tag may be
                // replaced by a multiple-word value that should be output as a single class.
                // "Office Manager" will be formatted as `.OfficeManager`, not `.Office` and `.Manager`
                add_filter( 'gform_merge_tag_filter', 'sanitize_html_class' );

                $custom_class = self::replace_variables( $custom_class, $form, $entry );

                // And then we want life to return to normal
                remove_filter( 'gform_merge_tag_filter', 'sanitize_html_class' );
            }

			// And now we want the spaces to be handled nicely.
			$classes[] = gravityview_sanitize_html_class( $custom_class );

		}

		if ( ! empty( $field['id'] ) ) {
			if ( ! empty( $form ) && ! empty( $form['id'] ) ) {
				$form_id = $form['id'];
			} else {
				// @deprecated path. Form should always be given.
				gravityview()->log->warning( 'GravityView_View::getInstance() legacy API called' );
				$gravityview_view = GravityView_View::getInstance();
				$form_id          = $gravityview_view->getFormId() ? $gravityview_view->getFormId() : '';
			}

			$classes[] = 'gv-field' . ( $form_id ? '-' . $form_id : '' ) . '-' . $field['id'];

			// Field is from different form, so we add an extra class.
			if ( (int) ( $field['form_id'] ?? $form_id ) !== (int) $form_id ) {
				$classes[] = 'gv-field-' . $field['form_id'] . '-' . $field['id'];
			}
		}

		return esc_attr( implode( ' ', $classes ) );
	}

	/**
	 * Fetch Field HTML ID
	 *
	 * @since 1.11
	 *
	 * @static
	 * @param array $field GravityView field array passed to gravityview_field_output()
	 * @param array $form Gravity Forms form array, if set.
	 * @param array $entry Gravity Forms entry array
	 * @return string Sanitized unique HTML `id` attribute for the field
	 */
	public static function field_html_attr_id( $field, $form = array(), $entry = array() ) {
		$id = $field['id'];

		if ( ! empty( $id ) ) {
			if ( ! empty( $form ) && ! empty( $form['id'] ) ) {
				$form_id = $field['form_id'] ?? $form['id'];
				$form_id = '-' . $form_id;
			} else {
				// @deprecated path. Form should always be given.
				gravityview()->log->warning( 'GravityView_View::getInstance() legacy API called' );
				$gravityview_view = GravityView_View::getInstance();
				$form_id          = $gravityview_view->getFormId() ? '-' . $gravityview_view->getFormId() : '';
			}

			$id = 'gv-field' . $form_id . '-' . $field['id'];
		}

		return esc_attr( $id );
	}


	/**
	 * Given an entry and a form field id, calculate the entry value for that field.
	 *
	 * @deprecated Use \GV\Field_Template::render() or the more low-level \GV\Field::get_value()
	 *
	 * @param array $entry
	 * @param array $field
	 * @return null|string
	 */
	public static function field_value( $entry, $field_settings, $format = 'html' ) {
		gravityview()->log->notice( '\GravityView_API::field_value is deprecated. Use \GV\Field_Template::render() or \GV\Field::get_value()' );
		return \GV\Mocks\GravityView_API_field_value( $entry, $field_settings, $format );
	}

	/**
	 * Generate an anchor tag that links to an entry.
	 *
	 * @since 1.6
	 * @see GVCommon::get_link_html()
	 *
	 * @param string       $anchor_text The text or HTML inside the link
	 * @param array        $entry Gravity Forms entry array
	 * @param array|string $passed_tag_atts Attributes to be added to the anchor tag, such as `title` or `rel`.
	 * @param array        $field_settings Array of field settings. Optional, but passed to the `gravityview_field_entry_link` filter
	 *
	 * @since 2.0
	 * @param int          $base_id The post or the view that this entry is linked from.
	 *
	 * @return string|null Returns HTML for an anchor link. Null if $entry isn't defined or is missing an ID.
	 */
	public static function entry_link_html( $entry = array(), $anchor_text = '', $passed_tag_atts = array(), $field_settings = array(), $base_id = null ) {

		if ( empty( $entry ) || ! is_array( $entry ) || ! isset( $entry['id'] ) ) {
			gravityview()->log->debug( 'Entry not defined; returning null', array( 'data' => $entry ) );
			return null;
		}

		$href = self::entry_link( $entry, $base_id );

		if ( '' === $href ) {
			return null;
		}

		$link = gravityview_get_link( $href, $anchor_text, $passed_tag_atts );

		/**
		 * Modify the link HTML.
		 *
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
	 *
	 * @since 2.0
	 *
	 * @param  boolean                   $wpautop Apply wpautop() to the output?
	 * @param \GV\Template_Context|null $context The context
	 *
	 * @return string               HTML of "no results" text
	 */
	public static function no_results( $wpautop = true, $context = null ) {
		$is_search = false;

		if ( $context instanceof \GV\Template_Context ) {
			if ( $context->request->is_search() ) {
				$is_search = true;
			}
		} else {
			$gravityview_view = GravityView_View::getInstance();

			if ( $gravityview_view && ( $gravityview_view->curr_start || $gravityview_view->curr_end || $gravityview_view->curr_search ) ) {
				$is_search = true;
			}
		}

		$setting = '';

		if ( $is_search ) {

			$output = esc_html__( 'This search returned no results.', 'gk-gravityview' );

			if ( $context ) {
				$setting = $context->view->settings->get( 'no_search_results_text', $output );
			}
		} else {

			$output = esc_html__( 'No entries match your request.', 'gk-gravityview' );

			if ( $context ) {
				$setting = $context->view->settings->get( 'no_results_text', $output );
			}
		}

		if ( '' !== $setting ) {
			$output = $setting;
		}

		/**
		 * Added now that users are able to modify via View settings
		 *
		 * @since 2.8.2
		 */
		$output = wp_kses(
			$output,
			array(
				'p'      => array(
					'class' => array(),
					'id'    => array(),
				),
				'h1'     => array(
					'class' => array(),
					'id'    => array(),
				),
				'h2'     => array(
					'class' => array(),
					'id'    => array(),
				),
				'h3'     => array(
					'class' => array(),
					'id'    => array(),
				),
				'h4'     => array(
					'class' => array(),
					'id'    => array(),
				),
				'h5'     => array(
					'class' => array(),
					'id'    => array(),
				),
				'strong' => array(
					'class' => array(),
					'id'    => array(),
				),
				'span'   => array(
					'class' => array(),
					'id'    => array(),
				),
				'b'      => array(
					'class' => array(),
					'id'    => array(),
				),
				'em'     => array(
					'class' => array(),
					'id'    => array(),
				),
				'a'      => array(
					'class'  => array(),
					'id'     => array(),
					'href'   => array(),
					'title'  => array(),
					'rel'    => array(),
					'target' => array(),
				),
				'div'    => array(
					'class' => array(),
					'id'    => array(),
				),
				'br'     => array(),
			)
		);

		$unformatted_output = $output;

		$output = $wpautop ? wpautop( $output ) : $output;

		/**
		 * Modify the text displayed when there are no entries.
		 * Note: this filter is, and always has been, misspelled. This will not be fixed, since the filter is deprecated.
		 *
		 * @param string $output The existing "No Entries" text
		 * @param boolean $is_search Is the current page a search result, or just a multiple entries screen?
		 * @return string The modified text.
		 * @deprecated Use `gravityview/template/text/no_entries`
		 */
		$output = apply_filters( 'gravitview_no_entries_text', $output, $is_search );

		/**
		 * Modify the text displayed when there are no entries.
		 *
		 * @since 2.0
		 * @since 2.17 Added $wpautop parameter.
		 * @param string $output The existing "No Entries" text.
		 * @param boolean $is_search Is the current page a search result, or just a multiple entries screen?
		 * @param \GV\Template_Context $context The context.
		 * @param string $unformatted_output Output without `wpautop()`.
		 * @return string The modified text.
		 */
		$output = apply_filters( 'gravityview/template/text/no_entries', $output, $is_search, $context, $unformatted_output );

		return $output;
	}

	/**
	 * Generate a URL to the Directory context
	 *
	 * Uses local static variable to speed up repeated requests to get permalink, which improves load time. Since we may be doing this hundreds of times per request, it adds up!
	 *
	 * @used-by GravityView_API::entry_link()
	 * @used-by GravityView_Widget_Page_Links::render_frontend()
	 *
	 * @param int                  $post_id Post ID
	 * @param boolean              $add_query_args Add pagination and sorting arguments
	 *
	 * @since 2.0
	 * @param \GV\Template_Context $context The context this is being used in.
	 *
	 * @return string      Permalink to multiple entries view
	 */
	public static function directory_link( $post_id = null, $add_query_args = true, $context = null ) {
		global $post;

		if ( empty( $post_id ) ) {
			// DataTables passes the Post ID
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				$post_id = \GV\Utils::_POST( 'post_id', false );
			} elseif ( $context instanceof \GV\Template_Context ) {
					// Shortcodes, embeds
				if ( is_a( $post, 'WP_Post' ) ) {
					$post_id = $post->ID;

					// Actual views
				} else {
					$post_id = $context->view ? $context->view->ID : false;
				}
			} else {

				if ( ! class_exists( 'GravityView_View' ) ) {
					gravityview()->plugin->include_legacy_frontend( true );
				}

				/** @deprecated path of execution */
				$gravityview_view = GravityView_View::getInstance();

				// The Post ID has been passed via the shortcode
				if ( ! empty( $gravityview_view ) && $gravityview_view->getPostId() ) {
					$post_id = $gravityview_view->getPostId();
				} else {
					// This is a GravityView post type
					if ( GravityView_frontend::getInstance()->isGravityviewPostType() ) {
						$post_id = isset( $gravityview_view ) ? $gravityview_view->getViewId() : $post->ID;
					} else {
						// This is an embedded GravityView; use the embedded post's ID as the base.
						if ( GravityView_frontend::getInstance()->isPostHasShortcode() && is_a( $post, 'WP_Post' ) ) {
							$post_id = $post->ID;
						} elseif ( $gravityview_view->getViewId() ) {
							// The GravityView has been embedded in a widget or in a template, and
							// is not in the current content. Thus, we defer to the View's own ID.
							$post_id = $gravityview_view->getViewId();
						}
					}
				}
			}
		}

		// No post ID, get outta here.
		if ( empty( $post_id ) ) {
			return null;
		}

		static $directory_links = array();

		/**
		 * If we've saved the permalink, use it. Reduces time spent on `get_permalink()`, which is heavy.
		 *
		 * @since 1.3
		 * @since 2.17 Changed from using wp_cache_set() to using a static variable.
		 */
		if ( isset( $directory_links[ 'gv_directory_link_' . $post_id ] ) ) {
			$link = $directory_links[ 'gv_directory_link_' . $post_id ];
		}

		if ( (int) $post_id === (int) get_option( 'page_on_front' ) ) {
			$link = home_url();
		}

		if ( empty( $link ) ) {
			$link = get_permalink( $post_id );

			$directory_links[ 'gv_directory_link_' . $post_id ] = $link;
		}

		// Deal with returning to proper pagination for embedded views
		if ( $link && $add_query_args ) {

			$args = array();

			if ( $pagenum = \GV\Utils::_GET( 'pagenum' ) ) {
				$args['pagenum'] = intval( $pagenum );
			}

			if ( $sort = \GV\Utils::_GET( 'sort' ) ) {
				$args['sort'] = $sort;
				$args['dir']  = \GV\Utils::_GET( 'dir' );
			}

			$link = add_query_arg( $args, $link );
		}

		/**
		 * Modify the URL to the View "directory" context.
		 *
		 * @since 1.19.4
		 * @param string $link URL to the View's "directory" context (Multiple Entries screen)
		 * @param int $post_id ID of the post to link to. If the View is embedded, it is the post or page ID
		 */
		$link = apply_filters( 'gravityview_directory_link', $link, $post_id );

		/**
		 * Modify the URL to the View "directory" context.
		 *
		 * @since 2.0
		 * @param string $link URL to the View's "directory" context (Multiple Entries screen)
		 * @param \GV\Template_Context $context
		 */
		return apply_filters( 'gravityview/view/links/directory', $link, $context );
	}

	/**
	 * Calculate an *unique* hash for an entry based on the entry ID
	 *
	 * This allows you to be more discrete as to the number of the entry - if you don't want users to know that you have made a certain number of sales, for example, or that their entry in the giveaway is entry #3.
	 *
	 * The hashed value MUST be unique, otherwise multiple entries will share the same URL, which leads to obvious problems.
	 *
	 * @param  int|string $id Entry ID to generate the hash for.
	 * @param  array      $entry        Entry data passed to provide additional information when generating the hash. Optional - don't rely on it being available.
	 * @return string               Hashed unique value for entry
	 */
	private static function get_custom_entry_slug( $id, $entry = array() ) {

		// Generate an unique hash to use as the default value
		$slug = substr( wp_hash( $id, 'gravityview' . $id ), 0, 8 );

		/**
		 * Modify the unique entry slug, which is used in the entry URL.
		 *
		 * You can customize the slug based on entry data, for example using `{first-name}-{last-name}` (if unique).
		 *
		 * @since 1.4
		 *
		 * @param string $slug Existing slug generated by GravityView (8-character hash by default).
		 * @param string $id The entry ID.
		 * @param array $entry Entry data array. May be empty.
		 */
		$slug = apply_filters( 'gravityview_entry_slug', $slug, $id, $entry );

		// Make sure we have something - use the original ID as backup.
		if ( empty( $slug ) ) {
			$slug = $id;
		}

		return sanitize_title( $slug );
	}

	/**
	 * Get the entry slug for the entry. By default, it is the entry ID.
     *
	 * @see gravityview_get_entry()
	 * @uses GravityView_API::get_custom_entry_slug() If using custom slug, gets the custom slug value
	 * @since 1.4
	 * @param  int|string $id_or_string ID of the entry, or custom slug string
	 * @param  array      $entry        Gravity Forms Entry array, optional. Used only to provide data to customize the `gravityview_entry_slug` filter
	 * @return string               Unique slug ID, passed through `sanitize_title()`
	 */
	public static function get_entry_slug( $id_or_string, $entry = array() ) {

		/**
		 * Default: use the entry ID as the unique identifier
		 */
		$slug = $id_or_string;

		/**
		 * Whether to enable and use custom entry slugs.
		 *
		 * @param boolean True: Allow for slugs based on entry values. False: always use entry IDs (default)
		 */
		$custom = apply_filters( 'gravityview_custom_entry_slug', false );

		// If we're using custom slug...
		if ( $custom ) {

			// Get the entry hash
			$hash = self::get_custom_entry_slug( $id_or_string, $entry );

			// Cache the slugs
			static $cache = array();

			if ( ! isset( $cache[ $id_or_string ] ) ) {
				global $wpdb;

				if ( version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '>=' ) ) {
					$table  = GFFormsModel::get_entry_meta_table_name();
					$column = 'entry_id';
				} else {
					$table  = RGFormsModel::get_lead_meta_table_name();
					$column = 'lead_id';
				}

				$results = $wpdb->get_results( $wpdb->prepare( "SELECT $column, meta_value FROM $table WHERE form_id = (SELECT form_id FROM $table WHERE $column = %d LIMIT 1) AND meta_key = 'gravityview_unique_id'", $id_or_string ) );

				if ( $results ) {
					$cache = array_replace( $cache, array_combine( wp_list_pluck( $results, $column ), wp_list_pluck( $results, 'meta_value' ) ) );
				}

				if ( ! isset( $cache[ $id_or_string ] ) ) {
					$cache[ $id_or_string ] = false;
				}
			}

			$value = $cache[ $id_or_string ];

			// If it does have a hash set, and the hash is expected, use it.
			// This check allows users to change the hash structure using the
			// gravityview_entry_hash filter and have the old hashes expire.
			if ( empty( $value ) || $value !== $hash ) {
				gravityview()->log->debug(
                    'Setting hash for entry {entry}: {hash}',
                    array(
						'entry' => $id_or_string,
						'hash'  => $hash,
                    )
                );
				gform_update_meta( $id_or_string, 'gravityview_unique_id', $hash, \GV\Utils::get( $entry, 'form_id' ) );
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
	     * On entry creation, check if we are using the custom entry slug feature and update the meta.
	     *
	     * @param boolean $custom Should we process the custom entry slug?
	     */
	    $custom = apply_filters( 'gravityview_custom_entry_slug', false );
        if ( $custom ) {
            // create the gravityview_unique_id and save it

            // Get the entry hash
            $hash = self::get_custom_entry_slug( $entry['id'], $entry );

	        gravityview()->log->debug(
                'Setting hash for entry {entry_id}: {hash}',
                array(
					'entry_id' => $entry['id'],
					'hash'     => $hash,
                )
            );

            gform_update_meta( $entry['id'], 'gravityview_unique_id', $hash, \GV\Utils::get( $entry, 'form_id' ) );

        }
    }




	/**
	 * return href for single entry
	 *
	 * @since 1.7.3 Added $add_directory_args parameter
	 * @since 2.7.2 Added $view_id parameter
	 * @since 2.10  Added $_GET args to links by default. Use `gravityview/entry_link/add_query_args` filter to override.
	 *
	 * @used-by GravityView_Entry_List::get_output()
	 * @used-by GravityView_Delete_Entry::get_delete_link()
	 * @used-by GravityView_Edit_Entry::get_edit_link()
	 * @used-by GravityView_Entry_Link_Shortcode::shortcode()
	 *
	 * @param  array|int $entry   Entry array or entry ID.
	 * @param  int|null  $post_id If wanting to define the parent post, pass a post ID.
	 * @param boolean   $add_directory_args True: Add args to help return to directory; False: only include args required to get to entry.
	 * @param int       $view_id
	 *
	 * @return string Link to the entry with the directory parent slug, or empty string if embedded post or View doesn't exist
	 */
	public static function entry_link( $entry, $post_id = null, $add_directory_args = true, $view_id = 0 ) {

		if ( ! empty( $entry ) && ! is_array( $entry ) ) {
			$entry = GVCommon::get_entry( $entry );
		} elseif ( empty( $entry ) ) {
			// @deprecated path
			$entry = GravityView_frontend::getInstance()->getEntry();
		}

		// Second parameter used to be passed as $field; this makes sure it's not an array
		if ( ! is_numeric( $post_id ) ) {
			$post_id = null;
		}

		// Get the permalink to the View
		$directory_link = self::directory_link( $post_id, false );

		// No post ID? Get outta here.
		if ( empty( $directory_link ) ) {
			return '';
		}

		$query_arg_name = \GV\Entry::get_endpoint_name();

		if ( ! empty( $entry['_multi'] ) ) {
			$entry_slugs = array();

			foreach ( $entry['_multi'] as $_multi ) {

				if ( $gv_multi = \GV\GF_Entry::from_entry( $_multi ) ) {
					$entry_slugs[] = $gv_multi->get_slug();
				} else {
					// TODO: This path isn't covered by unit tests
					$entry_slugs[] = self::get_entry_slug( $_multi['id'], $_multi );
				}

				unset( $gv_multi );

				$forms[] = $_multi['form_id'];
			}

			$entry_slug = implode( ',', $entry_slugs );
		} else {

			// Fallback when
			if ( $gv_entry = \GV\GF_Entry::from_entry( $entry ) ) {
				$entry_slug = $gv_entry->get_slug();
			} else {
				// TODO: This path isn't covered by unit tests
				$entry_slug = self::get_entry_slug( $entry['id'], $entry );
			}

			unset( $gv_entry );
		}

		$args = array();

		/**
		 * Modify whether to include passed $_GET parameters to the end of the url.
		 *
		 * @since 2.10
		 * @param bool $add_query_params Whether to include passed $_GET parameters to the end of the Entry Link URL. Default: true.
		 */
		$add_query_args = apply_filters( 'gravityview/entry_link/add_query_args', true );

		if ( $add_query_args ) {
			$args = gv_get_query_args();
		}

		if ( get_option( 'permalink_structure' ) && ! is_preview() ) {

			/**
			 * Make sure the $directory_link doesn't contain any query otherwise it will break when adding the entry slug.
			 *
			 * @since 1.16.5
			 */
			$link_parts = explode( '?', $directory_link );

			$query = ! empty( $link_parts[1] ) ? '?' . $link_parts[1] : '';

			$directory_link = trailingslashit( $link_parts[0] ) . $query_arg_name . '/' . $entry_slug . '/' . $query;

		} else {

			$args[] = array( $query_arg_name => $entry_slug );
		}

		/**
		 * @since 1.7.3
		 */
		if ( $add_directory_args ) {

			if ( ! empty( $_GET['pagenum'] ) ) {
				$args['pagenum'] = intval( $_GET['pagenum'] );
			}

			/**
			 * @since 1.7
			 */
			if ( $sort = \GV\Utils::_GET( 'sort' ) ) {
				$args['sort'] = $sort;
				$args['dir']  = \GV\Utils::_GET( 'dir' );
			}
		}

		if ( $post_id ) {
			$passed_post        = get_post( $post_id );
			$views              = \GV\View_Collection::from_post( $passed_post );
			$has_multiple_views = $views->count() > 1;
		} else {
			$has_multiple_views = class_exists( 'GravityView_View_Data' ) && GravityView_View_Data::getInstance()->has_multiple_views();
		}

		if ( $has_multiple_views ) {
			$args['gvid'] = $view_id ? $view_id : gravityview_get_view_id();
		}

		return add_query_arg( $args, $directory_link );
	}
}

/**
 * Returns query parameters from $_GET with reserved internal GravityView keys removed
 *
 * @uses stripslashes_deep() $_GET is passed through stripslashes_deep().
 * @uses urldecode_deep() $_GET is passed through urldecode_deep().
 *
 * Important: The return value of gv_get_query_args() is not escaped by default. Output should be
 * late-escaped with esc_url() or similar to help prevent vulnerability to cross-site scripting
 * (XSS) attacks.
 *
 * @since 2.10
 *
 * @return array
 */
function gv_get_query_args() {

	$passed_get = isset( $_GET ) ? $_GET : array();

	$passed_get = stripslashes_deep( $passed_get );
	$passed_get = urldecode_deep( $passed_get );

	if ( empty( $passed_get ) ) {
		return array();
	}

	$query_args = $passed_get;

	$reserved_args = array(
		'entry',
		'gvid',
		'status',
		'action',
		'view_id',
		'entry_id',
		'pagenum',
		'gv_updated',
	);

	/**
	 * Modify the URL arguments that should not be used because they are internal to GravityView.
     *
	 * @since 2.10
	 * @param array $reserved_args Array of URL query keys that should not be used except internally.
	 */
	$reserved_args = apply_filters( 'gravityview/api/reserved_query_args', $reserved_args );

	foreach ( $reserved_args as $reserved_arg ) {
		unset( $query_args[ $reserved_arg ] );
	}

	return $query_args;
}


// inside loop functions

/**
 * @deprecated Use \GV\Field::get_label()
 */
function gv_label( $field, $entry = null ) {
	return GravityView_API::field_label( $field, $entry );
}

function gv_class( $field, $form = null, $entry = array() ) {
	return GravityView_API::field_class( $field, $form, $entry );
}

/**
 * Generate a CSS class to be added to the wrapper <div> of a View
 *
 * @since 1.5.4
 * @since 1.16 Added $echo parameter.
 * @since 2.0 Added $context parameter.
 *
 * @param string               $passed_css_class Default: `gv-container gv-container-{view id}`. If View is hidden until search, adds ` hidden`
 * @param boolean              $echo Whether to echo the output. Default: true
 * @param \GV\Template_Context $context The template context.
 *
 * @return string CSS class, sanitized by gravityview_sanitize_html_class()
 */
function gv_container_class( $passed_css_class = '', $echo = true, $context = null ) {
	if ( $context instanceof \GV\Template_Context ) {
		$hide          = false;
		$total_entries = 0;
		$view_id       = 0;
		if ( $context->view ) {
			$view_id = $context->view->ID;
			if ( $context->view->settings->get( 'hide_until_searched' ) ) {
				$hide = ( empty( $context->entry ) && ! $context->request->is_search() );
			}
		}
		if ( $context->entries ) {
			$total_entries = $context->entries->total();
		} elseif ( $context->entry ) {
			$total_entries = 1;
		}
	} else {
		/** @deprecated legacy execution path */
		$view_id       = GravityView_View::getInstance()->getViewId();
		$hide          = GravityView_View::getInstance()->isHideUntilSearched();
		$total_entries = GravityView_View::getInstance()->getTotalEntries();
	}

	$passed_css_class = trim( $passed_css_class );

	$default_css_class = ! empty( $view_id ) ? sprintf( 'gv-container gv-container-%d', $view_id ) : 'gv-container';

	if ( 0 === $total_entries ) {
		$default_css_class .= ' gv-container-no-results';

		if (
			! gravityview()->request->is_search()
			&& $context instanceof \GV\Template_Context
			&& 3 === (int) $context->view->settings->get( 'no_entries_options', '0' )
		) {
			$hide = true;
		}
	}

	if ( $hide ) {
		$default_css_class .= ' gv-hidden';
	}

	if ( $context instanceof \GV\Template_Context && $context->view ) {
		$default_css_class .= ' ' . $context->view->settings->get( 'class', '' );
	}

	$css_class = trim( $passed_css_class . ' ' . $default_css_class );

	/**
	 * Modify the CSS class to be added to the wrapper div of a View.
     *
	 * @since 1.5.4
	 * @param string $css_class Default: `gv-container gv-container-{view id}`. If View is hidden until search, adds ` hidden`. If the View has no results, adds `gv-container-no-results`
	 * @since 2.0
	 * @param \GV\Template_Context $context The context.
	 */
	$css_class = apply_filters( 'gravityview/render/container/class', $css_class, $context );

	$css_class = gravityview_sanitize_html_class( $css_class );

	if ( $echo ) {
		echo $css_class;
	}

	return $css_class;
}

/**
 * @deprecated Use \GV\Field_Template::render()
 */
function gv_value( $entry, $field ) {

	$value = GravityView_API::field_value( $entry, $field );

	if ( '' === $value ) {
		/**
		 * What to display when a field is empty.
		 *
		 * @param string $value (empty string)
		 */
		$value = apply_filters( 'gravityview_empty_value', '' );
	}

	return $value;
}

function gv_directory_link( $post = null, $add_pagination = true, $context = null ) {
	return GravityView_API::directory_link( $post, $add_pagination, $context );
}

function gv_entry_link( $entry, $post_id = null ) {
	return GravityView_API::entry_link( $entry, $post_id );
}

function gv_no_results( $wpautop = true, $context = null ) {
	return GravityView_API::no_results( $wpautop, $context );
}

/**
 * Generate HTML for the back link from single entry view
 *
 * @since 1.0.1
 * @since 2.0
 * @param \GV\Template_Context $context The context this link is being displayed from.
 * @return string|null      If no GV post exists, null. Otherwise, HTML string of back link.
 */
function gravityview_back_link( $context = null ) {

	$href = gv_directory_link( null, true, $context );

	/**
	 * Modify the back link URL.
     *
	 * @since 1.17.5
	 * @see gv_directory_link() Generated the original back link
	 * @param string $href Existing label URL
	 * @deprecated Use `gravityview/template/links/back/url`
	 */
	$href = apply_filters( 'gravityview_go_back_url', $href );

	/**
	 * Modify the back link URL.
     *
	 * @since 2.0
	 * @see gv_directory_link() Generated the original back link
	 * @param string $href Existing label URL
	 * @param \GV\Template_Context The context.
	 */
	$href = apply_filters( 'gravityview/template/links/back/url', $href, $context );

	if ( empty( $href ) ) {
		return null;
	}

	if ( $context instanceof \GV\Template_Context ) {
		$view_id    = $context->view->ID;
		$view_label = $context->template->get_back_label();
	} else {
		/** @deprecated legacy path */
		$gravityview_view = GravityView_View::getInstance();
		$view_id          = $gravityview_view->getViewId();
		$view_label       = $gravityview_view->getBackLinkLabel() ? $gravityview_view->getBackLinkLabel() : false;
	}

	/** Default */
	$label = $view_label ? $view_label : __( '&larr; Go back', 'gk-gravityview' );

	/**
	 * Modify the back link text.
     *
	 * @since 1.0.9
	 * @param string $label Existing label text
	 * @deprecated Use `gravityview/template/links/back/label`
	 */
	$label = apply_filters( 'gravityview_go_back_label', $label );

	/**
	 * Modify the back link text.
     *
	 * @since 2.0
	 * @see gv_directory_link() Generated the original back link
	 * @param string $label Existing label text
	 * @param \GV\Template_Context The context.
	 */
	$label = apply_filters( 'gravityview/template/links/back/label', $label, $context );

	/**
	 * Modify the attributes used on the back link anchor tag.
     *
	 * @since 2.1
	 * @param array $atts Original attributes, default: [ data-viewid => $view_id ]
	 * @param \GV\Template_Context The context.
	 */
	$atts = apply_filters( 'gravityview/template/links/back/atts', array( 'data-viewid' => $view_id ), $context );

	$link = gravityview_get_link( $href, esc_html( $label ), $atts );

	return $link;
}

/**
 * Handle getting values for complex Gravity Forms fields
 *
 * If the field is complex, like a product, the field ID, for example, 11, won't exist. Instead,
 * it will be 11.1, 11.2, and 11.3. This handles being passed 11 and 11.2 with the same function.
 *
 * @since 1.0.4
 * @param  array  $entry    GF entry array
 * @param  string $field_id [description]
 * @param  string $display_value The value generated by Gravity Forms
 * @return string                Value
 */
function gravityview_get_field_value( $entry, $field_id, $display_value ) {

	if ( floatval( $field_id ) === floor( floatval( $field_id ) ) ) {

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
 * @param  string $value    Existing value
 * @param  string $taxonomy Type of term (`post_tag` or `category`)
 * @return string                CSV of linked terms
 */
function gravityview_convert_value_to_term_list( $value, $taxonomy = 'post_tag' ) {

	$output = array();

	if ( is_array( $value ) ) {
		$terms = array_filter( array_values( $value ), 'strlen' );
	} else {
		$terms = explode( ', ', $value );
	}

	foreach ( $terms as $term_name ) {

		// If we're processing a category,
		if ( 'category' === $taxonomy ) {

			// Use rgexplode to prevent errors if : doesn't exist
			list( $term_name, $term_id ) = rgexplode( ':', $term_name, 2 );

			// The explode was succesful; we have the category ID
			if ( ! empty( $term_id ) ) {
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
		if ( $term ) {

			$term_link = get_term_link( $term, $taxonomy );

			// If there was an error, continue to the next term.
			if ( is_wp_error( $term_link ) ) {
			    continue;
			}

			$output[] = gravityview_get_link( $term_link, esc_html( $term->name ) );
		}
	}

	return implode( ', ', $output );
}

/**
 * Get the links for post_tags and post_category output based on post ID
 *
 * @param  int     $post_id  The ID of the post
 * @param  boolean $link     Add links or no?
 * @param  string  $taxonomy Taxonomy of term to fetch.
 * @return string                String with terms
 */
function gravityview_get_the_term_list( $post_id, $link = true, $taxonomy = 'post_tag' ) {

	$output = get_the_term_list( $post_id, $taxonomy, null, ', ' );

	if ( empty( $link ) ) {
		return wp_strip_all_tags( $output );
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
	if ( ! $fe->getGvOutputData() ) {

		gravityview()->log->debug( 'gv_output_data not defined; parsing content.' );

		$fe->parse_content();
	}

	// Make 100% sure that we're dealing with a properly called situation
	if ( ! is_a( $fe->getGvOutputData(), 'GravityView_View_Data' ) ) {

		gravityview()->log->debug( 'gv_output_data not an object or get_view not callable.', array( 'data' => $fe->getGvOutputData() ) );

		return array();
	}

	return $fe->getGvOutputData()->get_views();
}

/**
 * Get data for a specific view
 *
 * @deprecated use \GV\View API instead
 * @since 2.5
 *
 * @see  GravityView_View_Data::get_view()
 * @return array View data with `id`, `view_id`, `form_id`, `template_id`, `atts`, `fields`, `widgets`, `form` keys.
 */
function gravityview_get_current_view_data( $view_id = 0 ) {
	if ( $view_id ) {
		if ( $view = \GV\View::by_id( $view_id ) ) {
			return $view; // implements ArrayAccess
		}
		return array();
	}

	$fe = GravityView_frontend::getInstance();

	// If not set, grab the current view ID
	if ( empty( $view_id ) ) {
		$view_id = $fe->get_context_view_id();
	}

	if ( ! $fe->getGvOutputData() ) {
		return array(); }

	return $fe->getGvOutputData()->get_view( $view_id );
}

// Templates' hooks
function gravityview_before() {
	/**
	 * Append content to the view.
     *
	 * @param object $gravityview The $gravityview object available in templates.
	 */
	if ( count( $args = func_get_args() ) ) {
		$gravityview = reset( $args );
		if ( $gravityview instanceof \GV\Template_Context ) {
			/**
			 * Prepend content to the View.
			 *
			 * @param \GV\Template_Context $gravityview The $gravityview object available in templates.
			 */
			do_action( 'gravityview/template/before', $gravityview );

			/**
			 * @deprecated Use `gravityview/template/before`
			 */
			return do_action( 'gravityview_before', $gravityview->view->ID );
		}
	}

	/**
	 * Prepend content to the View container div.
     *
	 * @deprecated Use `gravityview/template/before`.
	 * @param int $view_id The ID of the View being displayed
	 */
	do_action( 'gravityview_before', gravityview_get_view_id() );
}

function gravityview_header() {
	/**
	 * Append content to the view.
     *
	 * @param object $gravityview The $gravityview object available in templates.
	 */
	if ( count( $args = func_get_args() ) ) {
		$gravityview = reset( $args );
		if ( $gravityview instanceof \GV\Template_Context ) {
			/**
			 * Prepend content to the View container div.
			 *
			 * @param \GV\Template_Context $gravityview The $gravityview object available in templates.
			 */
			do_action( 'gravityview/template/header', $gravityview );

			/**
			 * @deprecated Use `gravityview/template/header`
			 */
			return do_action( 'gravityview_header', $gravityview->view->ID );
		}
	}

	/**
	 * Prepend content to the View container div.
     *
	 * @deprecated Use `gravityview/template/header`.
	 * @param int $view_id The ID of the View being displayed
	 */
	do_action( 'gravityview_header', gravityview_get_view_id() );
}

function gravityview_footer() {
	/**
	 * Append content to the view.
     *
	 * @param object $gravityview The $gravityview object available in templates.
	 */
	if ( count( $args = func_get_args() ) ) {
		$gravityview = reset( $args );
		if ( $gravityview instanceof \GV\Template_Context ) {
			/**
			 * Prepend outside the View container div.
			 *
			 * @param \GV\Template_Context $gravityview The $gravityview object available in templates.
			 */
			do_action( 'gravityview/template/footer', $gravityview );

			/**
			 * @deprecated Use `gravityview/template/footer`
			 */
			return do_action( 'gravityview_footer', $gravityview->view->ID );
		}
	}

	/**
	 * Display content after a View. Used to render footer widget areas. Rendered outside the View container div.
     *
	 * @deprecated Use `gravityview/template/footer`.
	 * @param int $view_id The ID of the View being displayed
	 */
	do_action( 'gravityview_footer', gravityview_get_view_id() );
}

function gravityview_after() {
	if ( count( $args = func_get_args() ) ) {
		$gravityview = reset( $args );
		if ( $gravityview instanceof \GV\Template_Context ) {
			/**
			 * Append content to the View.
			 *
			 * @param \GV\Template_Context $gravityview The $gravityview object available in templates.
			 * @since 2.0
			 */
			do_action( 'gravityview/template/after', $gravityview );

			/**
			 * @deprecated Use `gravityview/template/after`
			 */
			do_action( 'gravityview_after', $gravityview->view->ID );

			return;
		}
	}

	/**
	 * Append content to the View container div.
     *
	 * @deprecated Use `gravityview/template/after`
	 * @param int $view_id The ID of the View being displayed
	 */
	do_action( 'gravityview_after', gravityview_get_view_id() );
}

/**
 * Get the current View ID being rendered
 *
 * @global GravityView_View $gravityview_view
 *
 * @return int View ID, if exists. `0` if `GravityView_View` doesn't exist, like in the admin, or no View is set.
 */
function gravityview_get_view_id() {

	if ( ! class_exists( 'GravityView_View' ) ) {
		return 0;
	}

	return GravityView_View::getInstance()->getViewId();
}

/**
 * Returns the current GravityView context, or empty string if not GravityView
 *
 * - Returns empty string on GravityView archive pages
 * - Returns empty string on archive pages containing embedded Views
 * - Returns empty string for embedded Views, not 'directory'
 * - Returns empty string for embedded entries (oEmbed or [gventry]), not 'single'
 * - Returns 'single' when viewing a [gravityview] shortcode-embedded single entry
 *
 * @global GravityView_View $gravityview_view
 * @deprecated since 2.0.6.2 Use `gravityview()->request`
 * @return string View context "directory", "single", "edit", or empty string if not GravityView
 */
function gravityview_get_context() {
	global $wp_query;

	if ( isset( $wp_query ) && $wp_query->post_count > 1 ) {
		return '';
	}

	if ( gravityview()->request->is_edit_entry() ) {
		return 'edit';
	} elseif ( gravityview()->request->is_entry() ) {
		return 'single';
	} elseif ( gravityview()->request->is_view( false ) ) {
		return 'directory';
	} elseif ( gravityview()->views->get() ) {
		return 'directory';
	}

	return '';
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
 * @param  string               $value    Field value passed by Gravity Forms. String of file URL, or serialized string of file URL array
 * @param  string               $gv_class Field class to add to the output HTML
 * @since  2.0
 * @param  \GV\Template_Context $context The context
 * @return array           Array of file output, with `file_path` and `html` keys (see comments above)
 */
function gravityview_get_files_array( $value, $gv_class = '', $context = null ) {
	/** @define "GRAVITYVIEW_DIR" "../" */

	if ( ! class_exists( 'GravityView_Field' ) ) {
		include_once GRAVITYVIEW_DIR . 'includes/fields/class-gravityview-field.php';
	}

	if ( ! class_exists( 'GravityView_Field_FileUpload' ) ) {
		include_once GRAVITYVIEW_DIR . 'includes/fields/class-gravityview-field-fileupload.php';
	}

	if ( is_null( $context ) ) {
		_doing_it_wrong( __FUNCTION__, '2.0', 'Please pass an \GV\Template_Context object as the 3rd parameter' );
	}

	return GravityView_Field_FileUpload::get_files_array( $value, $gv_class, $context );
}

/**
 * Generate a mapping link from an address
 *
 * The address should be plain text with new line (`\n`) or `<br />` line breaks separating sections
 *
 * @since 2.14.1 Added $atts parameter
 *
 * @todo use GF's field get_export_value() instead
 *
 * @see https://docs.gravitykit.com/article/59-modify-the-map-it-address-link Read how to modify the link
 * @param  string $address Address
 * @return string          URL of link to map of address
 */
function gravityview_get_map_link( $address, $atts = array() ) {
	$address_qs = str_replace( array( '<br />', "\n" ), ' ', $address ); // Replace \n with spaces
	$address_qs = urlencode( $address_qs );

	$url = "https://maps.google.com/maps?q={$address_qs}";

	$link_text = esc_html__( 'Map It', 'gk-gravityview' );

	$atts = array_merge(
		array(
			'class' => 'map-it-link',
		),
		$atts
	);

	$link = gravityview_get_link( $url, $link_text, $atts );

	/**
	 * Modify the map link generated. You can use a different mapping service, for example.
     *
	 * @param  string $link Map link
	 * @param string $address Address to generate link for
	 * @param string $url URL generated by the function
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
 * @param  array                                     $passed_args Associative array with field data. `field` and `form` are required.
 * @since  2.0
 * @param  \GV\Template_Context The template context.
 * @return string Field output. If empty value and hide empty is true, return empty.
 */
function gravityview_field_output( $passed_args, $context = null ) {
	$defaults = array(
		'entry'        => null,
		'field'        => null,
		'form'         => null,
		'hide_empty'   => true,
		'markup'       => '<div id="{{ field_id }}" class="{{ class }}">{{ label }}{{ value }}</div>',
		'label_markup' => '',
		'wpautop'      => false,
		'zone_id'      => null,
	);

	$args = wp_parse_args( $passed_args, $defaults );

	/**
	 * Modify the args before generation begins.
     *
	 * @since 1.7
	 * @param array $args Associative array; `field` and `form` is required.
	 * @param array $passed_args Original associative array with field data. `field` and `form` are required.
	 * @since 2.0
	 * @param \GV\Template_Context $context The context.
	 * @deprecated
	 */
	$args = apply_filters( 'gravityview/field_output/args', $args, $passed_args, $context );

	/**
	 * Modify the context before generation begins.
     *
	 * @since 2.0
	 * @param \GV\Template_Context $context The context.
	 * @param array $args The sanitized arguments, these should not be trusted any longer.
	 * @param array $passed_args The passed arguments, these should not be trusted any longer.
	 */
	$context = apply_filters( 'gravityview/template/field_output/context', $context, $args, $passed_args );

	if ( $context instanceof \GV\Template_Context ) {
		if ( ! $context->field || ! $context->view || ! $context->view->form ) {
			gravityview()->log->error( 'Field or form are empty.', array( 'data' => array( $context->field, $context->view->form ) ) );
			return '';
		}
	} else {
		// @deprecated path
		// Required fields.
		if ( empty( $args['field'] ) || empty( $args['form'] ) ) {
			gravityview()->log->error( 'Field or form are empty.', array( 'data' => $args ) );
			return '';
		}
	}

	if ( $context instanceof \GV\Template_Context ) {
		$entry = $args['entry'] ? : ( $context->entry ? $context->entry->as_entry() : array() );
		$field = $args['field'] ? : ( $context->field ? $context->field->as_configuration() : array() );
		$form  = $args['form'] ? : ( $context->view->form ? $context->view->form->form : array() );
	} else {
		// @deprecated path
		$entry = empty( $args['entry'] ) ? array() : $args['entry'];
		$field = $args['field'];
		$form  = $args['form'];
	}

	/**
	 * Create the content variables for replacing.
     *
	 * @since 1.11
	 */
	$placeholders = [
		'value'                  => '',
		'width'                  => '',
		'width:style'            => '',
		'label'                  => '',
		'label_value'            => '',
		'label_value:esc_attr'   => '',
		'label_value:data-label' => '',
		'class'                  => '',
		'field_id'               => '',
		'rowspan'                => $args['rowspan'] ?? null,
		'row'                    => $args['row'] ?? 0,
	];

	if ( $context instanceof \GV\Template_Context ) {
		$placeholders['value'] = \GV\Utils::get( $args, 'value', '' );
	} else {
		// @deprecated path
		$placeholders['value'] = gv_value( $entry, $field );
	}

	// If the value is empty and we're hiding empty, return empty.
	if ( '' === $placeholders['value'] && ! empty( $args['hide_empty'] ) ) {
		return '';
	}

	if ( '' !== $placeholders['value'] && ! empty( $args['wpautop'] ) && 'gravityview_view' !== ( $field['id'] ?? '' ) ) {
		$placeholders['value'] = wpautop( $placeholders['value'] );
	}

	// Get width setting, if exists
	$placeholders['width'] = GravityView_API::field_width( $field );

	// If replacing with CSS inline formatting, let's do it.
	$placeholders['width:style'] = (string) GravityView_API::field_width( $field, 'width:' . $placeholders['width'] . '%;' );

	// Grab the Class using `gv_class`
	$placeholders['class']    = gv_class( $field, $form, $entry );
	$placeholders['field_id'] = GravityView_API::field_html_attr_id( $field, $form, $entry );

	if ( $context instanceof \GV\Template_Context ) {
		$placeholders['label_value'] = \GV\Utils::get( $args, 'label', '' );
	} else {
		// Default Label value
		$placeholders['label_value'] = gv_label( $field, $entry );
	}

	$placeholders['label_value:data-label'] = trim( esc_attr( strip_tags( str_replace( '>&nbsp;', '>', $placeholders['label_value'] ) ) ) );
	$placeholders['label_value:esc_attr']   = esc_attr( $placeholders['label_value'] );

	if ( empty( $placeholders['label'] ) && ! empty( $placeholders['label_value'] ) ) {
		$placeholders['label'] = '<span class="gv-field-label">{{ label_value }}</span>';
	}

	/**
	 * Allow Pre filtering of the HTML.
     *
	 * @since 1.11
	 * @param string $markup The HTML for the markup
	 * @param array $args All args for the field output
	 * @since 2.0
	 * @param \GV\Template_Context $context The context.
	 */
	$html = apply_filters( 'gravityview/field_output/pre_html', $args['markup'], $args, $context );

	/**
	 * Modify the opening tags for the template content placeholders.
     *
	 * @since 1.11
	 * @param string $open_tag Open tag for template content placeholders. Default: `{{`
	 * @since 2.0
	 * @param \GV\Template_Context $context The context.
	 */
	$open_tag = apply_filters( 'gravityview/field_output/open_tag', '{{', $args, $context );

	/**
	 * Modify the closing tags for the template content placeholders.
     *
	 * @since 1.11
	 * @param string $close_tag Close tag for template content placeholders. Default: `}}`
	 * @since 2.0
	 * @param \GV\Template_Context $context The context.
	 */
	$close_tag = apply_filters( 'gravityview/field_output/close_tag', '}}', $args, $context );

	/**
	 * Loop through each of the tags to replace and replace both `{{tag}}` and `{{ tag }}` with the values
     *
	 * @since 1.11
	 */
	foreach ( $placeholders as $tag => $value ) {

		// If the tag doesn't exist just skip it
		if ( false === strpos( $html, $open_tag . $tag . $close_tag ) && false === strpos( $html, $open_tag . ' ' . $tag . ' ' . $close_tag ) ) {
			continue;
		}

		// Array to search
		$search = array(
			$open_tag . $tag . $close_tag,
			$open_tag . ' ' . $tag . ' ' . $close_tag,
		);

		/**
		 * `gravityview/field_output/context/{$tag}` Allow users to filter content on context
		 *
		 * @since 1.11
		 * @param string $value The content to be shown instead of the {{tag}} placeholder
		 * @param array $args Arguments passed to the function
		 * @since 2.0
		 * @param \GV\Template_Context $context The context.
		 */
		$value = apply_filters( 'gravityview/field_output/context/' . $tag, $value, $args, $context );

		// Finally do the replace
		$html = str_replace( $search, (string) $value, $html );
	}

	/**
	 * Modify field HTML output.
     *
	 * @param string $html Existing HTML output
	 * @param array $args Arguments passed to the function
	 * @since 2.0
	 * @param \GV\Template_Context $context The context.
	 */
	$html = apply_filters( 'gravityview_field_output', $html, $args, $context );

	/**
	 * Modify field HTML output.
     *
	 * @param string $html Existing HTML output
	 * @param array $args Arguments passed to the function
	 * @since 2.0
	 * @param \GV\Template_Context $context The context.
	 */
	$html = apply_filters( 'gravityview/field_output/html', $html, $args, $context );

	/** @since 2.0.8 Remove unused atts */
	$html = str_replace( array( ' style=""', ' class=""', ' id=""' ), '', $html );

	return $html;
}
