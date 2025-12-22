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

		// If we're outside field context (like a GV widget), don't replace the merge tag.
		if ( ! $legacy_field ) {
			gravityview()->log->error( '{sequence} Merge Tag was used without outside of the GravityView entry loop.', array( 'data' => $matches ) );

			return $text;
		}

		$return = $text;

		// Entry is required for sequence calculation.
		if ( empty( $entry ) ) {
			gravityview()->log->debug( '{sequence} Merge Tag requires an entry context.', array( 'data' => $matches ) );

			return $text;
		}

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
	 * @param  \GV\Template_Context $context The context.
	 *
	 * @return int The sequence number for the field/entry within the view results.
	 */
	public function get_sequence( $context ) {
		static $startlines = array();

		$context_key = md5(
			json_encode(
				array(
					$context->view->get_anchor_id(),
					\GV\Utils::get( $context, 'field/UID' ),
				)
			)
		);

		/**
		 * Figure out the starting number.
		 */
		if ( $context->request && $entry = $context->request->is_entry() ) {

			$sql_query = array();

			add_filter(
				'gform_gf_query_sql',
				$callback = function ( $sql ) use ( &$sql_query ) {
					$sql_query = $sql;
					return $sql;
				}
			);

			$total = $context->view->get_entries()->total();
			remove_filter( 'gform_gf_query_sql', $callback );

			unset( $sql_query['paginate'] );

			global $wpdb;

			$results = $wpdb->get_results( implode( ' ', $sql_query ), ARRAY_A );

			if ( is_null( $results ) ) {
				return 0;
			}

			foreach ( $results as $n => $result ) {
				if ( in_array( $entry->ID, $result ) ) {
					return $context->field->reverse ? ( $total - $n ) : ( $n + 1 );
				}
			}

			return 0;
		} elseif ( ! isset( $startlines[ $context_key ] ) ) {
			$pagenum  = max( 0, \GV\Utils::_GET( 'pagenum', 1 ) - 1 );
			$pagesize = $context->view->settings->get( 'page_size', 25 );

			if ( $context->field->reverse ) {
				$startlines[ $context_key ]  = $context->view->get_entries()->total() - ( $pagenum * $pagesize );
				$startlines[ $context_key ] += $context->field->start - 1;
			} else {
				$startlines[ $context_key ] = ( $pagenum * $pagesize ) + $context->field->start;
			}
		}

		return $context->field->reverse ? $startlines[ $context_key ]-- : $startlines[ $context_key ]++;
	}
}

new GravityView_Field_Sequence();
