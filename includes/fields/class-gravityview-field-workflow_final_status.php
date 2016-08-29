<?php
/**
 * @file class-gravityview-field-workflow_final_status.php
 * @since 1.17.2
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Workflow_Final_Status extends GravityView_Field {

	var $name = 'workflow_final_status';

	var $group = 'meta';

	public function __construct() {
		$this->label = esc_html__( 'Workflow Status', 'gravityview' );
		$this->default_search_label = $this->label;
		$this->add_hooks();
		parent::__construct();
	}

	function add_hooks() {
		add_filter( 'gravityview_widget_search_filters', array( $this, 'modify_search_filters' ), 10, 3 );

		add_filter( 'gravityview_field_entry_value_workflow_final_status', array( $this, 'modify_entry_value_workflow_final_status' ), 10, 4 );
	}

	/**
	 * Convert the status key with the full status label. Uses custom labels, if set.
	 *
	 * @uses Gravity_Flow::translate_status_label()
	 *
	 * @param string $output HTML value output
	 * @param array  $entry The GF entry array
	 * @param  array $field_settings Settings for the particular GV field
	 * @param array $field Current field being displayed
	 *
	 * @since 1.17
	 *
	 * @return string If Gravity Flow not found, or entry not processed yet, returns initial value. Otherwise, returns name of workflow step.
	 */
	function modify_entry_value_workflow_final_status( $output, $entry, $field_settings, $field ) {

		if( ! empty( $output ) ) {
			$output = gravity_flow()->translate_status_label( $output );
		}

		return $output;
	}


	/**
	 * Populate the Final Status Search Bar field dropdown with all the statuses in Gravity Flow
	 *
	 * @since 1.17.3
	 *
	 * @param array $search_fields
	 * @param GravityView_Widget_Search $widget
	 * @param array $widget_args
	 *
	 * @return array
	 */
	function modify_search_filters( $search_fields = array(), GravityView_Widget_Search $widget, $widget_args = array() ) {

		foreach ( $search_fields as & $search_field ) {
			if ( $this->name === $search_field['key'] ) {
				$search_field['choices'] = GravityView_Plugin_Hooks_Gravity_Flow::get_status_options();
			}
		}

		return $search_fields;
	}

}

new GravityView_Field_Workflow_Final_Status;
