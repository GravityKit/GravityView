<?php
/**
 * Set of common functions to separate main plugin from Gravity Forms API and other cross-plugin methods
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
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
	 * Contains micro cached Forms.
	 *
	 * @since 2.42
	 *
	 * @var array
	 */
	private static array $forms = [];

	/**
	 * Clears the internal microcache.
	 *
	 * @since 2.42
	 */
	public static function clear_cache(): void {
		self::$forms = [];
	}

	/**
	 * Returns the form object for a given Form ID.
	 *
	 * @param int|string $form_id The Form ID.
	 *
	 * @return array|false Array: Form object returned from Gravity Forms; False: no form ID specified or Gravity Forms isn't active.
	 */
	public static function get_form( $form_id ) {

		if ( empty( $form_id ) ) {
			return false;
		}

		if ( ! class_exists( 'GFAPI' ) ) {
			return false;
		}

		if ( isset( self::$forms[ $form_id ] ) ) {
			return self::$forms[ $form_id ];
		}

		$form = \GFAPI::get_form( $form_id );

		self::$forms[ $form_id ] = $form;

		return $form;
	}

	/**
	 * Returns form object for existing form or a form template.
	 *
	 * @since 2.16
	 *
	 * @param int|string $form_id Gravity Forms form ID. Default: 0.
	 *
	 * @return array|false
	 */
	public static function get_form_or_form_template( $form_id = 0 ) {
		// Determine if form is a preset and convert it to an array with fields
		if ( is_string( $form_id ) && preg_match( '/^preset_/', $form_id ) ) {
			$form = GravityView_Ajax::pre_get_form_fields( $form_id );
		} else {
			$form = self::get_form( $form_id );
		}

		return $form;
	}

	/**
	 * Alias of GravityView_Roles_Capabilities::has_cap()
	 *
	 * @since 1.15
	 *
	 * @see GravityView_Roles_Capabilities::has_cap()
	 *
	 * @param string|array $caps Single capability or array of capabilities
	 * @param int          $object_id (optional) Parameter can be used to check for capabilities against a specific object, such as a post or user
	 * @param int|null     $user_id (optional) Check the capabilities for a user who is not necessarily the currently logged-in user
	 *
	 * @return bool True: user has at least one passed capability; False: user does not have any defined capabilities
	 */
	public static function has_cap( $caps = '', $object_id = null, $user_id = null ) {
		return GravityView_Roles_Capabilities::has_cap( $caps, $object_id, $user_id );
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

		if ( class_exists( 'GF_Fields' ) ) {

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
	 * @since 1.5.4 Added $args array
	 *
	 * @param array $args Pass custom array of args, formatted as if for `get_posts()`
	 *
	 * @return WP_Post[] Array of Views as `WP_Post`. Empty array if none found.
	 */
	public static function get_all_views( $args = array() ) {

		$default_params = array(
			'post_type'      => 'gravityview',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'exclude'        => array(),
		);

		$params = wp_parse_args( $args, $default_params );

		/**
		 * Modify the parameters sent to get all views.
		 *
		 * @param  array $params Array of parameters to pass to `get_posts()`
		 */
		$views_params = apply_filters( 'gravityview/get_all_views/params', $params );

		$views = get_posts( $views_params );

		return $views;
	}


	/**
	 * Get the form array for an entry based only on the entry ID
	 *
	 * @param  int|string $entry_slug Entry slug
	 * @return array|false Array: Form object returned from Gravity Forms; False: form doesn't exist, or $entry didn't exist or $entry didn't specify form ID
	 */
	public static function get_form_from_entry_id( $entry_slug ) {

		$entry = self::get_entry( $entry_slug, true, false );

		$form = false;

		if ( $entry ) {
			$form = self::get_form( $entry['form_id'] );
		}

		return $form;
	}

	/**
	 * Check whether a form has product fields
	 *
	 * @since 1.16
	 * @since 1.20 Refactored the field types to get_product_field_types() method
	 *
	 * @param array $form Gravity Forms form array
	 *
	 * @return bool|GF_Field[]
	 */
	public static function has_product_field( $form = array() ) {

		$product_fields = self::get_product_field_types();

		$fields = GFAPI::get_fields_by_type( $form, $product_fields );

		return empty( $fields ) ? false : $fields;
	}

	/**
	 * Return array of product field types
	 *
	 * Modify the value using the `gform_product_field_types` filter
	 *
	 * @since 1.20
	 *
	 * @return array
	 */
	public static function get_product_field_types() {

		$product_fields = apply_filters( 'gform_product_field_types', array( 'option', 'quantity', 'product', 'total', 'shipping', 'calculation', 'price', 'hiddenproduct', 'singleproduct', 'singleshipping' ) );

		return $product_fields;
	}

	/**
	 * Check if an entry has transaction data
	 *
	 * Checks the following keys to see if they are set: 'payment_status', 'payment_date', 'transaction_id', 'payment_amount', 'payment_method'
	 *
	 * @since 1.20
	 *
	 * @param array $entry Gravity Forms entry array
	 *
	 * @return bool True: Entry has metadata suggesting it has communicated with a payment gateway; False: it does not have that data.
	 */
	public static function entry_has_transaction_data( $entry = array() ) {

		if ( ! is_array( $entry ) ) {
			return false;
		}

		$has_transaction_data = false;

		$payment_meta = array( 'payment_status', 'payment_date', 'transaction_id', 'payment_amount', 'payment_method' );

		foreach ( $payment_meta as $meta ) {

			$has_transaction_data = \GV\Utils::get( $entry, $meta, false );

			if ( is_numeric( $has_transaction_data ) && ( ! floatval( $has_transaction_data ) > 0 ) ) {
				$has_transaction_data = false;
				continue;
			}

			if ( ! empty( $has_transaction_data ) ) {
				break;
			}
		}

		return (bool) $has_transaction_data;
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
					'key'      => 'gravityview_unique_id', // Search the meta values
					'value'    => $slug,
					'operator' => 'is',
					'type'     => 'meta',
				),
			),
		);

		// Limit to one for speed
		$paging = array(
			'page_size' => 1,
		);

		/**
		 * The form ID used to get the custom entry ID. Change this to avoid collisions with data from other forms with the same values and the same field ID.
		 *
		 * @since 1.17.2
		 * @param int $form_id ID of the form to search. Default: `0` (searches all forms)
		 */
		$form_id = apply_filters( 'gravityview/common/get_entry_id_from_slug/form_id', 0 );

		$results = GFAPI::get_entries( intval( $form_id ), $search_criteria, null, $paging );

		$result = ( ! empty( $results ) && ! empty( $results[0]['id'] ) ) ? $results[0]['id'] : null;

		return $result;
	}

	/**
	 * Alias of GFFormsModel::get_form_ids(), but allows for fetching other columns as well.
	 *
	 * @see GFFormsModel::get_form_ids()
	 * @since 2.17.2
	 *
	 * @param bool   $active      True if active forms are returned. False to get inactive forms. Defaults to true.
	 * @param bool   $trash       True if trashed forms are returned. False to exclude trash. Defaults to false.
	 * @param string $sort_column The column to sort the results on.
	 * @param string $sort_dir    The sort direction, ASC or DESC.
	 * @param array  $columns     The columns to return. Defaults to ['id']. Other options are 'title', 'date_created', 'is_active', 'is_trash'. 'date_updated' may be supported, depending on the Gravity Forms version.
	 *
	 * @return array Forms indexed from 0 by SQL result row number. Each row is an associative array (column => value).
	 */
	public static function get_forms_columns( $active = true, $trash = false, $sort_column = 'id', $sort_dir = 'ASC', $columns = array( 'id' ) ) {
		global $wpdb;

		// Only allow valid columns.
		$columns = array_intersect( $columns, GFFormsModel::get_form_db_columns() );

		$sql   = 'SELECT ' . implode( ', ', $columns ) . ' FROM ' . GFFormsModel::get_form_table_name();
		$where = array();

		if ( null !== $active ) {
			$where[] = $wpdb->prepare( 'is_active=%d', $active );
		}

		if ( null !== $trash ) {
			$where[] = $wpdb->prepare( 'is_trash=%d', $trash );
		}

		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . join( ' AND ', $where );
		}

		if ( ! in_array( strtolower( $sort_column ), GFFormsModel::get_form_db_columns() ) ) {
			$sort_column = 'id';
		}

		if ( ! empty( $sort_column ) ) {
			$sql .= " ORDER BY $sort_column " . ( 'ASC' == $sort_dir ? 'ASC' : 'DESC' );
		}

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Get all forms to use as options in View settings.
	 *
	 * @since 2.17
	 *
	 * @used-by \GV\View_Settings::defaults()
	 *
	 * @param bool   $active      True if active forms are returned. False to get inactive forms. Defaults to true.
	 * @param bool   $trash       True if trashed forms are returned. False to exclude trash. Defaults to false.
	 * @param string $sort_column The column to sort the results on.
	 * @param string $sort_dir    The sort direction, ASC or DESC.
	 *
	 * @return array
	 */
	public static function get_forms_as_options( $active = true, $trash = false, $sort_column = 'id', $sort_dir = 'ASC' ) {

		$options = array(
			'' => esc_html__( 'Select a Form', 'gk-gravityview' ),
		);

		// This is only used in the admin and in ajax, so don't run on the front-end.
		if ( gravityview()->request->is_frontend() ) {
			return $options;
		}

		static $static_cache;

		$static_cache_key = md5( json_encode( func_get_args() ) );

		if ( isset( $static_cache[ $static_cache_key ] ) ) {
			return $static_cache[ $static_cache_key ];
		}

		$forms = self::get_forms_columns( $active, $trash, $sort_column, $sort_dir, array( 'id', 'title', 'is_active' ) );

		if ( empty( $forms ) ) {
			return $options;
		}

		foreach ( $forms as $form ) {

			// Catch if the form is false, if GFFormsModel::get_form_ids() returns a form ID that doesn't exist.
			if ( ! isset( $form['id'], $form['title'] ) ) {
				continue;
			}

			$title = sprintf( '%s (#%d)', esc_html( $form['title'] ), (int) $form['id'] );

			if ( empty( $form['is_active'] ) ) {
				$title .= sprintf( ' (%s)', esc_html_x( 'Inactive', 'Indicates that a form is inactive.', 'gk-gravityview' ) );
			}

			$options[ (int) $form['id'] ] = $title;
		}

		$static_cache[ $static_cache_key ] = $options;

		return $options;
	}

	/**
	 * Alias of GFAPI::get_forms()
	 *
	 * @see GFAPI::get_forms()
	 *
	 * @since 1.19 Allow "any" $active status option.
	 * @since 2.7.2 Allow sorting forms using wp_list_sort().
	 * @since 2.17.6 Added `gravityview/common/get_forms` filter.
	 *
	 * @param bool|string  $active Status of forms. Use `any` to get array of forms with any status. Default: `true`.
	 * @param bool         $trash Include forms in trash? Default: `false`.
	 * @param string|array $order_by Optional. Either the field name to order by or an array of multiple orderby fields as $orderby => $order.
	 * @param string       $order Optional. Either 'ASC' or 'DESC'. Only used if $orderby is a string.
	 *
	 * @return array Empty array if GFAPI class isn't available or no forms. Otherwise, the array of Forms.
	 */
	public static function get_forms( $active = true, $trash = false, $order_by = 'id', $order = 'ASC' ) {
		$forms = array();
		if ( ! class_exists( 'GFAPI' ) ) {
			return array();
		}

		if ( 'any' === $active ) {
			$active_forms   = GFAPI::get_forms( true, $trash );
			$inactive_forms = GFAPI::get_forms( false, $trash );
			$forms          = array_merge( array_filter( $active_forms ), array_filter( $inactive_forms ) );
		} else {
			$forms = GFAPI::get_forms( $active, $trash );
		}


		// Handle case-insensitive title sorting with uppercase/lowercase letters
		if ( ! empty( $forms ) && 'title' === $order_by ) {
			uasort( $forms, function( $a, $b ) use ( $order ) {
				$result = strnatcasecmp( $a['title'], $b['title'] );
				return ( 'DESC' === $order ) ? -$result : $result;
			});
		} elseif ( ! empty( $forms ) ) {
			$forms = wp_list_sort( $forms, $order_by, $order, true );
		}

		/**
		 * Modify the forms returned by GFAPI::get_forms().
		 *
		 * @since 2.17.6
		 *
		 * @param array $forms Array of forms, with form ID as the key.
		 * @param bool|string $active Status of forms. Use `any` to get array of forms with any status. Default: `true`.
		 * @param bool $trash Include forms in trash? Default: `false`.
		 * @param string|array $order_by Optional. Either the field name to order by or an array of multiple orderby fields as $orderby => $order.
		 * @param string $order Optional. Either 'ASC' or 'DESC'. Only used if $orderby is a string.
		 *
		 * @return array Modified array of forms.
		 */
		$forms = apply_filters( 'gk/gravityview/common/get_forms', $forms, $active, $trash, $order_by, $order );

		return $forms;
	}

	/**
	 * Return array of fields' id and label, for a given Form ID
	 *
	 * @param string|array $form_id (default: '') or $form object
	 * @param bool         $add_default_properties
	 * @param bool         $include_parent_field
	 * @return array
	 */
	public static function get_form_fields( $form = '', $add_default_properties = false, $include_parent_field = true ) {

		if ( ! is_array( $form ) ) {
			$form = self::get_form( $form );
		}

		$fields             = array();
		$has_product_fields = false;
		$has_post_fields    = false;

		if ( $form ) {
			foreach ( $form['fields'] as $field ) {
				if ( $include_parent_field || empty( $field['inputs'] ) ) {
					$fields[ "{$field['id']}" ] = array(
						'label'      => \GV\Utils::get( $field, 'label' ),
						'parent'     => null,
						'type'       => \GV\Utils::get( $field, 'type' ),
						'adminLabel' => \GV\Utils::get( $field, 'adminLabel' ),
						'adminOnly'  => \GV\Utils::get( $field, 'adminOnly' ),
					);
				}

				if ( $add_default_properties && ! empty( $field['inputs'] ) ) {
					foreach ( $field['inputs'] as $input ) {

						if ( ! empty( $input['isHidden'] ) ) {
							continue;
						}

						/**
						 * @hack
						 * In case of email/email confirmation, the input for email has the same id as the parent field
						 */
						if ( 'email' === $field['type'] && false === strpos( $input['id'], '.' ) ) {
							continue;
						}
						$fields[ "{$input['id']}" ] = array(
							'label'       => \GV\Utils::get( $input, 'label' ),
							'customLabel' => \GV\Utils::get( $input, 'customLabel' ),
							'parent'      => $field,
							'type'        => \GV\Utils::get( $field, 'type' ),
							'adminLabel'  => \GV\Utils::get( $field, 'adminLabel' ),
							'adminOnly'   => \GV\Utils::get( $field, 'adminOnly' ),
						);
					}
				}

				if ( GFCommon::is_product_field( $field['type'] ) ) {
					$has_product_fields = true;
				}

				if ( GFCommon::is_post_field( $field ) ) {
					$has_post_fields = true;
				}
			}
		}

		/**
		 * @since 1.7
		 */
		if ( $has_post_fields ) {
			$fields['post_id'] = array(
				'label' => __( 'Post ID', 'gk-gravityview' ),
				'type'  => 'post_id',
			);
		}

		if ( $has_product_fields ) {

			$payment_fields = GravityView_Fields::get_all( 'pricing' );

			foreach ( $payment_fields as $payment_field ) {

				// Either the field exists ($fields['shipping']) or the form explicitly contains a `shipping` field with numeric key
				if ( isset( $fields[ "{$payment_field->name}" ] ) || GFCommon::get_fields_by_type( $form, $payment_field->name ) ) {
					continue;
				}

				$fields[ "{$payment_field->name}" ] = array(
					'label' => $payment_field->label,
					'desc'  => $payment_field->description,
					'type'  => $payment_field->name,
				);
			}
		}

		/**
		 * Modify the form fields shown in the Add Field field picker.
		 *
		 * @since 1.17
		 * @param array $fields Associative array of fields, with keys as field type, values an array with the following keys: (string) `label` (required), (string) `type` (required), `desc`, (string) `customLabel`, (GF_Field) `parent`, (string) `adminLabel`, (bool)`adminOnly`
		 * @param array $form GF Form array
		 * @param bool $include_parent_field Whether to include the parent field when getting a field with inputs
		 */
		$fields = apply_filters( 'gravityview/common/get_form_fields', $fields, $form, $include_parent_field );

		return $fields;
	}

	/**
	 * get extra fields from entry meta
	 *
	 * @param  string $form_id (default: '')
	 * @return array
	 */
	public static function get_entry_meta( $form_id, $only_default_column = true ) {

		$extra_fields = GFFormsModel::get_entry_meta( $form_id );

		$fields = array();

		foreach ( $extra_fields as $key => $field ) {
			if ( ! empty( $only_default_column ) && ! empty( $field['is_default_column'] ) ) {
				$fields[ $key ] = array(
					'label' => $field['label'],
					'type'  => 'entry_meta',
				);
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
	 * @return array|void          Array of entry IDs. Void if Gravity Forms isn't active.
	 */
	public static function get_entry_ids( $form_id, $search_criteria = array() ) {

		if ( ! class_exists( 'GFAPI' ) ) {
			return;
		}

		return GFAPI::get_entry_ids( $form_id, $search_criteria );
	}

	/**
	 * Calculates the Search Criteria used on the self::get_entries / self::get_entry methods
	 *
	 * @since 1.7.4
	 *
	 * @param array $passed_criteria array Input Criteria (search_criteria, sorting, paging)
	 * @param array $form_ids array Gravity Forms form IDs
	 * @return array
	 */
	public static function calculate_get_entries_criteria( $passed_criteria = array(), $form_ids = array() ) {

		$search_criteria_defaults = array(
			'search_criteria' => null,
			'sorting'         => null,
			'paging'          => null,
			'cache'           => ( isset( $passed_criteria['cache'] ) ? (bool) $passed_criteria['cache'] : true ),
			'context_view_id' => null,
		);

		$criteria = wp_parse_args( $passed_criteria, $search_criteria_defaults );

		if ( ! empty( $criteria['search_criteria']['field_filters'] ) && is_array( $criteria['search_criteria']['field_filters'] ) ) {
			foreach ( $criteria['search_criteria']['field_filters'] as &$filter ) {

				if ( ! is_array( $filter ) ) {
					continue;
				}

				// By default, we want searches to be wildcard for each field.
				$filter['operator'] = empty( $filter['operator'] ) ? 'contains' : $filter['operator'];

				/**
				 * Modify the search operator for the field (contains, is, isnot, etc).
				 *
				 * @param string $operator Existing search operator
				 * @param array $filter array with `key`, `value`, `operator`, `type` keys
				 */
				$filter['operator'] = apply_filters( 'gravityview_search_operator', $filter['operator'], $filter );
			}

			// don't send just the [mode] without any field filter.
			if ( 1 === count( $criteria['search_criteria']['field_filters'] ) && array_key_exists( 'mode', $criteria['search_criteria']['field_filters'] ) ) {
				unset( $criteria['search_criteria']['field_filters']['mode'] );
			}
		}

		/**
		 * Prepare date formats to be in Gravity Forms DB format;
		 * $passed_criteria may include date formats incompatible with Gravity Forms.
		 */
		foreach ( array( 'start_date', 'end_date' ) as $key ) {

			if ( ! empty( $criteria['search_criteria'][ $key ] ) ) {

				// Use date_create instead of new DateTime so it returns false if invalid date format.
				$date = date_create( $criteria['search_criteria'][ $key ] );

				if ( $date ) {
					// Gravity Forms wants dates in the `Y-m-d H:i:s` format.
					$criteria['search_criteria'][ $key ] = $date->format( 'Y-m-d H:i:s' );
				} else {
					gravityview()->log->error(
						'{key} Date format not valid:',
						array(
							'key' => $key,
							$criteria['search_criteria'][ $key ],
						)
					);

					// If it's an invalid date, unset it. Gravity Forms freaks out otherwise.
					unset( $criteria['search_criteria'][ $key ] );
				}
			}
		}

		if ( empty( $criteria['context_view_id'] ) ) {
			// Calculate the context view id and send it to the advanced filter
			if ( GravityView_frontend::getInstance()->getSingleEntry() ) {
				$criteria['context_view_id'] = GravityView_frontend::getInstance()->get_context_view_id();
			} elseif ( class_exists( 'GravityView_View_Data' ) && GravityView_View_Data::getInstance() && GravityView_View_Data::getInstance()->has_multiple_views() ) {
				$criteria['context_view_id'] = GravityView_frontend::getInstance()->get_context_view_id();
			} elseif ( 'delete' === GFForms::get( 'action' ) ) {
				$criteria['context_view_id'] = isset( $_GET['view_id'] ) ? intval( $_GET['view_id'] ) : null;
			}
		}

		/**
		 * Apply final criteria filter (Used by the Advanced Filter extension).
		 *
		 * @param array $criteria Search criteria used by GravityView
		 * @param array $form_ids Forms to search
		 * @param int $view_id ID of the view being used to search
		 */
		$criteria = apply_filters( 'gravityview_search_criteria', $criteria, $form_ids, $criteria['context_view_id'] );

		return (array) $criteria;
	}


	/**
	 * Retrieve entries given search, sort, paging criteria
	 *
	 * @see  GFAPI::get_entries()
	 * @see GFFormsModel::get_field_filters_where()
	 * @param int|array $form_ids The ID of the form or an array IDs of the Forms. Zero for all forms.
	 * @param mixed     $passed_criteria (default: null)
	 * @param mixed     &$total Optional. An output parameter containing the total number of entries. Pass a non-null value to generate the total count. (default: null)
	 *
	 * @deprecated {@see \GV\View::get_entries}
	 *
	 * @return mixed False: Error fetching entries. Array: Multi-dimensional array of Gravity Forms entry arrays
	 */
	public static function get_entries( $form_ids = null, $passed_criteria = null, &$total = null ) {

		gravityview()->log->notice( '\GVCommon::get_entries is deprecated. Use \GV\View::get_entries instead.' );

		// Filter the criteria before query (includes Adv Filter)
		$criteria = self::calculate_get_entries_criteria( $passed_criteria, $form_ids );

		gravityview()->log->debug( '[gravityview_get_entries] Final Parameters', array( 'data' => $criteria ) );

		// Return value
		$return = null;

		/** Reduce # of database calls */
		add_filter( 'gform_is_encrypted_field', '__return_false' );

		if ( ! empty( $criteria['cache'] ) ) {

			$Cache = new GravityView_Cache( $form_ids, $criteria );

			if ( $entries = $Cache->get() ) {

				// Still update the total count when using cached results
				if ( ! is_null( $total ) ) {
					$total = GFAPI::count_entries( $form_ids, $criteria['search_criteria'] );
				}

				$return = $entries;
			}
		}

		if ( is_null( $return ) && class_exists( 'GFAPI' ) && ( is_numeric( $form_ids ) || is_array( $form_ids ) ) ) {

			/**
			 * Define entries to be used before GFAPI::get_entries() is called.
			 *
			 * @since 1.14
			 * @param  null $return If you want to override GFAPI::get_entries() and define entries yourself, tap in here.
			 * @param  array $criteria The final search criteria used to generate the request to `GFAPI::get_entries()`
			 * @param array $passed_criteria The original search criteria passed to `GVCommon::get_entries()`
			 * @param  int|null $total Optional. An output parameter containing the total number of entries. Pass a non-null value to generate
			 * @since 2.1 The $total parameter can now be overriden by reference.
			 * @deprecated
			 */
			$entries = apply_filters_ref_array( 'gravityview_before_get_entries', array( null, $criteria, $passed_criteria, &$total ) );

			// No entries returned from gravityview_before_get_entries
			if ( is_null( $entries ) ) {

				$entries = GFAPI::get_entries( $form_ids, $criteria['search_criteria'], $criteria['sorting'], $criteria['paging'], $total );

				if ( is_wp_error( $entries ) ) {
					gravityview()->log->error(
						'{error}',
						array(
							'error' => $entries->get_error_message(),
							'data'  => $entries,
						)
					);

					/** Remove filter added above */
					remove_filter( 'gform_is_encrypted_field', '__return_false' );
					return false;
				}
			}

			if ( ! empty( $criteria['cache'] ) && isset( $Cache ) ) {

				// Cache results
				$Cache->set( $entries, 'entries' );

			}

			$return = $entries;
		}

		/** Remove filter added above */
		remove_filter( 'gform_is_encrypted_field', '__return_false' );

		/**
		 * Modify the array of entries returned to GravityView after it has been fetched from the cache or from `GFAPI::get_entries()`.
		 *
		 * @param  array|null $entries Array of entries as returned by the cache or by `GFAPI::get_entries()`
		 * @param  array $criteria The final search criteria used to generate the request to `GFAPI::get_entries()`
		 * @param array $passed_criteria The original search criteria passed to `GVCommon::get_entries()`
		 * @param  int|null $total Optional. An output parameter containing the total number of entries. Pass a non-null value to generate
		 * @since 2.1 The $total parameter can now be overriden by reference.
		 * @deprecated
		 */
		$return = apply_filters_ref_array( 'gravityview_entries', array( $return, $criteria, $passed_criteria, &$total ) );

		return $return;
	}


	/**
	 * Get the entry ID from a string that may be the Entry ID or the Entry Slug
	 *
	 * @since 1.18
	 *
	 * @param string $entry_id_or_slug The ID or slug of an entry.
	 * @param bool   $force_allow_ids Whether to force allowing getting the ID of an entry, even if custom slugs are enabled
	 *
	 * @return false|int|null Returns the ID of the entry found, if custom slugs is enabled. Returns original value if custom slugs is disabled. Returns false if not allowed to convert slug to ID. Returns NULL if entry not found for the passed slug.
	 */
	public static function get_entry_id( $entry_id_or_slug = '', $force_allow_ids = false ) {

		$entry_id = false;

		/**
		 * Whether to enable and use custom entry slugs.
		 *
		 * @param boolean True: Allow for slugs based on entry values. False: always use entry IDs (default)
		 */
		$custom_slug = apply_filters( 'gravityview_custom_entry_slug', false );

		/**
		 * When using a custom slug, allow access to the entry using the original slug (the Entry ID).
		 * - If disabled (default), only allow access to an entry using the custom slug value.  (example: `/entry/custom-slug/` NOT `/entry/123/`)
		 * - If enabled, you could access using the custom slug OR the entry id (example: `/entry/custom-slug/` OR `/entry/123/`)
		 *
		 * @param boolean $custom_slug_id_access True: allow accessing the slug by ID; False: only use the slug passed to the method.
		 */
		$custom_slug_id_access = $force_allow_ids || apply_filters( 'gravityview_custom_entry_slug_allow_id', false );

		/**
		 * If we're using custom entry slugs, we do a meta value search
		 * instead of doing a straight-up ID search.
		 */
		if ( $custom_slug ) {
			// Search for IDs matching $entry_id_or_slug
			$entry_id = self::get_entry_id_from_slug( $entry_id_or_slug );
		}

		// The custom slug search found something; return early.
		if ( $entry_id ) {
			return $entry_id;
		}

		// If custom slug is off, search using the entry ID
		// If allow ID access is on, also use entry ID as a backup
		if ( false === $custom_slug || true === $custom_slug_id_access ) {
			$entry_id = $entry_id_or_slug;
		}

		return $entry_id;
	}

	/**
	 * Return a single entry object
	 *
	 * Since 1.4, supports custom entry slugs. The way that GravityView fetches an entry based on the custom slug is by searching `gravityview_unique_id` meta. The `$entry_slug` is fetched by getting the current query var set by `is_single_entry()`
	 *
	 * @param string|int    $entry_slug Either entry ID or entry slug string
	 * @param boolean       $force_allow_ids Force the get_entry() method to allow passed entry IDs, even if the `gravityview_custom_entry_slug_allow_id` filter returns false.
	 * @param boolean       $check_entry_display Check whether the entry is visible for the current View configuration. Default: true. {@since 1.14}
	 * @param \GV\View|null $view The View if $check_entry_display is set to true. In legacy context mocks, can be null. {@since develop}
	 * @return array|boolean
	 */
	public static function get_entry( $entry_slug, $force_allow_ids = false, $check_entry_display = true, $view = null ) {

		if ( ! class_exists( 'GFAPI' ) || empty( $entry_slug ) ) {
			return false;
		}

		$entry_id = self::get_entry_id( $entry_slug, $force_allow_ids );

		if ( empty( $entry_id ) ) {
			return false;
		}

		// fetch the entry
		$entry = GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			gravityview()->log->error( '{error}', array( 'error' => $entry->get_error_message() ) );
			return false;
		}

		/**
		 * Override whether to check entry display rules against filters.
		 *
		 * @since 1.16.2
		 * @since 2.6 Added $view parameter
		 * @param bool $check_entry_display Check whether the entry is visible for the current View configuration. Default: true.
		 * @param array $entry Gravity Forms entry array
		 * @param \GV\View|null $view The View
		 */
		$check_entry_display = apply_filters( 'gravityview/common/get_entry/check_entry_display', $check_entry_display, $entry, $view );

		// Is the entry allowed
		if ( $check_entry_display ) {

			$gvid = \GV\Utils::_GET( 'gvid' );

			if ( $gvid ) {
				$view = \GV\View::by_id( $gvid );
			}

			$entry = self::check_entry_display( $entry, $view );
		}

		if ( is_wp_error( $entry ) ) {
			gravityview()->log->error( '{error}', array( 'error' => $entry->get_error_message() ) );

			return false;
		}

		return $entry;
	}

	/**
	 * Wrapper for the GFFormsModel::matches_conditional_operation() method that adds additional comparisons, including:
	 * 'equals', 'greater_than_or_is', 'greater_than_or_equals', 'less_than_or_is', 'less_than_or_equals',
	 * 'not_contains', 'in', and 'not_in'
	 *
	 * @see https://docs.gravitykit.com/article/252-gvlogic-shortcode
	 *
	 * @uses GFFormsModel::matches_operation
	 *
	 * @since 1.7.5
	 * @since 1.13 You can define context, which displays/hides based on what's being displayed (single, multiple, edit)
	 * @since 1.22.1 Added 'in' and 'not_in' for JSON-encoded array values, serialized non-strings
	 * @since 2.33 Uses GFFormsModel::matches_operation or GFFormsModel::matches_conditional_operation depending on the Gravity Forms version.
	 *
	 * @param mixed  $val1 Left side of comparison
	 * @param mixed  $val2 Right side of comparison
	 * @param string $operation Type of comparison
	 *
	 * @return bool True: matches, false: not matches
	 */
	public static function matches_operation( $val1, $val2, $operation ) {
		// Only process strings
		$val1 = ! is_string( $val1 ) ? wp_json_encode( $val1 ) : $val1;
		$val2 = ! is_string( $val2 ) ? wp_json_encode( $val2 ) : $val2;

		$value = false;

		if ( 'context' === $val1 ) {
			$matching_contexts = array( $val2 );

			// We allow for non-standard contexts.
			switch ( $val2 ) {
				// Check for either single or edit
				case 'singular':
					$matching_contexts = array( 'single', 'edit' );
					break;
				// Use multiple as alias for directory for consistency
				case 'multiple':
					$matching_contexts = array( 'directory' );
					break;
			}

			$val1 = in_array( gravityview_get_context(), $matching_contexts, true ) ? $val2 : false;
		}

		// Attempt to parse dates.
		$timestamp_1 = gravityview_maybe_convert_date_string_to_timestamp( $val1 );
		$timestamp_2 = gravityview_maybe_convert_date_string_to_timestamp( $val2 );

		// If both are timestamps, cast to string so we can use the > and < comparisons below.
		if ( $timestamp_1 && $timestamp_2 ) {
			$val1 = (string) $timestamp_1;
			$val2 = (string) $timestamp_2;
		}

		$gf_comparison_method = method_exists( GFFormsModel::class, 'matches_conditional_operation' )
			? 'matches_conditional_operation'
			: 'matches_operation';

		switch ( $operation ) {
			case 'equals':
				$value = self::matches_operation( $val1, $val2, 'is' );
				break;
			case 'greater_than_or_is':
			case 'greater_than_or_equals':
				$is    = self::matches_operation( $val1, $val2, 'is' );
				$gt    = self::matches_operation( $val1, $val2, 'greater_than' );
				$value = ( $is || $gt );
				break;
			case 'less_than_or_is':
			case 'less_than_or_equals':
				$is    = self::matches_operation( $val1, $val2, 'is' );
				$gt    = self::matches_operation( $val1, $val2, 'less_than' );
				$value = ( $is || $gt );
				break;
			case 'not_contains':
				$contains = self::matches_operation( $val1, $val2, 'contains' );
				$value    = ! $contains;
				break;
			/**
			 * @since 1.22.1 Handle JSON-encoded comparisons
			 */
			case 'in':
			case 'not_in':
				$json_val_1 = json_decode( $val1, true );
				$json_val_2 = json_decode( $val2, true );

				if ( ! empty( $json_val_1 ) || ! empty( $json_val_2 ) ) {

					$json_in    = false;
					$json_val_1 = $json_val_1 ? (array) $json_val_1 : array( $val1 );
					$json_val_2 = $json_val_2 ? (array) $json_val_2 : array( $val2 );

					// For JSON, we want to compare as "in" or "not in" rather than "contains"
					foreach ( $json_val_1 as $item_1 ) {
						foreach ( $json_val_2 as $item_2 ) {
							$json_in = self::matches_operation( $item_1, $item_2, 'is' );

							if ( $json_in ) {
								break 2;
							}
						}
					}

					$value = ( 'in' === $operation ) ? $json_in : ! $json_in;
				}
				break;
			case 'less_than':
			case '<':
				if ( is_string( $val1 ) && is_string( $val2 ) ) {
					$value = $val1 < $val2;
				} else {
					$value = GFFormsModel::$gf_comparison_method( $val1, $val2, $operation );
				}
				break;
			case 'greater_than':
			case '>':
				if ( is_string( $val1 ) && is_string( $val2 ) ) {
					$value = $val1 > $val2;
				} else {
					$value = GFFormsModel::$gf_comparison_method( $val1, $val2, $operation );
				}
				break;
			default:
				$value = GFFormsModel::$gf_comparison_method( $val1, $val2, $operation );
		}

		return $value;
	}

	/**
	 *
	 * Checks if a certain entry is valid according to the View search filters (specially the Adv Filters)
	 *
	 * @uses GVCommon::calculate_get_entries_criteria();
	 * @see GFFormsModel::is_value_match()
	 *
	 * @since 1.7.4
	 * @since 2.1 Added $view parameter
	 *
	 * @param array                        $entry Gravity Forms Entry object
	 * @param \GV\View|\GV\View_Collection $view The View or a View Collection
	 *
	 * @return WP_Error|array Returns WP_Error if entry is not valid according to the view search filters (Adv Filter). Returns original $entry value if passes.
	 */
	public static function check_entry_display( $entry, $view = null ) {

		// Check whether Embed Only is enabled. If we're on a CPT, the entry is not allowed to be displayed.
		if ( gravityview()->request->is_view() && $view && $view->settings->get( 'embed_only' ) ) {
			return new WP_Error( 'gravityview/embed_only' );
		}

		if ( ! $entry || is_wp_error( $entry ) ) {
			return new WP_Error( 'entry_not_found', 'Entry was not found.', $entry );
		}

		if ( empty( $entry['form_id'] ) ) {
			return new WP_Error( 'form_id_not_set', '[check_entry_display] Form ID is not set for the entry.', $entry );
		}

		if ( is_null( $view ) ) {
			gravityview()->log->warning( '$view was not supplied to check_entry_display, results will be non-typical.' );
			return new WP_Error( 'view_not_supplied', 'View is not supplied!', $entry );
		}

		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
			return new WP_Error( 'no_gf_query', 'GF_Query is missing.', $entry );
		}

		$_gvid = \GV\Utils::_GET( 'gvid' );

		if ( $_gvid && $view->ID !== (int) $_gvid ) {
			return new WP_Error( 'view_id_not_match_gvid', 'View does not match passed $_GET["gvid"].', $view->ID );
		}

		$view_form_id = $view->form->ID;

		if ( $view->joins ) {
			if ( in_array( (int) $entry['form_id'], array_keys( $view::get_joined_forms( $view->ID ) ), true ) ) {
				$view_form_id = $entry['form_id'];
			}
		}

		if ( (int) $view_form_id !== (int) $entry['form_id'] ) {
			return new WP_Error( 'view_id_not_match', 'View form source does not match entry form source ID.', $entry );
		}

		/**
		 * Check whether the entry is in the entries subset by running a modified query.
		 */
		add_action(
			'gravityview/view/query',
			$entry_subset_callback = function ( &$query, $view, $request ) use ( $entry, $view_form_id ) {
				$_tmp_query = new \GF_Query(
					$view_form_id,
					array(
						'field_filters' => array(
							'mode' => 'all',
							array(
								'key'       => 'id',
								'operation' => 'is',
								'value'     => $entry['id'],
							),
						),
					)
				);

				$_tmp_query_parts = $_tmp_query->_introspect();

				/** @type \GF_Query $query */
				$query_parts = $query->_introspect();

				$query->where( \GF_Query_Condition::_and( $_tmp_query_parts['where'], $query_parts['where'] ) );
			},
			10,
			3
		);

		// Prevent page offset from being applied to the single entry query; it's used to return to the referring page number
		add_filter(
			'gravityview_search_criteria',
			$remove_pagenum = function ( $criteria ) {

				$criteria['paging'] = array(
					'offset'    => 0,
					'page_size' => 25,
				);

				return $criteria;
			}
		);

		$entries = $view->get_entries()->all();

		// Remove the modifying filter
		remove_filter( 'gravityview_search_criteria', $remove_pagenum );

		if ( ! $entries ) {
			remove_action( 'gravityview/view/query', $entry_subset_callback );
			return new \WP_Error( 'failed_criteria', 'Entry failed search_criteria and field_filters' );
		}

		// This entry is on a View with joins
		if ( $entries[0]->is_multi() ) {

			$multi_entry_ids = array();

			foreach ( $entries[0]->entries as $multi_entry ) {
				$multi_entry_ids[] = (int) $multi_entry->ID;
			}

			if ( ! in_array( (int) $entry['id'], $multi_entry_ids, true ) ) {
				remove_action( 'gravityview/view/query', $entry_subset_callback );
				return new \WP_Error( 'failed_criteria', 'Entry failed search_criteria and field_filters' );
			}
		} elseif ( (int) $entries[0]->ID !== (int) $entry['id'] ) {
			remove_action( 'gravityview/view/query', $entry_subset_callback );
			return new \WP_Error( 'failed_criteria', 'Entry failed search_criteria and field_filters' );
		}

		remove_action( 'gravityview/view/query', $entry_subset_callback );
		return $entry;
	}

	/**
	 * Formats date without applying site's timezone. This is a copy of {@see GFCommon::format_date()}.
	 *
	 * @since 2.33
	 *
	 * @param string $gmt_datetime The UTC date/time value to be formatted.
	 * @param bool   $is_human     Indicates if a human-readable time difference such as "1 hour ago" should be returned when within 24hrs of the current time. Defaults to true.
	 * @param string $date_format  The format the value should be returned in. Defaults to an empty string; the date format from the WordPress general settings, if configured, or Y-m-d.
	 * @param bool   $include_time Indicates if the time should be included in the returned string. Defaults to true; the time format from the WordPress general settings, if configured, or H:i.
	 *
	 * @return string
	 *
	 */
	public static function format_date_without_timezone_offset( $gmt_datetime, $is_human = true, $date_format = '', $include_time = true ) {
		if ( empty( $gmt_datetime ) ) {
			return '';
		}

		$gmt_time = mysql2date( 'G', $gmt_datetime );

		if ( $is_human ) {
			$time_diff = time() - $gmt_time;

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				return sprintf( esc_html__( '%s ago', 'gk-gravityview' ), human_time_diff( $gmt_time ) );
			}
		}

		if ( empty( $date_format ) ) {
			$date_format = GFCommon::get_default_date_format();
		}

		if ( $include_time ) {
			$time_format = GFCommon::get_default_time_format();

			return sprintf( esc_html__( '%1$s at %2$s', 'gk-gravityview' ), date_i18n( $date_format, $gmt_time, true ), date_i18n( $time_format, $gmt_time, true ) );
		}

		return date_i18n( $date_format, $gmt_time, true );
	}

	/**
	 * Allow formatting date and time based on GravityView standards
	 *
	 * @since 1.16
	 *
	 * @see GVCommon_Test::test_format_date for examples
	 *
	 * @param string       $date_string The date as stored by Gravity Forms (`Y-m-d h:i:s` GMT)
	 * @param string|array $args Array or string of settings for output parsed by `wp_parse_args()`; Can use `raw=1` or `array('raw' => true)` \n
	 * - `raw` Un-formatted date string in original `Y-m-d h:i:s` format
	 * - `timestamp` Integer timestamp returned by GFCommon::get_local_timestamp()
	 * - `diff` "%s ago" format, unless other `format` is defined
	 * - `human` Set $is_human parameter to true for `GFCommon::format_date()`. Shows `diff` within 24 hours or date after. Format based on blog setting, unless `format` is defined.
	 * - `time` Include time in the `GFCommon::format_date()` output
	 * - `format` Define your own date format, or `diff` format
	 *
	 * @return int|null|string Formatted date based on the original date
	 */
	public static function format_date( $date_string = '', $args = array() ) {
		$default_atts = [
			'raw'          => false,
			'timestamp'    => false,
			'diff'         => false,
			'human'        => false,
			'format'       => '',
			'time'         => false,
			'no_tz_offset' => false,
		];

		$atts = wp_parse_args( $args, $default_atts );

		/**
		 * Gravity Forms code to adjust date to locally-configured timezone.
		 *
		 * @see GFCommon::format_date() for original code
		 */
		$date_gmt_time        = mysql2date( 'G', $date_string );
		$date_local_timestamp = GFCommon::get_local_timestamp( $date_gmt_time );

		$format       	= \GV\Utils::get( $atts, 'format' );
		$is_human    	= ! empty( $atts['human'] );
		$is_diff     	= ! empty( $atts['diff'] );
		$is_raw      	= ! empty( $atts['raw'] );
		$is_timestamp 	= ! empty( $atts['timestamp'] );
		$include_time 	= ! empty( $atts['time'] );
		$no_tz_offset 	= ! empty( $atts['no_tz_offset'] );
		$time_diff    	= strtotime( $date_string ) - current_time( 'timestamp' );

		// If we're using time diff, we want to have a different default format
		if ( empty( $format ) ) {
			/* translators: %s: relative time from now, used for generic date comparisons. "1 day ago", or "20 seconds ago" */
			$is_past    = ( $time_diff < 0 );
			$human_diff = $is_past ? esc_html__( '%s ago', 'gk-gravityview' ) : esc_html__( '%s from now', 'gk-gravityview' );
			$format     = $is_diff ? $human_diff : get_option( 'date_format' );
		}

		// If raw was specified, don't modify the stored value
		if ( $is_raw ) {
			$formatted_date = $date_string;
		} elseif ( $is_timestamp ) {
			$formatted_date = $date_local_timestamp;
		} elseif ( $is_diff ) {
			$formatted_date = sprintf( $format, human_time_diff( $date_gmt_time, current_time( 'timestamp' ) ) );
		} elseif ( $no_tz_offset ) {
			$formatted_date = self::format_date_without_timezone_offset( $date_string, $is_human, $format, $include_time );
		} else {
			$formatted_date = GFCommon::format_date( $date_string, $is_human, $format, $include_time );
		}

		unset( $format, $is_diff, $is_human, $is_timestamp, $no_tz_offset, $is_raw, $date_gmt_time, $date_local_timestamp, $default_atts );

		return $formatted_date;
	}

	/**
	 * Retrieve the label of a given field id (for a specific form)
	 *
	 * @since 1.17 Added $field_value parameter
	 *
	 * @param array        $form Gravity Forms form array
	 * @param string       $field_id ID of the field. If an input, full input ID (like `1.3`)
	 * @param string|array $field_value Raw value of the field.
	 * @return string
	 */
	public static function get_field_label( $form = array(), $field_id = '', $field_value = '' ) {

		if ( empty( $form ) || empty( $field_id ) ) {
			return '';
		}

		$field = self::get_field( $form, $field_id );

		$label = \GV\Utils::get( $field, 'label' );

		if ( floor( $field_id ) !== floatval( $field_id ) ) {
			$label = GFFormsModel::get_choice_text( $field, $field_value, $field_id );
		}

		return $label;
	}


	/**
	 * Returns the field details array of a specific form given the field id
	 *
	 * Alias of GFFormsModel::get_field
	 *
	 * @since 1.19 Allow passing form ID as well as form array
	 *
	 * @uses GFFormsModel::get_field
	 * @see GFFormsModel::get_field
	 * @param array|int  $form Form array or ID
	 * @param string|int $field_id
	 * @return GF_Field|null Gravity Forms field object, or NULL: Gravity Forms GFFormsModel does not exist or field at $field_id doesn't exist.
	 */
	public static function get_field( $form, $field_id ) {
		$field = GFAPI::get_field( $form, $field_id );

		// Maintain previous behavior by returning null instead of false.
		return $field ? $field : null;
	}


	/**
	 * Check whether the post is GravityView
	 *
	 * - Check post type. Is it `gravityview`?
	 * - Check shortcode
	 *
	 * @param  WP_Post $post WordPress post object
	 * @return boolean           True: yep, GravityView; No: not!
	 */
	public static function has_gravityview_shortcode( $post = null ) {
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return false;
		}

		if ( 'gravityview' === get_post_type( $post ) ) {
			return true;
		}

		return self::has_shortcode_r( $post->post_content, 'gravityview' ) ? true : false;
	}


	/**
	 * Placeholder until the recursive has_shortcode() patch is merged
	 *
	 * @see https://core.trac.wordpress.org/ticket/26343#comment:10
	 * @param string $content Content to check whether there's a shortcode
	 * @param string $tag Current shortcode tag
	 */
	public static function has_shortcode_r( $content, $tag = 'gravityview' ) {
		if ( false === strpos( $content, '[' ) ) {
			return false;
		}

		if ( shortcode_exists( $tag ) ) {

			$shortcodes = array();

			preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );
			if ( empty( $matches ) ) {
				return false;
			}

			foreach ( $matches as $shortcode ) {
				if ( $tag === $shortcode[2] ) {

					// Changed this to $shortcode instead of true so we get the parsed atts.
					$shortcodes[] = $shortcode;

				} elseif ( isset( $shortcode[5] ) && $results = self::has_shortcode_r( $shortcode[5], $tag ) ) {
					foreach ( $results as $result ) {
						$shortcodes[] = $result;
					}
				}
			}

			return $shortcodes;
		}
		return false;
	}



	/**
	 * Get the views for a particular form
	 *
	 * @since 1.15.2 Add $args array and limit posts_per_page to 500
	 * @since 2.19   Added $include_joins param
	 *
	 * @uses get_posts()
	 *
	 * @param  int   $form_id Gravity Forms form ID
	 * @param  array $args Pass args sent to get_posts()
	 * @param  bool  $include_joins Whether to include forms that are joined to the View
	 *
	 * @return array          Array with view details, as returned by get_posts()
	 */
	public static function get_connected_views( $form_id, $args = array(), $include_joins = true ) {

		global $wpdb;

		$defaults = array(
			'post_type'      => 'gravityview',
			'posts_per_page' => 100,
			'meta_key'       => '_gravityview_form_id',
			'meta_value'     => (int) $form_id,
		);
		$args     = wp_parse_args( $args, $defaults );
		$views    = get_posts( $args );

		if ( ! $include_joins ) {
			return $views;
		}

		$views_with_joins = $wpdb->get_results( "SELECT `post_id`, `meta_value` FROM $wpdb->postmeta WHERE `meta_key` = '_gravityview_form_joins'" );

		$joined_forms = array();
		foreach ( $views_with_joins as $view ) {

			$data = unserialize( $view->meta_value );

			if ( ! $data || ! is_array( $data ) ) {
				continue;
			}

			foreach ( $data as $datum ) {
				if ( ! empty( $datum[2] ) && (int) $datum[2] === (int) $form_id ) {
					$joined_forms[] = $view->post_id;
				}
			}
		}

		if ( ! $joined_forms ) {
			return $views;
		}

		$joined_args = array(
			'post_type'      => 'gravityview',
			'posts_per_page' => $args['posts_per_page'],
			'post__in'       => $joined_forms,
		);

		$views = array_merge( $views, get_posts( $joined_args ) );

		return $views;
	}

	/**
	 * Get the Gravity Forms form ID connected to a View
	 *
	 * @param int $view_id The ID of the View to get the connected form of
	 *
	 * @return false|string ID of the connected Form, if exists. Empty string if not. False if not the View ID isn't valid.
	 */
	public static function get_meta_form_id( $view_id ) {
		return get_post_meta( $view_id, '_gravityview_form_id', true );
	}

	/**
	 * Get the template ID (`list`, `table`, `datatables`, `map`) for a View
	 *
	 * @see GravityView_Template::template_id
	 *
	 * @param int    $view_id The ID of the View to get the layout of.
	 * @param string $section The view section.
	 *
	 * @return string GravityView_Template::template_id value. Empty string if not.
	 */
	public static function get_meta_template_id( $view_id, string $section = 'directory' ) {
		$section_key = [
			'directory' => '_gravityview_directory_template',
			'single'    => '_gravityview_single_template',
		];

		if ( ! isset( $section_key[ $section ] ) ) {
			gravityview()->log->error(
				'{section} Not a valid section:',
				compact( 'view_id', 'section' )
			);

			return '';
		}

		$result = get_post_meta( $view_id, $section_key[ $section ], true );

		// Fall back to the template of `directory` in case a different section has no value.
		if ( ! $result && 'directory' !== $section ) {
			return self::get_meta_template_id( $view_id, 'directory' );
		}

		return $result;
	}


	/**
	 * Get all the settings for a View
	 *
	 * @uses  \GV\View_Settings::defaults() Parses the settings with the plugin defaults as backups.
	 * @param  int $post_id View ID
	 * @return array          Associative array of settings with plugin defaults used if not set by the View
	 */
	public static function get_template_settings( $post_id ) {

		$settings = get_post_meta( $post_id, '_gravityview_template_settings', true );
		// Enable secured views by default on new views.
		if ( ! $settings ) {
			$settings = [ 'is_secure' => 1 ];
		}

		if ( class_exists( '\GV\View_Settings' ) ) {

			return wp_parse_args( (array) $settings, \GV\View_Settings::defaults() );

		}

		// Backup, in case GravityView_View_Data isn't loaded yet.
		return $settings;
	}

	/**
	 * Get the setting for a View
	 *
	 * If the setting isn't set by the View, it returns the plugin default.
	 *
	 * @param  int    $post_id View ID
	 * @param  string $key     Key for the setting
	 * @return mixed|null          Setting value, or NULL if not set.
	 */
	public static function get_template_setting( $post_id, $key ) {

		$settings = self::get_template_settings( $post_id );

		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}

		return null;
	}

	/**
	 * Get the field configuration for the View
	 *
	 * array(
	 *
	 *  [other zones]
	 *
	 *  'directory_list-title' => array(
	 *
	 *      [other fields]
	 *
	 *      '5372653f25d44' => array(
	 *          'id' => string '9' (length=1)
	 *          'label' => string 'Screenshots' (length=11)
	 *          'show_label' => string '1' (length=1)
	 *          'custom_label' => string '' (length=0)
	 *          'custom_class' => string 'gv-gallery' (length=10)
	 *          'only_loggedin' => string '0' (length=1)
	 *          'only_loggedin_cap' => string 'read' (length=4)
	 *      )
	 *
	 *      [other fields]
	 *  )
	 *
	 *  [other zones]
	 * )
	 *
	 * @since 1.17.4 Added $apply_filter parameter.
	 * @since 2.17   Added $form_id parameter.
	 *
	 * @param  int  $post_id View ID.
	 * @param  bool $apply_filter Whether to apply the `gravityview/configuration/fields` filter [Default: true]
	 * @return array Multi-array of fields with first level being the field zones. See code comment.
	 */
	public static function get_directory_fields( $post_id, $apply_filter = true, $form_id = 0 ) {
		$fields = get_post_meta( $post_id, '_gravityview_directory_fields', true );

		if ( $apply_filter ) {
			/**
			 * Filter the View fields' configuration array.
			 *
			 * @since 1.6.5
			 * @since 2.16.3 Added the $form_id parameter.
			 *
			 * @param $fields array Multi-array of fields with first level being the field zones
			 * @param $post_id int Post ID
			 * @param int $form_id The main form ID for the View.
			 */
			$fields = apply_filters( 'gravityview/configuration/fields', $fields, $post_id, $form_id );

			/**
			 * Filter the View fields' configuration array.
			 *
			 * @since 2.0
			 * @since 2.16.3 Added the $form_id parameter.
			 *
			 * @param array $fields Multi-array of fields with first level being the field zones.
			 * @param \GV\View $view The View the fields are being pulled for.
			 * @param int $form_id The main form ID for the View.
			 */
			$fields = apply_filters( 'gravityview/view/configuration/fields', $fields, \GV\View::by_id( $post_id ), $form_id );
		}

		return $fields;
	}

	/**
	 * Get the widget configuration for a View
	 *
	 * @param int  $view_id View ID
	 * @param bool $json_decode Whether to JSON-decode the widget values. Default: `false`
	 *
	 * @return array Multi-array of widgets, with the slug of each widget "zone" being the key ("header_top"), and each widget having their own "id"
	 */
	public static function get_directory_widgets( $view_id, $json_decode = false ) {

		$view_widgets = get_post_meta( $view_id, '_gravityview_directory_widgets', true );

		$defaults = array(
			'header_top'   => array(),
			'header_left'  => array(),
			'header_right' => array(),
			'footer_left'  => array(),
			'footer_right' => array(),
		);

		$directory_widgets = wp_parse_args( $view_widgets, $defaults );

		if ( $json_decode ) {
			$directory_widgets = gv_map_deep( $directory_widgets, 'gv_maybe_json_decode' );
		}

		return $directory_widgets;
	}


	/**
	 * Render dropdown (select) with the list of sortable fields from a form ID
	 *
	 * @param int    $formid  Form ID
	 * @param string $current Current selected field ID
	 *
	 * @return string         html
	 */
	public static function get_sortable_fields( $formid, $current = 'id' ) {
		$output = '<option value="id" ' . selected( '', $current, false ) . '>' . esc_html__( 'Default (Entry ID)', 'gk-gravityview' ) . '</option>';

		if ( empty( $formid ) ) {
			return $output;
		}

		$fields = self::get_sortable_fields_array( $formid );

		if ( ! empty( $fields ) ) {

			$blocklist_field_types = array( 'list', 'textarea' );

			$blocklist_field_types = apply_filters_deprecated( 'gravityview_blacklist_field_types', array( $blocklist_field_types, null ), '2.14', 'gravityview_blocklist_field_types' );

			$blocklist_field_types = apply_filters( 'gravityview_blocklist_field_types', $blocklist_field_types, null );

			foreach ( $fields as $id => $field ) {
				if ( in_array( $field['type'], $blocklist_field_types ) ) {
					continue;
				}

				$output .= '<option value="' . $id . '" ' . selected( $id, $current, false ) . '>' . esc_attr( $field['label'] ) . '</option>';
			}
		}

		return $output;
	}

	/**
	 *
	 * @param int   $formid Gravity Forms form ID
	 * @param array $blocklist Field types to exclude
	 *
	 * @since 1.8
	 *
	 * @todo Get all fields, check if sortable dynamically
	 *
	 * @return array
	 */
	public static function get_sortable_fields_array( $formid, $blocklist = array( 'list', 'textarea' ) ) {

		// Get fields with sub-inputs and no parent
		$fields = self::get_form_fields( $formid, true, false );

		$date_created = array(
			'date_created' => array(
				'type'  => 'date_created',
				'label' => __( 'Date Created', 'gk-gravityview' ),
			),
			'date_updated' => array(
				'type'  => 'date_updated',
				'label' => __( 'Date Updated', 'gk-gravityview' ),
			),
		);

		$fields = $date_created + $fields;

		$blocklist_field_types = $blocklist;

		$blocklist_field_types = apply_filters_deprecated( 'gravityview_blacklist_field_types', array( $blocklist_field_types, null ), '2.14', 'gravityview_blocklist_field_types' );

		$blocklist_field_types = apply_filters( 'gravityview_blocklist_field_types', $blocklist_field_types, null );

		// TODO: Convert to using array_filter
		foreach ( $fields as $id => $field ) {

			if ( in_array( $field['type'], $blocklist_field_types ) ) {
				unset( $fields[ $id ] );
			}

			/**
			 * Merge date and time subfields.
			 */
			if ( in_array( $field['type'], array( 'date', 'time' ) ) && ! empty( $field['parent'] ) ) {
				$fields[ intval( $id ) ] = array(
					'label'      => \GV\Utils::get( $field, 'parent/label' ),
					'parent'     => null,
					'type'       => \GV\Utils::get( $field, 'parent/type' ),
					'adminLabel' => \GV\Utils::get( $field, 'parent/adminLabel' ),
					'adminOnly'  => \GV\Utils::get( $field, 'parent/adminOnly' ),
				);

				unset( $fields[ $id ] );
			}
		}

		/**
		 * Filter the sortable fields.
		 *
		 * @since 1.12
		 * @param array $fields Sub-set of GF form fields that are sortable
		 * @param int $formid The Gravity Forms form ID that the fields are from
		 */
		$fields = apply_filters( 'gravityview/common/sortable_fields', $fields, $formid );

		return $fields;
	}

	/**
	 * Returns the GF Form field type for a certain field(id) of a form
	 *
	 * @param  object $form     Gravity Forms form
	 * @param  mixed  $field_id Field ID or Field array
	 * @return string field type
	 */
	public static function get_field_type( $form = null, $field_id = '' ) {

		if ( ! empty( $field_id ) && ! is_array( $field_id ) ) {
			$field = self::get_field( $form, $field_id );
		} else {
			$field = $field_id;
		}

		return class_exists( 'RGFormsModel' ) ? RGFormsModel::get_input_type( $field ) : '';
	}


	/**
	 * Checks if the field type is a 'numeric' field type (e.g. to be used when sorting)
	 *
	 * @param  int|array $form  form ID or form array
	 * @param  int|array $field field key or field array
	 * @return boolean
	 */
	public static function is_field_numeric( $form = null, $field = '' ) {

		if ( ! is_array( $form ) && ! is_array( $field ) ) {
			$form = self::get_form( $form );
		}

		// If entry meta, it's a string. Otherwise, numeric
		if ( ! is_numeric( $field ) && is_string( $field ) ) {
			$type = $field;
		} else {
			$type = self::get_field_type( $form, $field );
		}

		/**
		 * What types of fields are numeric?
		 *
		 * @since 1.5.2
		 * @param array $numeric_types Fields that are numeric. Default: `[ number, time ]`
		 */
		$numeric_types = apply_filters( 'gravityview/common/numeric_types', array( 'number', 'time' ) );

		// Defer to GravityView_Field setting, if the field type is registered and `is_numeric` is true
		if ( $gv_field = GravityView_Fields::get( $type ) ) {
			if ( true === $gv_field->is_numeric ) {
				$numeric_types[] = $gv_field->is_numeric;
			}
		}

		$return = in_array( $type, $numeric_types );

		return $return;
	}

	/**
	 * Encrypt content using Javascript so that it's hidden when JS is disabled.
	 *
	 * This is mostly used to hide email addresses from scraper bots.
	 *
	 * @param string $content Content to encrypt
	 * @param string $message Message shown if Javascript is disabled
	 *
	 * @see  https://github.com/katzwebservices/standalone-phpenkoder StandalonePHPEnkoder on Github
	 *
	 * @since 1.7
	 *
	 * @return string Content, encrypted
	 */
	public static function js_encrypt( $content, $message = '' ) {

		$output = $content;

		if ( ! class_exists( 'StandalonePHPEnkoder' ) ) {
			include_once GRAVITYVIEW_DIR . 'includes/lib/StandalonePHPEnkoder.php';
		}

		if ( class_exists( 'StandalonePHPEnkoder' ) ) {

			$enkoder = new StandalonePHPEnkoder();

			$message = empty( $message ) ? __( 'Email hidden; Javascript is required.', 'gk-gravityview' ) : $message;

			/**
			 * Modify the message shown when Javascript is disabled and an encrypted email field is displayed.
			 *
			 * @since 1.7
			 * @param string $message Existing message
			 * @param string $content Content to encrypt
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
	 *
	 * @param $string string string to parse (not altered like in the original parse_str(), use the second parameter!)
	 * @param $result array  If the second parameter is present, variables are stored in this variable as array elements
	 * @return bool true or false if $string is an empty string
	 * @since  1.5.3
	 *
	 * @see https://gist.github.com/rubo77/6821632
	 **/
	public static function gv_parse_str( $string, &$result ) {
		if ( empty( $string ) ) {
			return false;
		}

		$result = array();

		// find the pairs "name=value"
		$pairs = explode( '&', $string );

		foreach ( $pairs as $pair ) {
			// use the original parse_str() on each element
			parse_str( $pair, $params );

			$k = key( $params );
			if ( ! isset( $result[ $k ] ) ) {
				$result += $params;
			} elseif ( array_key_exists( $k, $params ) && is_array( $params[ $k ] ) ) {
				$result[ $k ] = self::array_merge_recursive_distinct( $result[ $k ], $params[ $k ] );
			}
		}
		return true;
	}


	/**
	 * Generate an HTML anchor tag with a list of supported attributes
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a Supported attributes defined here
	 * @uses esc_url_raw() to sanitize $href
	 * @uses esc_attr() to sanitize $atts
	 *
	 * @since 1.6
	 *
	 * @param string       $href URL of the link. Sanitized using `esc_url_raw()`
	 * @param string       $anchor_text The text or HTML inside the anchor. This is not sanitized in the function.
	 * @param array|string $atts Attributes to be added to the anchor tag. Parsed by `wp_parse_args()`, sanitized using `esc_attr()`
	 *
	 * @return string HTML output of anchor link. If empty $href, returns NULL
	 */
	public static function get_link_html( $href = '', $anchor_text = '', $atts = array() ) {

		// Supported attributes for anchor tags. HREF left out intentionally.
		$allowed_atts = array(
			'href'        => null, // Will override the $href argument if set
			'title'       => null,
			'rel'         => null,
			'id'          => null,
			'class'       => null,
			'target'      => null,
			'style'       => null,

			// Used by GravityView
			'data-viewid' => null,

			// Not standard
			'hreflang'    => null,
			'type'        => null,
			'tabindex'    => null,

			// Deprecated HTML4 but still used
			'name'        => null,
			'onclick'     => null,
			'onchange'    => null,
			'onkeyup'     => null,

			// HTML5 only
			'download'    => null,
			'media'       => null,
			'ping'        => null,
		);

		/**
		 * Modify the attributes that are allowed to be used in generating links.
		 *
		 * @since 1.6
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
		if ( empty( $final_atts['href'] ) && ! empty( $href ) ) {
			$final_atts['href'] = $href;
		}

		if ( isset( $final_atts['href'] ) ) {
			$final_atts['href'] = esc_url_raw( $final_atts['href'] );
		}

		/**
		 * Fix potential security issue with target=_blank
		 *
		 * @see https://dev.to/ben/the-targetblank-vulnerability-by-example
		 */
		if ( '_blank' === \GV\Utils::get( $final_atts, 'target' ) ) {
			$final_atts['rel'] = trim( \GV\Utils::get( $final_atts, 'rel', '' ) . ' noopener noreferrer' );
		}

		// Sort the attributes alphabetically, to help testing
		ksort( $final_atts );

		// For each attribute, generate the code
		$output = '';
		foreach ( $final_atts as $attr => $value ) {
			$output .= sprintf( ' %s="%s"', $attr, esc_attr( $value ) );
		}

		if ( '' !== $output ) {
			$output = '<a' . $output . '>' . $anchor_text . '</a>';
		}

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
	 * @author Daniel <daniel@danielsmedegaardbuus.dk>
	 * @author Gabriel Sobrinho <gabriel.sobrinho@gmail.com>
	 */
	public static function array_merge_recursive_distinct( array &$array1, array &$array2 ) {
		$merged = $array1;
		foreach ( $array2 as $key => $value ) {
			if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = self::array_merge_recursive_distinct( $merged[ $key ], $value );
			} elseif ( is_numeric( $key ) && isset( $merged[ $key ] ) ) {
				$merged[] = $value;
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

	/**
	 * Get WordPress users with reasonable limits set
	 *
	 * @param string $context Where are we using this information (e.g. change_entry_creator, search_widget ..)
	 * @param array  $args Arguments to modify the user query. See get_users() {@since 1.14}
	 * @return array Array of WP_User objects.
	 */
	public static function get_users( $context = 'change_entry_creator', $args = array() ) {

		$default_args = array(
			'number'  => 2000,
			'orderby' => 'display_name',
			'order'   => 'ASC',
			'fields'  => array( 'ID', 'display_name', 'user_login', 'user_nicename' ),
		);

		// Merge in the passed arg
		$get_users_settings = wp_parse_args( $args, $default_args );

		/**
		 * There are issues with too many users using [get_users()](http://codex.wordpress.org/Function_Reference/get_users) where it breaks the select. We try to keep it at a reasonable number. \n.
		 * `$context` is where are we using this information (e.g. change_entry_creator, search_widget ..)
		 *
		 * @param array $settings Settings array, with `number` key defining the # of users to display
		 */
		$get_users_settings = apply_filters( 'gravityview/get_users/' . $context, apply_filters( 'gravityview_change_entry_creator_user_parameters', $get_users_settings ) );

		return get_users( $get_users_settings );
	}


	/**
	 * Display updated/error notice
	 *
	 * @since 1.19.2 Added $cap and $object_id parameters
	 *
	 * @param string $notice text/HTML of notice
	 * @param string $class CSS class for notice (`updated` or `error`)
	 * @param string $cap [Optional] Define a capability required to show a notice. If not set, displays to all caps.
	 *
	 * @return string
	 */
	public static function generate_notice( $notice, $class = '', $cap = '', $object_id = null ) {

		// If $cap is defined, only show notice if user has capability
		if ( $cap && ! self::has_cap( $cap, $object_id ) ) {
			return '';
		}

		return '<div class="gv-notice ' . gravityview_sanitize_html_class( $class ) . '">' . $notice . '</div>';
	}

	/**
	 * Inspired on \GFCommon::encode_shortcodes, reverse the encoding by replacing the ascii characters by the shortcode brackets
	 *
	 * @since 1.16.5
	 * @param string $string Input string to decode
	 * @return string $string Output string
	 */
	public static function decode_shortcodes( $string ) {
		$replace = array( '[', ']', '"' );
		$find    = array( '&#91;', '&#93;', '&quot;' );
		$string  = str_replace( $find, $replace, $string );

		return $string;
	}


	/**
	 * Send email using GFCommon::send_email()
	 *
	 * @since 1.17
	 *
	 * @see GFCommon::send_email This just makes the method public
	 *
	 * @param string       $from               Sender address (required)
	 * @param string       $to                 Recipient address (required)
	 * @param string       $bcc                BCC recipients (required)
	 * @param string       $reply_to           Reply-to address (required)
	 * @param string       $subject            Subject line (required)
	 * @param string       $message            Message body (required)
	 * @param string       $from_name          Displayed name of the sender
	 * @param string       $message_format     If "html", sent text as `text/html`. Otherwise, `text/plain`. Default: "html".
	 * @param string|array $attachments  Optional. Files to attach. {@see wp_mail()} for usage. Default: "".
	 * @param array|false  $entry         Gravity Forms entry array, related to the email. Default: false.
	 * @param array|false  $notification  Gravity Forms notification that triggered the email. {@see GFCommon::send_notification}. Default:false.
	 */
	public static function send_email( $from, $to, $bcc, $reply_to, $subject, $message, $from_name = '', $message_format = 'html', $attachments = '', $entry = false, $notification = false ) {

		$SendEmail = new ReflectionMethod( 'GFCommon', 'send_email' );

		// It was private; let's make it public
		$SendEmail->setAccessible( true );

		// Required: $from, $to, $bcc, $replyTo, $subject, $message
		// Optional: $from_name, $message_format, $attachments, $lead, $notification
		$SendEmail->invoke( new GFCommon(), $from, $to, $bcc, $reply_to, $subject, $message, $from_name, $message_format, $attachments, $entry, $notification );
	}

	/**
	 * Get post ID from an entry, checking both standard Gravity Forms post fields and Advanced Post Creation add-on
	 *
	 * @since TODO
	 *
	 * @param array $entry Gravity Forms entry array
	 * @param int   $feed_id Currently unused. Reserved for specifying which APC feed to use.
	 *
	 * @return int|null Post ID if found, null if not.
	 */
	public static function get_post_id_from_entry( $entry, $feed_id = null ) {
		if ( empty( $entry ) || ! is_array( $entry ) ) {
			return null;
		}

		$post_id = (int) \GV\Utils::get( $entry, 'post_id', 0 );

		/**
		 * Filter the post ID retrieved from an entry.
		 *
		 * Used by Advanced Post Creation integration.
		 *
		 * @since TODO
		 *
		 * @param int|null $post_id The post ID found, or null if not found.
		 * @param array    $entry   The entry array.
		 * @param int|null $feed_id The feed ID requested.
		 */
		$post_id = apply_filters( 'gk/gravityview/common/get-post-id-from-entry', $post_id, $entry, $feed_id );

		return $post_id;
	}

	/**
	 * Check if the current request is a REST request.
	 *
	 * @since 2.41
	 *
	 * @return bool True if the request is a REST request, false otherwise.
	 */
	public static function is_rest_request() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}
}//end class

/**
 * Generate an HTML anchor tag with a list of supported attributes
 *
 * @see GVCommon::get_link_html()
 *
 * @since 1.6
 *
 * @param string       $href URL of the link.
 * @param string       $anchor_text The text or HTML inside the anchor. This is not sanitized in the function.
 * @param array|string $atts Attributes to be added to the anchor tag
 *
 * @return string HTML output of anchor link. If empty $href, returns NULL
 */
function gravityview_get_link( $href = '', $anchor_text = '', $atts = array() ) {
	return GVCommon::get_link_html( $href, $anchor_text, $atts );
}
