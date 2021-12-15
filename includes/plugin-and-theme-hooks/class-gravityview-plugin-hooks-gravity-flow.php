<?php
/**
 * Add Gravity Flow output to GravityView
 *
 * @file      class-gravityview-plugin-hooks-gravity-flow.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
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

		add_filter( 'gravityview/admin/available_fields', array( $this, 'maybe_add_non_default_fields' ), 10, 3 );

		add_filter( 'gravityview/adv_filter/field_filters', array( $this, 'maybe_add_non_default_filter_fields' ), 10, 2 );

		add_action( 'gravityflow_post_process_workflow', array( $this, 'clear_cache_after_workflow' ), 10, 4 );
	}

	/**
	 * Clears GravityView entry cache after running a Gravity Flow Workflow
	 *
	 * @param array $form
	 * @param int $entry_id
	 * @param int $step_id
	 * @param int $starting_step_id
	 *
	 * @return void
	 */
	public function clear_cache_after_workflow( $form, $entry_id, $step_id, $starting_step_id ) {
		do_action( 'gravityview_clear_form_cache', $form['id'] );
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

		return (array) \GV\Utils::get( $entry_meta, $status_key . '/filter/choices' );
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

	/**
	 * Add the current status timestamp field to available Advanced Filters.
	 */
	public function maybe_add_non_default_filter_fields( $fields, $view_id ) {
		if ( ( $insert_at = array_search( 'workflow_final_status', wp_list_pluck( $fields, 'key' ) ) ) !== false ) {
			$fields_end = array_splice( $fields, $insert_at + 1 );

			$fields[] = array(
				'text' => __( 'Workflow Current Status Timestamp', 'gravityview' ),
				'operators' => array( '>', '<' ),
				'placeholder' => 'yyyy-mm-dd',
				'cssClass' => 'datepicker ymd_dash',
				'key' => 'workflow_current_status_timestamp',
				'preventMultiple' => false,
			);

			$fields = array_merge( $fields, $fields_end );
		}
		return $fields;
	}

	/**
	 * Add the current status timestamp field to available View configuration fields.
	 */
	public function maybe_add_non_default_fields( $fields, $form, $zone ) {
		if ( strpos( implode( ' ', array_keys( $fields ) ), 'workflow' ) !== false ) {
			$keys   = array_keys( $fields );
			$values = array_values( $fields );

			if ( ( $insert_at = array_search( 'workflow_final_status', $keys ) ) !== false ) {
				$keys_end = array_splice( $keys, $insert_at + 1 );
				$values_end = array_splice( $values, $insert_at + 1 );

				$keys[] = 'workflow_current_status_timestamp';
				$values[] = array(
					'label' => __( 'Workflow Current Status Timestamp', 'gravityview' ),
					'type' => 'workflow_current_status_timestamp',
				);

				$fields = array_combine( $keys, $values ) + array_combine( $keys_end, $values_end );
			}
		}

		return $fields;
	}

}

new GravityView_Plugin_Hooks_Gravity_Flow;