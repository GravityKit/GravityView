<?php
/**
 * @file class-gravityview-field-sequence.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add a sequence field.
 *
 * @since 2.3.3
 */
class GravityView_Field_Sequence extends GravityView_Field {

	var $name = 'sequence';

	var $contexts = array( 'single', 'multiple' );

	/**
	 * @var bool
	 */
	var $is_sortable = false;

	/**
	 * @var bool
	 */
	var $is_searchable = false;

	/**
	 * @var bool
	 */
	var $is_numeric = true;

	var $_custom_merge_tag = 'sequence';

	var $group = 'gravityview';

	var $icon = 'dashicons-editor-ol';

	public function __construct() {

		$this->label       = esc_html__( 'Number Sequence', 'gk-gravityview' );
		$this->description = esc_html__( 'Display a sequential result number for each entry.', 'gk-gravityview' );

		add_filter( 'gravityview/metaboxes/tooltips', array( $this, 'field_tooltips' ) );

		add_filter( 'gravityview_entry_default_fields', array( $this, 'add_default_field' ), 10, 3 );

		parent::__construct();
	}

	/**
	 * Add as a default field, outside those set in the Gravity Form form
	 *
	 * @since 2.10 Moved here from GravityView_Admin_Views::get_entry_default_fields
	 *
	 * @param array        $entry_default_fields Existing fields
	 * @param string|array $form form_ID or form object
	 * @param string       $zone Either 'single', 'directory', 'edit', 'header', 'footer'
	 *
	 * @return array
	 */
	public function add_default_field( $entry_default_fields, $form = array(), $zone = '' ) {

		if ( 'edit' === $zone ) {
			return $entry_default_fields;
		}

		$entry_default_fields['sequence'] = array(
			'label' => __( 'Result Number', 'gk-gravityview' ),
			'type'  => $this->name,
			'desc'  => $this->description,
			'icon'  => $this->icon,
			'group' => 'gravityview',
		);

		return $entry_default_fields;
	}

	/**
	 * Add tooltips
	 *
	 * @param  array $tooltips Existing tooltips
	 * @return array           Modified tooltips
	 */
	public function field_tooltips( $tooltips ) {

		$return = $tooltips;

		$return['reverse_sequence'] = array(
			'title' => __( 'Reverse the order of the result numbers', 'gk-gravityview' ),
			'value' => __( 'Output the number sequence in descending order. If enabled, numbers will count down from high to low.', 'gk-gravityview' ),
		);

		return $return;
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		unset( $field_options['search_filter'] );

		$new_fields = array(
			'start'   => array(
				'type'       => 'number',
				'label'      => __( 'First Number in the Sequence', 'gk-gravityview' ),
				'desc'       => __( 'For each entry, the displayed number will increase by one. When displaying ten entries, the first entry will display "1", and the last entry will show "10".', 'gk-gravityview' ),
				'value'      => '1',
				'merge_tags' => false,
			),
			'reverse' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Reverse the order of the number sequence (high to low)', 'gk-gravityview' ),
				'tooltip' => 'reverse_sequence',
				'value'   => '',
			),
		);

