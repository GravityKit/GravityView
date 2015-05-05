<?php
/**
 * Set of common functions to separate main plugin from Gravity Forms API and other cross-plugin methods
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.5.2
 */

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class GVCommon {

	/**
	 * Returns the form object for a given Form ID.
	 *
	 * @access public
	 * @param mixed $form_id
	 * @return mixed False: no form ID specified or Gravity Forms isn't active. Array: Form returned from Gravity Forms
	 */
	public static function get_form( $form_id ) {
		if( empty( $form_id ) ) {
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

	/**
	 * Return a Gravity Forms field array, whether using GF 1.9 or not
	 *
	 * @since 1.7
	 *
	 * @param array|GF_Fields $field Gravity Forms field or array
	 * @return array Array version of $field
	 */
	public static function get_field_array( $field ) {

		if( class_exists('GF_Fields') ) {

			$field_object = GF_Fields::create( $field );

			// Convert the field object in 1.9 to an array for backward compatibility
			$field_array = get_object_vars( $field_object );

		} else {
			$field_array = $field;
		}

		return $field_array;
	}

	/**
	 * Get all existing Views
	 *
	 * @since  1.5.4
	 * @return array Array of Views as `WP_Post`. Empty array if none found.
	 */
	public static function get_all_views() {

		$params = array(
			'post_type' => 'gravityview',
			'posts_per_page' => -1,
			'post_status' => 'publish',
		);

		/**
		 * Modify the parameters sent to get all views.
		 * @param  array $params description
		 */
		$views_params = apply_filters( 'gravityview/get_all_views/params', $params );

		$views = get_posts( $views_params );

		return $views;
	}


	/**
	 * Get the form array for an entry based only on the entry ID
	 * @param  int|string $entry_slug Entry slug
	 * @return array           Gravity Forms form array
	 */
	public static function get_form_from_entry_id( $entry_slug ) {

		$entry = self::get_entry( $entry_slug, true );

		$form = self::get_form( $entry['form_id'] );

		return $form;
	}

	/**
	 * Get the entry ID from the entry slug, which may or may not be the entry ID
	 *
	 * @since  1.5.2
	 * @param  string $slug The entry slug, as returned by GravityView_API::get_entry_slug()
	 * @return int|null       The entry ID, if exists; `NULL` if not
	 */
	public static function get_entry_id_from_slug( $slug ) {
		global $wpdb;

		$search_criteria = array(
			'field_filters' => array(
				array(
					'key' => 'gravityview_unique_id', // Search the meta values
					'value' => $slug,
                    'operator' => 'is',
                    'type' => 'meta'
				)
			)
		);

		// Limit to one for speed
		$paging = array(
			'page_size' => 1
		);

		$results = GFAPI::get_entries( 0, $search_criteria, NULL, $paging );

		$result = ( !empty( $results ) && !empty( $results[0]['id'] ) ) ? $results[0]['id'] : NULL;

		return $result;
	}


	/**
	 * Returns the list of available forms
	 *
	 * @access public
	 * @param mixed $form_id
	 * @return array (id, title)
	 */
	public static function get_forms() {

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


	/**
	 * Return array of fields' id and label, for a given Form ID
	 *
	 * @access public
	 * @param string|array $form_id (default: '') or $form object
	 * @return array
	 */
	public static function get_form_fields( $form = '', $add_default_properties = false, $include_parent_field = true ) {

		if( !is_array( $form ) ) {
			$form = self::get_form( $form );
		}

		$fields = array();
		$has_product_fields = false;
		$has_post_fields = false;

		// If GF_Field exists, we're using GF 1.9+, where add_default_properties has been deprecated.
		if( false === class_exists('GF_Field') && $add_default_properties ) {
			$form = RGFormsModel::add_default_properties( $form );
		}

		if( $form ) {

			foreach( $form['fields'] as $field ) {

				if( $include_parent_field || empty( $field['inputs'] ) ) {
					$fields[ $field['id'] ] = array(
						'label' => rgar( $field, 'label' ),
						'parent' => null,
						'type' => rgar( $field, 'type' ),
						'adminLabel' => rgar( $field, 'adminLabel' ),
						'adminOnly' => rgar( $field, 'adminOnly' ),
					);
				}

				if( $add_default_properties && !empty( $field['inputs'] ) ) {
					foreach( $field['inputs'] as $input ) {
						$fields[ (string)$input['id'] ] = array(
							'label' => rgar( $input, 'label' ),
							'customLabel' => rgar( $input, 'customLabel' ),
							'parent' => $field,
							'type' => rgar( $field, 'type' ),
							'adminLabel' => rgar( $field, 'adminLabel' ),
							'adminOnly' => rgar( $field, 'adminOnly' ),
						);
					}

				}

				if( GFCommon::is_product_field( $field['type'] ) ){
					$has_product_fields = true;
				}

				/**
				 * @hack Version 1.9
				 */
				$field_for_is_post_field = class_exists('GF_Fields') ? (object)$field : (array)$field;

				if( GFCommon::is_post_field( $field_for_is_post_field ) ) {
					$has_post_fields = true;
				}
			}
		}

		/**
		 * @since 1.7
		 */
		if( $has_post_fields ) {

			$fields['post_id'] = array(
				"label" => __( 'Post ID', 'gravityview' ),
				"type" => 'post_id'
			);

		}

		if( $has_product_fields ) {

			$fields['payment_status'] = array(
			    "label" => __( 'Payment Status', 'gravityview' ),
			    "type" => 'payment_status'
			);

			$fields['payment_date'] = array(
			    "label" => __( 'Payment Date', 'gravityview' ),
			    "type" => 'payment_date',
			);

			$fields['payment_amount'] = array(
			    "label" => __( 'Payment Amount', 'gravityview' ),
			    "type" => 'payment_amount'
			);

			$fields['payment_method'] = array(
			    "label" => __( 'Payment Method', 'gravityview' ),
			    "type" => 'payment_method'
			);

			$fields['is_fulfilled'] = array(
			    "label" => __( 'Is Fulfilled', 'gravityview' ),
			    "type" => 'is_fulfilled',
			);

			$fields['transaction_id'] = array(
			    "label" => __( 'Transaction ID', 'gravityview' ),
			    "type" => 'transaction_id',
			);

			$fields['transaction_type'] = array(
			    "label" => __( 'Transaction Type', 'gravityview' ),
			    "type" => 'transaction_type',
			);

		}

		return $fields;

	}


	/**
	 * get extra fields from entry meta
	 * @param  string $form_id (default: '')
	 * @return array
	 */
	public static function get_entry_meta( $form_id, $only_default_column = true ) {

		$extra_fields = GFFormsModel::get_entry_meta( $form_id );

		$fields = array();

		foreach( $extra_fields as $key => $field ){
			if( !empty( $only_default_column ) && !empty( $field['is_default_column'] ) ) {
				$fields[ $key ] = array( 'label' => $field['label'], 'type' => 'entry_meta' );
			}
	    }

	    return $fields;
	}


	/**
	 * Wrapper for the Gravity Forms GFFormsModel::search_lead_ids() method
	 *
	 * @see  GFEntryList::leads_page()
	 * @param  int $form_id ID of the Gravity Forms form
	 * @since  1.1.6
	 * @return array          Array of entry IDs
	 */
	public static function get_entry_ids( $form_id, $search_criteria = array() ) {

		if( !class_exists( 'GFFormsModel' ) ) { return; }

		return GFFormsModel::search_lead_ids( $form_id, $search_criteria );
	}

    /**
     * Calculates the Search Criteria used on the self::get_entries / self::get_entry methods
     *
     * @hook gravityview_search_criteria used by the Advanced Filter Extension
     *
     * @since 1.7.4
     *
     * @param null $passed_criteria array Input Criteria (search_criteria, sorting, paging)
     * @param null $form_ids array Gravity Forms form IDs
     * @return array|mixed|void
     */
    public static function calculate_get_entries_criteria( $passed_criteria = null, $form_ids = null ) {

        $search_criteria_defaults = array(
            'search_criteria' => null,
            'sorting' => null,
            'paging' => null,
            'cache' => (isset( $passed_criteria['cache'] ) ? $passed_criteria['cache'] : true),
        );

        $criteria = wp_parse_args( $passed_criteria, $search_criteria_defaults );

        if( !empty( $criteria['search_criteria']['field_filters'] ) ) {
            foreach ( $criteria['search_criteria']['field_filters'] as &$filter ) {

                if( !is_array( $filter ) ) { continue; }

                // By default, we want searches to be wildcard for each field.
                $filter['operator'] = empty( $filter['operator'] ) ? 'like' : $filter['operator'];
                $filter['operator'] = apply_filters( 'gravityview_search_operator', $filter['operator'], $filter );
            }
        }

        /**
         * Prepare date formats to be in Gravity Forms DB format;
         * $passed_criteria may include date formats incompatible with Gravity Forms.
         */
        foreach( array('start_date', 'end_date' ) as $key ) {

            if( !empty( $criteria['search_criteria'][ $key ] ) ) {

                // Use date_create instead of new DateTime so it returns false if invalid date format.
                $date = date_create( $criteria['search_criteria'][ $key ] );

                if( $date ) {

                    // Gravity Forms wants dates in the `Y-m-d H:i:s` format.
                    $criteria['search_criteria'][ $key ] = $date->format('Y-m-d H:i:s');

                } else {

                    // If it's an invalid date, unset it. Gravity Forms freaks out otherwise.
                    unset( $criteria['search_criteria'][ $key ] );

                    do_action( 'gravityview_log_error', '[filter_get_entries_criteria] '.$key.' Date format not valid:', $criteria['search_criteria'][ $key ] );
                }
            }
        }


        // When multiple views are embedded, OR single entry, calculate the context view id and send it to the advanced filter
        if( class_exists( 'GravityView_View_Data' ) && GravityView_View_Data::getInstance()->has_multiple_views() || GravityView_frontend::getInstance()->single_entry ) {
            $criteria['context_view_id'] = GravityView_frontend::getInstance()->get_context_view_id();
        } elseif( RGForms::get("action") === "delete" ) {
            $criteria['context_view_id'] = isset( $_GET['view_id'] ) ? $_GET['view_id'] : null;
        } else {
            $criteria['context_view_id'] = null;
        }

        // Apply final criteria filter (Used by the Advanced Filter extension)
        $criteria = apply_filters( 'gravityview_search_criteria', $criteria, $form_ids, $criteria['context_view_id'] );

        return $criteria;

    }


	/**
	 * Retrieve entries given search, sort, paging criteria
	 *
	 * @see  GFAPI::get_entries()
	 * @see GFFormsModel::get_field_filters_where()
	 * @access public
	 * @param int|array $form_ids The ID of the form or an array IDs of the Forms. Zero for all forms.
	 * @param mixed $passed_criteria (default: null)
	 * @param mixed &$total Optional. An output parameter containing the total number of entries. Pass a non-null value to generate the total count. (default: null)
	 * @return mixed False: Error fetching entries. Array: Multi-dimensional array of Gravity Forms entry arrays
	 */
	public static function get_entries( $form_ids = null, $passed_criteria = null, &$total = null ) {

        // Filter the criteria before query (includes Adv Filter)
        $criteria = self::calculate_get_entries_criteria( $passed_criteria, $form_ids );

		do_action( 'gravityview_log_debug', '[gravityview_get_entries] Final Parameters', $criteria );

		// Return value
		$return = NULL;

		if( !empty( $criteria['cache'] ) ) {

			$Cache = new GravityView_Cache( $form_ids, $criteria );

			if( $entries = $Cache->get() ) {

				// Still update the total count when using cached results
				if( !is_null( $total ) ) {
					$total = GFAPI::count_entries( $form_ids, $criteria['search_criteria'] );
				}

				$return = $entries;
			}

		}

		if( class_exists( 'GFAPI' ) && ( is_numeric( $form_ids ) || is_array( $form_ids ) ) ) {

			$entries = GFAPI::get_entries( $form_ids, $criteria['search_criteria'], $criteria['sorting'], $criteria['paging'], $total );

			if( is_wp_error( $entries ) ) {
				do_action( 'gravityview_log_error', $entries->get_error_message(), $entries );
				return false;
			}

			if( !empty( $criteria['cache'] ) ) {

				// Cache results
				$Cache->set( $entries, 'entries' );

			}

			$return = $entries;
		}

		/**
		 * Modify the array of entries returned to GravityView after it has been fetched from the cache or from `GFAPI::get_entries()`.
		 *
		 * @param  array|null $entries Array of entries as returned by the cache or by `GFAPI::get_entries()`
		 * @param  array $criteria The final search criteria used to generate the request to `GFAPI::get_entries()`
		 * @param array $passed_criteria The original search criteria passed to `GVCommon::get_entries()`
		 * @param  int|null $total Optional. An output parameter containing the total number of entries. Pass a non-null value to generate
		 * @var array
		 */
		$return = apply_filters( 'gravityview_entries', $return, $criteria, $passed_criteria, $total );

		return $return;
	}


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
	public static function get_entry( $entry_slug, $force_allow_ids = false ) {

		if( class_exists( 'GFAPI' ) && !empty( $entry_slug ) ) {

			/**
			 * Enable custom entry slug functionality.
			 *
			 * @see  GravityView_API::get_entry_slug()
			 * @var boolean
			 */
			$custom_slug = apply_filters('gravityview_custom_entry_slug', false );

			/**
			 * When using a custom slug, allow access to the entry using the original slug (the Entry ID).
			 *
			 * If disabled (default), only allow access to an entry using the custom slug value.  (example: `/entry/custom-slug/` NOT `/entry/123/`)
			 * If enabled, you could access using the custom slug OR the entry id (example: `/entry/custom-slug/` OR `/entry/123/`)
			 *
			 * @var boolean
			 */
			$custom_slug_id_access = $force_allow_ids || apply_filters('gravityview_custom_entry_slug_allow_id', false );

			/**
			 * If we're using custom entry slugs, we do a meta value search
			 * instead of doing a straightup ID search.
			 */
			if( $custom_slug ) {

                $entry_id = self::get_entry_id_from_slug( $entry_slug );

			}

			// If custom slug is off, search using the entry ID
			// ID allow ID access is on, also use entry ID as a backup
			if( empty( $custom_slug ) || !empty( $custom_slug_id_access ) ) {

				// Search for IDs matching $entry_slug
                $entry_id = $entry_slug;

			}

            if( empty( $entry_id ) ) {
                return false;
            }

            // fetch the entry
            $entry = GFAPI::get_entry( $entry_id );

            // Is the entry allowed
            $entry = self::check_entry_display( $entry );

            return $entry;

		}

		return false;
	}

	/**
	 * Wrapper for the GFFormsModel::matches_operation() method that adds additional comparisons, including:
	 * 'equals', 'greater_than_or_is', 'greater_than_or_equals', 'less_than_or_is', 'less_than_or_equals',
	 * and 'not_contains'
	 *
	 * @link http://docs.gravityview.co/article/252-gvlogic-shortcode
	 * @uses GFFormsModel::matches_operation
	 * @since 1.7.5
	 *
	 * @param string $val1 Left side of comparison
	 * @param string $val2 Right side of comparison
	 * @param string $operation Type of comparison
	 *
	 * @return bool True: matches, false: not matches
	 */
	public static function matches_operation( $val1, $val2, $operation ) {

		switch( $operation ) {
			case 'equals':
				$value = GFFormsModel::matches_operation( $val1, $val2, 'is' );
				break;
			case 'greater_than_or_is':
			case 'greater_than_or_equals':
				$is = GFFormsModel::matches_operation( $val1, $val2, 'is' );
				$gt = GFFormsModel::matches_operation( $val1, $val2, 'greater_than' );
				$value = ( $is || $gt );
				break;
			case 'less_than_or_is':
			case 'less_than_or_equals':
				$is = GFFormsModel::matches_operation( $val1, $val2, 'is' );
				$gt = GFFormsModel::matches_operation( $val1, $val2, 'less_than' );
				$value = ( $is || $gt );
				break;
			case 'not_contains':
				$contains = GFFormsModel::matches_operation( $val1, $val2, 'contains' );
				$value = !$contains;
				break;
			default:
				$value = GFFormsModel::matches_operation( $val1, $val2, $operation );
		}

		return $value;
	}

    /**
     *
     * Checks if a certain entry is valid according to the View search filters (specially the Adv Filters)
     *
     * @see GFFormsModel::is_value_match()
     *
     * @since 1.7.4
     *
     * @param array $entry Gravity Forms Entry object
     * @return bool|array Returns 'false' if entry is not valid according to the view search filters (Adv Filter)
     */
    public static function check_entry_display( $entry ) {

	    if( ! $entry || is_wp_error( $entry ) ) {
		    do_action( 'gravityview_log_debug', __METHOD__ . ' Entry was not found.', $entry );
		    return false;
	    }

        if( empty( $entry['form_id'] ) ) {
            do_action( 'gravityview_log_debug', '[apply_filters_to_entry] Entry is empty! Entry:', $entry );
            return false;
        }

        $criteria = self::calculate_get_entries_criteria();

        if( empty( $criteria['search_criteria'] ) || !is_array( $criteria['search_criteria'] ) ) {
            do_action( 'gravityview_log_debug', '[apply_filters_to_entry] Entry approved! No search criteria found:', $criteria );
            return $entry;
        }

        $search_criteria = $criteria['search_criteria'];
        unset( $criteria );

        // check entry status
        if( array_key_exists( 'status', $search_criteria ) && $search_criteria['status'] != $entry['status'] ) {
            do_action( 'gravityview_log_debug', sprintf( '[apply_filters_to_entry] Entry status - %s - is not valid according to filter:', $entry['status'] ), $search_criteria );
            return false;
        }

        // check entry date
        // @todo: Does it make sense to apply the Date create filters to the single entry?

        // field_filters
        if( empty( $search_criteria['field_filters'] ) || !is_array( $search_criteria['field_filters'] ) ) {
            do_action( 'gravityview_log_debug', '[apply_filters_to_entry] Entry approved! No field filters criteria found:', $criteria );
            return $entry;
        }

        $filters = $search_criteria['field_filters'];
        unset( $search_criteria );

        $mode = array_key_exists( 'mode', $filters ) ? strtolower( $filters['mode'] ) : 'all';
        unset( $filters['mode'] );

        $form = self::get_form( $entry['form_id'] );

        foreach( $filters as $filter ) {

            if( !isset( $filter['key'] ) ) {
                continue;
            }

            $k = $filter['key'];

            if( 'created_by' === $k ) {
                $field_value = $entry['created_by'];
                $field = null;
            } else {
                $field = self::get_field( $form, $k );
                $field_value  = GFFormsModel::get_lead_field_value( $entry, $field );
            }

            $operator = isset( $filter['operator'] ) ? strtolower( $filter['operator'] ) : 'is';
            $is_value_match = GFFormsModel::is_value_match( $field_value, $filter['value'], $operator, $field );

            // verify if we are already free to go!
            if( !$is_value_match && 'all' === $mode ) {
                do_action( 'gravityview_log_debug', '[apply_filters_to_entry] Entry cannot be displayed. Failed one criteria for ALL mode', $filter );
                return false;
            } elseif( $is_value_match && 'any' === $mode ) {
                return $entry;
            }

        }

        // at this point, if in ALL mode, then entry is approved - all conditions were met.
        // Or, for ANY mode, means none of the conditions were satisfied, so entry is not approved
        if( 'all' === $mode ) {
            return $entry;
        } else {
            do_action( 'gravityview_log_debug', '[apply_filters_to_entry] Entry cannot be displayed. Failed all the criteria for ANY mode', $filters );
            return false;
        }

    }


	/**
	 * Retrieve the label of a given field id (for a specific form)
	 *
	 * @access public
	 * @param mixed $form
	 * @param mixed $field_id
	 * @return string
	 */
	public static function get_field_label( $form, $field_id ) {

		if( empty($form) || empty( $field_id ) ) {
			return '';
		}

		$field = self::get_field( $form, $field_id );
		return isset( $field['label'] ) ?  $field['label'] : '';

	}


	/**
	 * Returns the field details array of a specific form given the field id
	 *
	 * @access public
	 * @param mixed $form
	 * @param mixed $field_id
	 * @return array|null Array: Gravity Forms field array; NULL: Gravity Forms GFFormsModel does not exist
	 */
	public static function get_field( $form, $field_id ) {
		if( class_exists( 'GFFormsModel') ){
			return GFFormsModel::get_field( $form, $field_id );
		} else {
			return null;
		}
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
	public static function has_gravityview_shortcode( $post = NULL ) {

		if( !is_a( $post, 'WP_Post' ) ) {
			return false;
		}

		if( 'gravityview' === get_post_type( $post ) ) {
			return true;
		}

		return self::has_shortcode_r( $post->post_content, 'gravityview');

	}


	/**
	 * Placeholder until the recursive has_shortcode() patch is merged
	 * @link https://core.trac.wordpress.org/ticket/26343#comment:10
	 */
	public static function has_shortcode_r( $content, $tag = 'gravityview' ) {
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

				} else if ( isset( $shortcode[5] ) && $result = self::has_shortcode_r( $shortcode[5], $tag ) ) {
					$shortcodes = $result;
				}
			}

			return $shortcodes;
		}
		return false;
	}



	/**
	 * Get the views for a particular form
	 * @param  int $form_id Gravity Forms form ID
	 * @return array          Array with view details
	 */
	public static function get_connected_views( $form_id ) {

		$views = get_posts(array(
			'post_type' => 'gravityview',
			'posts_per_page' => -1,
			'meta_key' => '_gravityview_form_id',
			'meta_value' => (int)$form_id,
		));

		return $views;
	}

	public static function get_meta_form_id( $post_id ) {
		return get_post_meta( $post_id, '_gravityview_form_id', true );
	}

	public static function get_meta_template_id( $post_id ) {
		return get_post_meta( $post_id, '_gravityview_directory_template', true );
	}


	/**
	 * Get all the settings for a View
	 *
	 * @uses  GravityView_View_Data::get_default_args() Parses the settings with the plugin defaults as backups.
	 * @param  int $post_id View ID
	 * @return array          Associative array of settings with plugin defaults used if not set by the View
	 */
	public static function get_template_settings( $post_id ) {

		$settings = get_post_meta( $post_id, '_gravityview_template_settings', true );

		if( class_exists( 'GravityView_View_Data' ) ) {

			$defaults = GravityView_View_Data::get_default_args();

			return wp_parse_args( (array)$settings, $defaults );

		}

		// Backup, in case GravityView_View_Data isn't loaded yet.
		return $settings;
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
	public static function get_template_setting( $post_id, $key ) {

		$settings = self::get_template_settings( $post_id );

		if( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}

		return NULL;
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
	public static function get_directory_fields( $post_id ) {
		return get_post_meta( $post_id, '_gravityview_directory_fields', true );
	}


	/**
	 * Render dropdown (select) with the list of sortable fields from a form ID
	 *
	 * @access public
	 * @param  int $formid Form ID
	 * @return string         html
	 */
	public static function get_sortable_fields( $formid, $current = '' ) {

		$output = '<option value="" '. selected( '', $current, false ).'>'. esc_html__( 'Default', 'gravityview') .'</option>';

		if( empty( $formid ) ) {
			return $output;
		}

		// Get fields with sub-inputs and no parent
		$fields = self::get_form_fields( $formid, true, false );

		if( !empty( $fields ) ) {

			$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array( 'list', 'textarea' ), NULL );

			$output .= '<option value="date_created" '. selected( 'date_created', $current, false ).'>'. esc_html__( 'Date Created', 'gravityview' ) .'</option>';

			foreach( $fields as $id => $field ) {

				if( in_array( $field['type'], $blacklist_field_types ) ) { continue; }

				$output .= '<option value="'. $id .'" '. selected( $id, $current, false ).'>'. esc_attr( $field['label'] ) .'</option>';
			}

		}
		return $output;
	}


	/**
	 * Returns the GF Form field type for a certain field(id) of a form
	 * @param  object $form     Gravity Forms form
	 * @param  mixed $field_id Field ID or Field array
	 * @return string field type
	 */
	public static function get_field_type(  $form = null , $field_id = '' ) {

		if( !empty( $field_id ) && !is_array( $field_id ) ) {
			$field = self::get_field( $form, $field_id );
		} else {
			$field = $field_id;
		}

		return class_exists( 'RGFormsModel' ) ? RGFormsModel::get_input_type( $field ) : '';

	}


	/**
	 * Checks if the field type is a 'numeric' field type (e.g. to be used when sorting)
	 * @param  int|array  $form  form ID or form array
	 * @param  int|array  $field field key or field array
	 * @return boolean
	 */
	public static function is_field_numeric(  $form = null , $field = '' ) {

		$numeric_types = apply_filters( 'gravityview/common/numeric_types', array( 'number' ) );

		if( !is_array( $form ) && !is_array( $field ) ) {
			$form = self::get_form( $form );
		}

		$type = self::get_field_type( $form, $field );

		return in_array( $type, $numeric_types );

	}

	/**
	 * Encrypt content using Javascript so that it's hidden when JS is disabled.
	 *
	 * This is mostly used to hide email addresses from scraper bots.
	 *
	 * @param string $content Content to encrypt
	 * @param string $message Message shown if Javascript is disabled
	 *
	 * @uses StandalonePHPEnkoder
	 * @link  https://github.com/jnicol/standalone-phpenkoder
	 *
	 * @since 1.7
	 *
	 * @return string Content, encrypted
	 */
	public static function js_encrypt( $content, $message = '' ) {

		$output = $content;

		if( !class_exists( 'StandalonePHPEnkoder' ) ) {
			include_once( GRAVITYVIEW_DIR . 'includes/lib/standalone-phpenkoder/StandalonePHPEnkoder.php' );
		}

		if( class_exists( 'StandalonePHPEnkoder' ) ) {

			$enkoder = new StandalonePHPEnkoder;

			$message = empty( $message ) ? __( 'Email hidden; Javascript is required.', 'gravityview' ) : $message;

			/**
			 * Modify the message shown when Javascript is disabled
			 *
			 * @since 1.7
			 *
			 * @param string $message Existing message
			 * @param string $content Content to encrypt
			 *
			 */
			$enkoder->enkode_msg = apply_filters( 'gravityview/phpenkoder/msg', $message, $content );

			$output = $enkoder->enkode( $content );
		}

		return $output;
	}

	/**
	 *
	 * Do the same than parse_str without max_input_vars limitation:
	 * Parses $string as if it were the query string passed via a URL and sets variables in the current scope.
	 * @param $string array string to parse (not altered like in the original parse_str(), use the second parameter!)
	 * @param $result array  If the second parameter is present, variables are stored in this variable as array elements
	 * @return bool true or false if $string is an empty string
	 * @since  1.5.3
	 *
	 * @author rubo77 at https://gist.github.com/rubo77/6821632
	 **/
	public static function gv_parse_str( $string, &$result ) {
		if( empty( $string ) ) { return false; }

		$result = array();

		// find the pairs "name=value"
		$pairs = explode( '&', $string );

		foreach ( $pairs as $pair ) {
			// use the original parse_str() on each element
			parse_str( $pair, $params );

			$k = key( $params );
			if( !isset( $result[ $k ] ) ) {
				$result+=$params;
			} elseif( array_key_exists( $k, $params ) && is_array( $params[ $k ] ) ) {
				$result[ $k ] = self::array_merge_recursive_distinct( $result[ $k ], $params[ $k ] );
			}
		}
		return true;
	}


	/**
	 * Generate an HTML anchor tag with a list of supported attributes
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a Supported attributes defined here
	 *
	 * @since 1.6
	 *
	 * @param string $href URL of the link.
	 * @param string $anchor_text The text or HTML inside the anchor. This is not sanitized in the function.
	 * @param array $atts Attributes to be added to the anchor tag
	 *
	 * @return string HTML output of anchor link. If empty $href, returns NULL
	 */
	public static function get_link_html( $href = '', $anchor_text = '', $atts = array() ) {

		// Supported attributes for anchor tags. HREF left out intentionally.
		$allowed_atts = array(
			'href' => NULL, // Will override the $href argument if set
			'title' => NULL,
			'rel' => NULL,
			'id' => NULL,
			'class' => NULL,
			'target' => NULL,
			'style' => NULL,

			// Used by GravityView
			'data-viewid' => NULL,

			// Not standard
			'hreflang' => NULL,
			'type' => NULL,
			'tabindex' => NULL,

			// Deprecated HTML4 but still used
			'name' => NULL,
			'onclick' => NULL,
			'onchange' => NULL,
			'onkeyup' => NULL,

			// HTML5 only
			'download' => NULL,
			'media' => NULL,
			'ping' => NULL,
		);

		/**
		 * Modify the attributes that are allowed to be used in generating links
		 *
		 * @param array $allowed_atts Array of attributes allowed
		 */
		$allowed_atts = apply_filters( 'gravityview/get_link/allowed_atts', $allowed_atts );

		// Make sure the attributes are formatted as array
		$passed_atts = wp_parse_args( $atts );

		// Make sure the allowed attributes are only the ones in the $allowed_atts list
		$final_atts = shortcode_atts( $allowed_atts, $passed_atts );

		// Remove attributes with empty values
		$final_atts = array_filter( $final_atts );

		// If the href wasn't passed as an attribute, use the value passed to the function
		if( empty( $final_atts['href'] ) && !empty( $href ) ) {
			$final_atts['href'] = $href;
		}

		$final_atts['href'] = esc_url( $href );

		// For each attribute, generate the code
		$output = '';
		foreach( $final_atts as $attr => $value ) {
			$output .= sprintf( ' %s="%s"', $attr, esc_attr( $value ) );
		}

		$output = '<a'. $output .'>'. $anchor_text .'</a>';

		return $output;
	}

	/**
	* array_merge_recursive does indeed merge arrays, but it converts values with duplicate
	* keys to arrays rather than overwriting the value in the first array with the duplicate
	* value in the second array, as array_merge does.
	*
	* @see http://php.net/manual/en/function.array-merge-recursive.php
	*
	* @since  1.5.3
	* @param array $array1
	* @param array $array2
	* @return array
	* @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
	* @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
	*/
	public static function array_merge_recursive_distinct ( array &$array1, array &$array2 ) {
		$merged = $array1;

		foreach ( $array2 as $key => &$value )  {
			if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) ) {
				$merged [$key] = self::array_merge_recursive_distinct ( $merged [$key], $value );
			} else {
				$merged [$key] = $value;
			}
		}

		return $merged;
	}

} //end class



	/**
	 * Generate an HTML anchor tag with a list of supported attributes
	 *
	 * @see GVCommon::get_link_html()
	 *
	 * @since 1.6
	 *
	 * @param string $href URL of the link.
	 * @param string $anchor_text The text or HTML inside the anchor. This is not sanitized in the function.
	 * @param array $atts Attributes to be added to the anchor tag
	 *
	 * @return string HTML output of anchor link. If empty $href, returns NULL
	 */
	function gravityview_get_link( $href = '', $anchor_text = '', $atts = array() ) {

		return GVCommon::get_link_html( $href, $anchor_text, $atts );
	}
