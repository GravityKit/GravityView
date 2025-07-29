<?php
/**
 * @file class-gravityview-field-workflow_final_status.php
 * @since 1.17.2
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Workflow_Final_Status extends GravityView_Field {

	public $name = 'workflow_final_status';

	public $group = 'add-ons';

	public $icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMS43IDExLjIiPjxwYXRoIGQ9Ik0xNC43IDUuOWwtNy00Yy0xLjItLjctMi41LS44LTMuNy0uMy0xLjcuNy0yLjYgMS45LTIuNyAzLjYtLjEgMS41LjQgMi43IDEuNCAzLjcgMS4xIDEuMSAyLjYgMS40IDQuMy45LjIgMCAuNS0uMiAxLjEtLjQuMi0uMS4zLS4xLjQtLjEuMyAwIC41LjEuNi40LjEuMyAwIC41LS4zLjctMS4yLjctMi40LjktMy44LjgtMS4zLS4yLTIuNS0uNy0zLjQtMS42Qy41IDguNS0uMSA3LjEgMCA1LjVjLjEtMi40IDEuMi00IDMuMy01QzQuNS0uMSA1LjgtLjIgNy4yLjJjLjIuMS42LjIgMS4yLjZsNyAzLjkuNC0uNi44IDIuMS0yLjIuMy4zLS42em0tNy44LS41bDcgNGMxLjIuNyAyLjUuOCAzLjcuMyAxLjctLjcgMi42LTEuOSAyLjgtMy42LjEtMS40LS40LTIuNi0xLjUtMy43cy0yLjUtMS40LTQuMy0xYy0uNC4xLS44LjMtMS4xLjRsLS40LjFjLS4zIDAtLjUtLjEtLjYtLjQtLjEtLjMgMC0uNS4zLS43IDEuMS0uNyAyLjQtLjkgMy44LS44IDEuNC4yIDIuNS43IDMuNCAxLjcgMS4yIDEuMiAxLjcgMi41IDEuNiA0LjEtLjEgMi4zLTEuMiA0LTMuMyA1LTEuNC42LTIuNy42LTMuOS4yLS4zLS4xLS43LS4zLTEuMS0uNWwtNy0zLjktLjQuNUw1LjEgNWwyLjItLjMtLjQuN3oiLz48L3N2Zz4=';

	public function __construct() {
		if ( ! defined( 'GRAVITY_FLOW_VERSION' ) ) {
			return;
		}

		$this->label                = esc_html__( 'Workflow Status', 'gk-gravityview' );
		$this->default_search_label = $this->label;

		$this->add_hooks();

		parent::__construct();
	}

	function add_hooks() {
		add_filter( 'gravityview_field_entry_value_workflow_final_status', array( $this, 'modify_entry_value_workflow_final_status' ), 10, 4 );
	}

	/**
	 * Convert the status key with the full status label. Uses custom labels, if set.
	 *
	 * @uses Gravity_Flow::translate_status_label()
	 *
	 * @param string $output HTML value output
	 * @param array  $entry The GF entry array
	 * @param  array  $field_settings Settings for the particular GV field
	 * @param array  $field Current field being displayed
	 *
	 * @since 1.17
	 *
	 * @return string If Gravity Flow not found, or entry not processed yet, returns initial value. Otherwise, returns name of workflow step.
	 */
	function modify_entry_value_workflow_final_status( $output, $entry, $field_settings, $field ) {

		if ( ! empty( $output ) ) {
			$output = gravity_flow()->translate_status_label( $output );
		}

		return $output;
	}
}

new GravityView_Field_Workflow_Final_Status();
