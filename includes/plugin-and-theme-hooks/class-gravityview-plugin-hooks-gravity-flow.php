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
	 * @var string Check for the Gravity Flow constant
	 */
	protected $constant_name = 'GRAVITY_FLOW_VERSION';


	/**
	 * Filter the values shown in GravityView frontend
	 *
	 * @since 1.17
	 */
	function add_hooks() {

		parent::add_hooks();

		add_filter( 'gravityview/search/searchable_fields', array( $this, 'modify_search_bar_fields_dropdown'), 10, 2 );

	}
	

	/**
	 * Get the available status choices from Gravity Flow
	 *
	 * @uses Gravity_Flow::get_entry_meta()
	 *
	 * @since 1.17.3
	 *
	 * @param int $form_id
	 * @param string $status_key By default, get all statuses
	 *
	 * @return array
	 */
	public static function get_status_options( $form_id = 0, $status_key = 'workflow_final_status' ) {

		if( empty( $form_id ) ) {
			$form_id = GravityView_View::getInstance()->getFormId();
		}

		$entry_meta = gravity_flow()->get_entry_meta( array(), $form_id );

		return (array) rgars( $entry_meta, $status_key . '/filter/choices' );
	}


	/**
	 * Get the list of active Workflow Steps and Workflow Step Statuses
	 *
	 * @since 1.17.3
	 *
	 * @uses Gravity_Flow_API::get_current_step
	 *
	 * @param array $fields Array of searchable fields
	 * @param  int $form_id
	 *
	 * @return array Updated Array of searchable fields
	 */
	public function modify_search_bar_fields_dropdown( $fields, $form_id ) {

		$GFlow = new Gravity_Flow_API( $form_id );

		$workflow_steps = $GFlow->get_steps();

		if( $workflow_steps ) {

			foreach ( $workflow_steps as $step ) {

				$step_id = sprintf( 'workflow_step_status_%d', $step->get_id() );

				$fields[ $step_id ] = array(
					'label' => sprintf( _x( 'Status: %s', 'Gravity Flow Workflow Step Status', 'gravityview' ), $step->get_name() ),
					'type'  => 'select',
				);
			}

			$fields['workflow_step'] = array(
				'label' => esc_html__( 'Workflow Step', 'gravityview' ),
				'type'  => 'select',
			);

			$fields['workflow_final_status'] = array(
				'label' => esc_html__( 'Workflow Status', 'gravityview' ),
				'type'  => 'select',
			);
		}

		return $fields;
	}

}

new GravityView_Plugin_Hooks_Gravity_Flow;