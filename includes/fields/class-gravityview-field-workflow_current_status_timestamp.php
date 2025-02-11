<?php
/**
 * @file class-gravityview-field-workflow_current_status_timestamp.php
 * @since develop
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Workflow_Current_Status_Timestamp extends GravityView_Field {

	var $name = 'workflow_current_status_timestamp';

	public $group = 'add-ons';

	var $contexts = array( 'multiple', 'single' );

	var $entry_meta_key = 'workflow_current_status_timestamp';

	var $is_numeric = true;

	public $icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMS43IDExLjIiPjxwYXRoIGQ9Ik0xNC43IDUuOWwtNy00Yy0xLjItLjctMi41LS44LTMuNy0uMy0xLjcuNy0yLjYgMS45LTIuNyAzLjYtLjEgMS41LjQgMi43IDEuNCAzLjcgMS4xIDEuMSAyLjYgMS40IDQuMy45LjIgMCAuNS0uMiAxLjEtLjQuMi0uMS4zLS4xLjQtLjEuMyAwIC41LjEuNi40LjEuMyAwIC41LS4zLjctMS4yLjctMi40LjktMy44LjgtMS4zLS4yLTIuNS0uNy0zLjQtMS42Qy41IDguNS0uMSA3LjEgMCA1LjVjLjEtMi40IDEuMi00IDMuMy01QzQuNS0uMSA1LjgtLjIgNy4yLjJjLjIuMS42LjIgMS4yLjZsNyAzLjkuNC0uNi44IDIuMS0yLjIuMy4zLS42em0tNy44LS41bDcgNGMxLjIuNyAyLjUuOCAzLjcuMyAxLjctLjcgMi42LTEuOSAyLjgtMy42LjEtMS40LS40LTIuNi0xLjUtMy43cy0yLjUtMS40LTQuMy0xYy0uNC4xLS44LjMtMS4xLjRsLS40LjFjLS4zIDAtLjUtLjEtLjYtLjQtLjEtLjMgMC0uNS4zLS43IDEuMS0uNyAyLjQtLjkgMy44LS44IDEuNC4yIDIuNS43IDMuNCAxLjcgMS4yIDEuMiAxLjcgMi41IDEuNiA0LjEtLjEgMi4zLTEuMiA0LTMuMyA1LTEuNC42LTIuNy42LTMuOS4yLS4zLS4xLS43LS4zLTEuMS0uNWwtNy0zLjktLjQuNUw1LjEgNWwyLjItLjMtLjQuN3oiLz48L3N2Zz4=';

	public function __construct() {
		if ( ! defined( 'GRAVITY_FLOW_VERSION' ) ) {
			return;
		}

		$this->label = esc_html__( 'Workflow Current Status Timestamp', 'gk-gravityview' );

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
	 * @param  array  $field_settings Settings for the particular GV field,
	 * @param array  $field Current field being displayed
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
		if ( 'edit' == $context ) {
			return $field_options;
		}

		$this->add_field_support( 'date_display', $field_options );

		return $field_options;
	}
}

new GravityView_Field_Workflow_Current_Status_Timestamp();
