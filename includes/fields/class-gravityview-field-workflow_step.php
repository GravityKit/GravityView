<?php
/**
 * @file class-gravityview-field-workflow_step.php
 * @since 1.17.2
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Workflow_Step extends GravityView_Field {

	public $name = 'workflow_step';

	public $group = 'add-ons';

	public $icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMS43IDExLjIiPjxwYXRoIGQ9Ik0xNC43IDUuOWwtNy00Yy0xLjItLjctMi41LS44LTMuNy0uMy0xLjcuNy0yLjYgMS45LTIuNyAzLjYtLjEgMS41LjQgMi43IDEuNCAzLjcgMS4xIDEuMSAyLjYgMS40IDQuMy45LjIgMCAuNS0uMiAxLjEtLjQuMi0uMS4zLS4xLjQtLjEuMyAwIC41LjEuNi40LjEuMyAwIC41LS4zLjctMS4yLjctMi40LjktMy44LjgtMS4zLS4yLTIuNS0uNy0zLjQtMS42Qy41IDguNS0uMSA3LjEgMCA1LjVjLjEtMi40IDEuMi00IDMuMy01QzQuNS0uMSA1LjgtLjIgNy4yLjJjLjIuMS42LjIgMS4yLjZsNyAzLjkuNC0uNi44IDIuMS0yLjIuMy4zLS42em0tNy44LS41bDcgNGMxLjIuNyAyLjUuOCAzLjcuMyAxLjctLjcgMi42LTEuOSAyLjgtMy42LjEtMS40LS40LTIuNi0xLjUtMy43cy0yLjUtMS40LTQuMy0xYy0uNC4xLS44LjMtMS4xLjRsLS40LjFjLS4zIDAtLjUtLjEtLjYtLjQtLjEtLjMgMC0uNS4zLS43IDEuMS0uNyAyLjQtLjkgMy44LS44IDEuNC4yIDIuNS43IDMuNCAxLjcgMS4yIDEuMiAxLjcgMi41IDEuNiA0LjEtLjEgMi4zLTEuMiA0LTMuMyA1LTEuNC42LTIuNy42LTMuOS4yLS4zLS4xLS43LS4zLTEuMS0uNWwtNy0zLjktLjQuNUw1LjEgNWwyLjItLjMtLjQuN3oiLz48L3N2Zz4=';

	public function __construct() {
		if ( ! defined( 'GRAVITY_FLOW_VERSION' ) ) {
			return;
		}

		$this->label                = esc_html__( 'Workflow Step', 'gk-gravityview' );

		$this->default_search_label = $this->label;

		$this->add_hooks();

		parent::__construct();
	}

	function add_hooks() {

		add_filter( 'gravityview_search_field_label', array( $this, 'modify_gravityview_search_field_step_label' ), 10, 3 );

		add_filter( 'gravityview_widget_search_filters', array( $this, 'modify_frontend_search_fields' ), 10, 3 );

		add_filter( 'gravityview_field_entry_value_workflow_step', array( $this, 'modify_entry_value_workflow_step' ), 10, 4 );
	}

	/**
	 * Get the value of the Workflow Step based on the `workflow_step` entry meta int value
	 *
	 * @uses Gravity_Flow_API::get_current_step
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
	function modify_entry_value_workflow_step( $output, $entry, $field_settings, $field ) {

		// If not set, the entry hasn't started a workflow
		$has_workflow_step = isset( $entry['workflow_step'] );

		if ( $has_workflow_step ) {

			$GFlow = new Gravity_Flow_API( $entry['form_id'] );

			if ( $current_step = $GFlow->get_current_step( $entry ) ) {
				$output = esc_html( $current_step->get_name() );
			} else {
				$output = esc_html__( 'Workflow Complete', 'gk-gravityview' );
			}

			unset( $GFlow );
		}

		return $output;
	}

	/**
	 * Get the Workflow Step ID from a search field key
	 *
	 * @param string $key Search field key, in the following format: `workflow_step_status_[number]`
	 *
	 * @return bool|int The ID of the workflow step. False if not a workflow step field key.
	 */
	private function get_step_id_from_key( $key ) {

		$workflow_step_id = false;

		preg_match( '/workflow_step_status_(\d+)/', $key, $matches );

		if ( ! empty( $matches ) ) {
			$workflow_step_id = intval( $matches[1] );
		}

		return $workflow_step_id;
	}

	/**
	 * @since 1.17.3
	 *
	 * @param string        $label Existing label text, sanitized.
	 * @param null|GF_Field $gf_field If search field is connected to a Gravity Forms field, the field object.
	 * @param array         $field Array with the following keys: `field` ID of the meta key or field ID to be searched, `input` the type of search input to be shown, `label` the existing label. Same as $label parameter.
	 *
	 * @return string If showing a search field for a Step, show the step label.
	 */
	function modify_gravityview_search_field_step_label( $label = '', $gf_field = null, $field = array() ) {

		$return = $label;

		if ( '' === $label && $workflow_step_id = $this->get_step_id_from_key( $field['field'] ) ) {

			$step = $this->get_workflow_step( $workflow_step_id );

			$return = esc_html( $step->get_label() );
		}

		return $return;
	}

	/**
	 * Get a Gravity_Flow_Step object from the step ID
	 *
	 * @since 1.17.3
	 *
	 * @uses GravityView_View::getFormId() to get the current form being searched
	 * @uses Gravity_Flow_API::get_step()
	 *
	 * @param int $workflow_step_id ID of the step
	 *
	 * @return bool|Gravity_Flow_Step
	 */
	function get_workflow_step( $workflow_step_id = 0 ) {

		$form_id = GravityView_View::getInstance()->getFormId();

		$GFlow = new Gravity_Flow_API( $form_id );

		$workflow_step = $GFlow->get_step( $workflow_step_id );

		if ( ! $GFlow || ! $workflow_step ) {
			return false;
		}

		return $workflow_step;
	}

	/**
	 * Set the search field choices to the Steps available for the current form
	 *
	 * @since 1.17.3
	 *
	 * @param array                     $search_fields
	 * @param GravityView_Widget_Search $widget
	 * @param array                     $widget_args
	 *
	 * @return array
	 */
	function modify_frontend_search_fields( $search_fields = array(), GravityView_Widget_Search $widget = null, $widget_args = array() ) {

		foreach ( $search_fields as & $search_field ) {

			if ( $this->name === $search_field['key'] ) {

				$form_id = GravityView_View::getInstance()->getFormId();

				$workflow_steps = gravity_flow()->get_steps( $form_id );

				$choices = array();

				foreach ( $workflow_steps as $step ) {
					$choices[] = array(
						'text'  => $step->get_name(),
						'value' => $step->get_id(),
					);
				}

				$search_field['choices'] = $choices;
			}

			// Workflow Step Statuses
			elseif ( $workflow_step_id = $this->get_step_id_from_key( $search_field['key'] ) ) {

				$status_key = sprintf( 'workflow_step_status_%d', $workflow_step_id );

				$search_field['choices'] = GravityView_Plugin_Hooks_Gravity_Flow::get_status_options( null, $status_key );
			}
		}

		return $search_fields;
	}
}

new GravityView_Field_Workflow_Step();
