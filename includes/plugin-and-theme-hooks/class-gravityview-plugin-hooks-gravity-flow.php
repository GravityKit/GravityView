<?php
/**
 * Add Gravity Flow output to GravityView
 *
 * @file      class-gravityview-plugin-hooks-gravity-flow.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      https://gravityview.co
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 1.17
 */

/**
 * @inheritDoc
 * @since 1.17
 */
class GravityView_Plugin_Hooks_Gravity_Flow extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @var string Check for the class name, since that's needed by this class.
	 */
	protected $class_name = 'Gravity_Flow_API';


	/**
	 * Filter the values shown in GravityView frontend
	 *
	 * @since 1.17
	 */
	function add_hooks() {

		parent::add_hooks();

		add_filter( 'gravityview_field_entry_value_workflow_final_status', array( $this, 'modify_entry_value_workflow_final_status' ), 10, 4 );

		add_filter( 'gravityview_field_entry_value_workflow_step', array( $this, 'modify_entry_value_workflow_step' ), 10, 4 );

		add_filter( 'gravityview/search/searchable_fields', array( $this, 'modify_search_fields_workflow_steps'), 10, 2 );
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
	 * Get the value of the Workflow Step based on the `workflow_step` entry meta int value
	 *
	 * @uses Gravity_Flow_API::get_current_step
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
	function modify_entry_value_workflow_step( $output, $entry, $field_settings, $field ) {

		// If not set, the entry hasn't started a workflow
		$has_workflow_step = isset( $entry['workflow_step'] );

		if( $has_workflow_step ) {

			$GFlow = new Gravity_Flow_API( $entry['form_id'] );

			if ( $current_step = $GFlow->get_current_step( $entry ) ) {
				$output = esc_html( $current_step->get_name() );
			} else {
				$output = esc_html__( 'Workflow Complete', 'gravityview' );
			}

			unset( $GFlow );
		}

		return $output;
	}

	/**
	 * Get the list of active Workflow Steps of the Workflow Step based on the `workflow_step` entry meta int value
	 *
	 * @uses Gravity_Flow_API::get_current_step
	 *
	 * @param array $fields Array of searchable fields
	 * @param  int $form_id
	 *
	 * @since 1.17
	 *
	 * @return array Updated Array of searchable fields
	 */
	function modify_search_fields_workflow_steps( $fields, $form_id ) {

		$GFlow = new Gravity_Flow_API( $form_id );
		if( $GFlow ):
			$workflow_steps = $GFlow->get_steps();

			if( $workflow_steps ):

				$display_steps = array();

				foreach($workflow_steps as $step):
					$display_steps[] = array( "text" => $step->get_name(), "value" => $step->get_id() );
				endforeach;

				$workflow_step_type = array(
					"label" 		=> "Workflow Step",
					"input"			=> "select",
					"choices"		=> $display_steps
				);

				$fields[] = $workflow_step_type;

			endif;

		endif;

		return $fields;
	}

}

new GravityView_Plugin_Hooks_Gravity_Flow;