		return $new_fields + $field_options;
	}

	/**
	 * Replace {sequence} Merge Tags inside Custom Content fields
	 *
	 * TODO:
	 * - Find a better way to infer current View data (without using legacy code)
	 *
	 * @param array  $matches
	 * @param string $text
	 * @param array  $form
	 * @param array  $entry
	 * @param bool   $url_encode
	 * @param bool   $esc_html
	 *
	 * @return string
	 */
	public function replace_merge_tag( $matches = array(), $text = '', $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {
		/**
		 * An internal cache for sequence tag reuse within one field.
		 * Avoids calling get_sequence over and over again, off-by-many increments, etc.
		 */
		static $merge_tag_sequences = array();

		$view_data = gravityview_get_current_view_data(); // TODO: Don't use legacy code...

		// If we're not in a View or embed, don't replace the merge tag
		if ( empty( $view_data ) ) {
			gravityview()->log->error( '{sequence} Merge Tag used outside of a GravityView View.', array( 'data' => $matches ) );
			return $text;
		}

		$legacy_field = \GravityView_View::getInstance()->getCurrentField(); // TODO: Don't use legacy code...

		// If we're outside field context (like a GV widget), don't replace the merge tag
		if ( ! $legacy_field ) {
			gravityview()->log->error( '{sequence} Merge Tag was used without outside of the GravityView entry loop.', array( 'data' => $matches ) );
			return $text;
		}

		$return = $text;

		$context        = new \GV\Template_Context();
		$context->view  = \GV\View::by_id( $view_data['view_id'] );
		$context->entry = \GV\GF_Entry::from_entry( $entry );

		$gv_field          = \GV\Internal_Field::by_id( 'sequence' );
		$merge_tag_context = \GV\Utils::get( $legacy_field, 'UID' );
		$merge_tag_context = $entry['id'] . "/{$merge_tag_context}";

		foreach ( $matches as $match ) {

			$full_tag = $match[0];
			$property = $match[1];

			$gv_field->reverse = false;
			$gv_field->start   = 1;

			$modifiers = explode( ',', trim( $property ) );

			foreach ( $modifiers as $modifier ) {

				$modifier = trim( $modifier );

				if ( 'reverse' === $modifier ) {
					$gv_field->reverse = true;
				}

				$maybe_start = explode( ':', $modifier );

				// If there is a field with the ID of the start number, the merge tag won't work.
				// In that case, you can use "=" instead: `{sequence start=10}`
				if ( 1 === sizeof( $maybe_start ) ) {
					$maybe_start = explode( '=', $modifier );
				}

				if ( 'start' === rgar( $maybe_start, 0 ) && is_numeric( rgar( $maybe_start, 1 ) ) ) {
					$gv_field->start = (int) rgar( $maybe_start, 1 );
				}
			}

			/**
			 * We make sure that distinct sequence modifiers have their own
			 * output counters.
			 */
			$merge_tag_context_modifiers = $merge_tag_context . '/' . var_export( $gv_field->reverse, true ) . '/' . $gv_field->start;

			if ( ! isset( $merge_tag_sequences[ $merge_tag_context_modifiers ] ) ) {
				$gv_field->UID  = $legacy_field['UID'] . '/' . var_export( $gv_field->reverse, true ) . '/' . $gv_field->start;
				$context->field = $gv_field;
				$sequence       = $merge_tag_sequences[ $merge_tag_context_modifiers ] = $this->get_sequence( $context );
			} else {
				$sequence = $merge_tag_sequences[ $merge_tag_context_modifiers ];
			}

			$return = str_replace( $full_tag, $sequence, $return );
		}

		return $return;
	}

	/**
	 * Calculate the current sequence number for the context.
	 *
	 * This method handles three scenarios:
	 * 1. Single Entry - Finds the entry's position in the full result set.
	 * 2. Paginated Multiple Entries - Calculates sequence based on current page.
	 * 3. Sequential calls - Increments/decrements from cached starting point.
	 *
	 * @since 2.3.3
	 *
	 * @param \GV\Template_Context $context The template context containing View, field, and entry information.
	 *
	 * @return int The sequence number for the field/entry within the View results.
	 */
	public function get_sequence( $context ) {
		// Static cache for starting line numbers per View/field combination.
		static $startlines = array();

		// Ensure field configuration is loaded from View settings.
		$this->ensure_field_configuration( $context );

		// Generate unique key for this View/field combination to track sequence state.
		$context_key = $this->get_context_key( $context );

		// Handle single entry View - need to find its position in the full result set.
		if ( $this->is_single_entry_view( $context ) ) {
			return $this->get_single_entry_sequence( $context );
		}

		// Initialize starting line for this context if not already cached.
		if ( ! isset( $startlines[ $context_key ] ) ) {
			$starting_number = $this->calculate_starting_number( $context );
			$startlines[ $context_key ] = $starting_number;
		}

		// Return current sequence and increment/decrement for next call.
		return $this->get_next_sequence_number( $startlines, $context_key, $context );
	}

	/**
	 * Ensure field configuration values are loaded from View settings.
	 *
	 * This method reads the 'start' and 'reverse' settings from the field's
	 * configuration as saved in the View. This fixes the bug where these
	 * settings were ignored when the field was displayed.
	 *
	 * @since TODO
	 *
	 * @param \GV\Template_Context $context The template context.
	 *
	 * @return void
	 */
	private function ensure_field_configuration( $context ) {
		// Get field configuration once
		$field_settings = $context->field->as_configuration();

		// Load 'start' value from field configuration if not already set.
		// Only check if the value is numeric - this allows "0" to be preserved.
		if ( ! is_numeric( $context->field->start ) ) {
			$start_value = isset( $field_settings['start'] ) ? $field_settings['start'] : 1;
			$context->field->start = is_numeric( $start_value ) ? (int) $start_value : 1;
		}

		// Load 'reverse' value from field configuration if it exists.
		// Only override the field's reverse property if the configuration explicitly sets it.
		// This allows tests to manually set reverse while still respecting View configuration.
		if ( isset( $field_settings['reverse'] ) ) {
			$context->field->reverse = ! empty( $field_settings['reverse'] );
		}
	}

	/**
	 * Generate a unique key for caching sequence state per View/field combination.
	 *
	 * @since TODO
	 *
	 * @param \GV\Template_Context $context The template context.
	 *
	 * @return string MD5 hash of the View anchor ID and field UID.
	 */
	private function get_context_key( $context ) {
		return md5(
			json_encode(
				array(
					$context->view->get_anchor_id(),
					\GV\Utils::get( $context, 'field/UID' ),
				)
			)
		);
	}

	/**
	 * Check if we're in a single entry View context.
	 *
	 * @since TODO
	 *
	 * @param \GV\Template_Context $context The template context.
	 *
	 * @return bool True if viewing a single entry, false otherwise.
	 */
	private function is_single_entry_view( $context ) {
		return $context->request && $context->request->is_entry();
	}

	/**
	 * Calculate sequence number for a single entry View.
	 *
	 * This method finds the position of the current entry within the full
	 * result set by executing the View's query and locating the entry.
	 *
	 * @since TODO
	 *
	 * @param \GV\Template_Context $context The template context.
	 *
	 * @return int The sequence number for the entry, or 0 if not found.
	 */
	private function get_single_entry_sequence( $context ) {
		$entry = $context->request->is_entry();

		// Capture the SQL query used by the View.
		$sql_query = $this->capture_view_sql_query( $context );

		if ( empty( $sql_query ) ) {
			return 0;
		}

		// Get all entries (without pagination) to find current entry's position.
		$results = $this->get_all_entry_results( $sql_query );

		if ( is_null( $results ) ) {
			return 0;
		}

		// Find the entry's position in the results
		return $this->find_entry_position( $results, $entry, $context );
	}

	/**
	 * Capture the SQL query used to fetch entries for the View.
	 *
	 * @since TODO
	 *
	 * @param \GV\Template_Context $context The template context.
	 *
	 * @return array The SQL query parts.
	 */
	private function capture_view_sql_query( $context ) {
		$sql_query = array();

		// Hook into Gravity Forms query generation to capture the SQL
		add_filter(
			'gform_gf_query_sql',
			$callback = function ( $sql ) use ( &$sql_query ) {
				$sql_query = $sql;
				return $sql;
			}
		);

		// Trigger the query by getting entries (this populates $sql_query)
		// Pass the request to ensure proper context
		$request = isset( $context->request ) ? $context->request : gravityview()->request;
		$context->view->get_entries( $request )->total();

		// Clean up by removing our filter
		remove_filter( 'gform_gf_query_sql', $callback );

		// Remove pagination to get all results
		unset( $sql_query['paginate'] );

		// Optimize SELECT clause to only fetch entry IDs for better memory efficiency
		// This significantly reduces memory usage for large result sets
		$sql_query['select'] = 'SELECT `t1`.`id`';

		// For Single Entry context with a custom start value, sort by ID ASC to get
		// consistent sequence numbers based on creation order
		// Only override if start value is different from default (1)
		if ( $this->is_single_entry_view( $context ) && $context->field->start !== 1 ) {
			$sql_query['order'] = 'ORDER BY `t1`.`id` ASC';
		}

		return $sql_query;
	}

	/**
	 * Execute SQL query to get all entry results.
	 *
	 * @since TODO
	 *
	 * @param array $sql_query The SQL query parts.
	 *
	 * @return array|null Array of results or null on failure.
	 */
	private function get_all_entry_results( $sql_query ) {
		global $wpdb;
		return $wpdb->get_results( implode( ' ', $sql_query ), ARRAY_A );
	}

	/**
	 * Find the position of an entry in the results and calculate its sequence number.
	 *
	 * @since TODO
	 *
	 * @param array                $results The query results.
	 * @param \GV\Entry           $entry   The entry to find.
	 * @param \GV\Template_Context $context The template context.
	 *
	 * @return int The sequence number for the entry, or 0 if not found.
	 */
	private function find_entry_position( $results, $entry, $context ) {
		$total_entries = count( $results );

		foreach ( $results as $position => $result ) {
			// Check if this result row contains the entry ID.
			// The result may have the ID in 'id' or 'entry_id' column.
			$result_id = isset( $result['id'] ) ? $result['id'] : ( isset( $result['entry_id'] ) ? $result['entry_id'] : null );

			// Use loose comparison to handle string/int type differences.
			if ( $result_id != $entry->ID ) {
				continue;
			}

			// Calculate sequence based on position in the View's sort order.
			if ( $context->field->reverse ) {
				// For reverse: highest number - position.
				return $context->field->start + $total_entries - $position - 1;
			} else {
				// For normal: position + start value (position is 0-based)
				return $position + $context->field->start;
			}
		}

		return 0; // Entry not found.
	}

	/**
	 * Calculate the starting number for paginated Views.
	 *
	 * Takes into account the current page, page size, and whether
	 * the sequence is reversed.
	 *
	 * @since TODO
	 *
	 * @param \GV\Template_Context $context The template context.
	 *
	 * @return int The starting sequence number for the current page.
	 */
	private function calculate_starting_number( $context ) {
		// Get current page number (convert from 1-based to 0-based).
		$pagenum = max( 0, \GV\Utils::_GET( 'pagenum', 1 ) - 1 );

		// Get number of entries per page.
		$pagesize = $context->view->settings->get( 'page_size', 25 );

		if ( $context->field->reverse ) {
			// For reversed sequences: highest number = start + total - 1.
			// Then subtract entries on previous pages.

			// Get total entries count
			$total_entries = 0;

			// Primary method: Use View's entries collection to respect all View filters.
			// This includes search filters, field filters, approval status, joins, etc.
			try {
				$request = gravityview()->request;
				if ( $request ) {
					$entries_collection = $context->view->get_entries( $request );
					if ( $entries_collection ) {
						$total_entries = $entries_collection->total();
					}
				}
			} catch ( \Exception $e ) {
				// If there's any error getting the View's collection, we'll fall back.
				$total_entries = 0;
			}

			// If we couldn't get the total from the View's collection,
			// we should NOT fall back to GFAPI::count_entries() as it ignores filters.
			// Instead, default to 1 to avoid division by zero or negative numbers.
			// This is a safer approach that won't give misleading sequence numbers.
			if ( $total_entries <= 0 ) {
				$total_entries = 1;
			}

			$entries_before = $pagenum * $pagesize;

			// Highest number in sequence = start + (total - 1).
			// Current page starts at: highest - entries_before.
			// Formula: start + (total - 1) - entries_before = start + total - entries_before - 1.
			return $context->field->start + $total_entries - $entries_before - 1;
		} else {
			// For normal sequences: calculate based on page position.
			$entries_before = $pagenum * $pagesize;

			// Calculate: entries_before + start_value.
			return $entries_before + $context->field->start;
		}
	}

	/**
	 * Get the next sequence number and update the counter for subsequent calls.
	 *
	 * @since TODO
	 *
	 * @param array                $startlines  Reference to the static startlines cache.
	 * @param string               $context_key The unique context key.
	 * @param \GV\Template_Context $context     The template context.
	 *
	 * @return int The current sequence number.
	 */
	private function get_next_sequence_number( &$startlines, $context_key, $context ) {
		// Get the current sequence number before incrementing/decrementing
		$sequence_number = $startlines[ $context_key ];

		// Update the counter for the next call
		if ( $context->field->reverse ) {
			$startlines[ $context_key ]--;
		} else {
			$startlines[ $context_key ]++;
		}

		/**
		 * Filter the sequence number before it's displayed.
		 *
		 * This allows developers to customize the sequence number output,
		 * for example to add prefixes, suffixes, or custom formatting.
		 *
		 * @since TODO
		 *
		 * @param int                  $sequence_number The calculated sequence number.
		 * @param \GV\Template_Context $context         The template context.
		 * @param string               $context_key     The unique context key.
		 */
		$sequence_number = apply_filters( 'gravityview/field/sequence/value', $sequence_number, $context, $context_key );

		return $sequence_number;
	}
}

new GravityView_Field_Sequence();
