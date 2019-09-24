<?php
/**
 * @file class-gravityview-field-workflow_current_status_timestamp.php
 * @since develop
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Workflow_Current_Status_Timestamp extends GravityView_Field {

	var $name = 'workflow_current_status_timestamp';

	var $group = 'meta';

	var $contexts = array( 'multiple', 'single' );

	var $entry_meta_key = 'workflow_current_status_timestamp';

	var $is_numeric = true;

	public function __construct() {
		$this->label = esc_html__( 'Workflow Current Status Timestamp', 'gravityview' );
		$this->add_hooks();
		parent::__construct();
	}

	function add_hooks() {
		add_filter( 'gravityview_field_entry_value_workflow_current_status_timestamp', array( $this, 'modify_entry_value_workflow_current_status_timestamp' ), 10, 4 );
	}

	/**
	 * Convert a timestamp into a nice format.
	 *
	 * @param string $output HTML value output
	 * @param array  $entry The GF entry array
	 * @param  array $field_settings Settings for the particular GV field,
	 * @param array $field Current field being displayed
	 *
	 * @since 1.17
	 *
	 * @return string If Gravity Flow not found, or entry not processed yet, returns initial value. Otherwise, returns name of workflow step.
	 */
	function modify_entry_value_workflow_current_status_timestamp( $output, $entry, $field_settings, $field ) {
		$timestamp = gform_get_meta( $entry['id'], 'workflow_current_status_timestamp' );

		if ( ! $timestamp ) {
			return $timestamp;
		}

		return GVCommon::format_date( date( 'Y-m-d H:i:s', $timestamp ), 'format=' . \GV\Utils::get( $field_settings, 'date_display' ) );
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {
		if ( $context == 'edit' ) {
			return $field_options;
		}

		$this->add_field_support( 'date_display', $field_options );

		return $field_options;
	}
}

new GravityView_Field_Workflow_Current_Status_Timestamp;
