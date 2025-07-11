<?php
/**
 * Add Gravity Flow output to GravityView
 *
 * @file      class-gravityview-plugin-hooks-gravity-flow.php
 * @since     1.17
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

use GV\Search\Fields\Search_Field_Gravity_Flow_Final_Status;
use GV\Search\Fields\Search_Field_Gravity_Flow_Status_Step;
use GV\Search\Fields\Search_Field_Gravity_Flow_Step;

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

//		add_filter( 'gravityview/search/searchable_fields', array( $this, 'modify_search_bar_fields_dropdown' ), 10, 2 );

		add_filter( 'gravityview/admin/available_fields', [ $this, 'maybe_add_non_default_fields' ], 10, 3 );

		if ( defined( 'GRAVITYKIT_ADVANCED_FILTERING_VERSION' ) ) {
			add_filter(
				version_compare( GRAVITYKIT_ADVANCED_FILTERING_VERSION, '3.0.0', '<' )
					? 'gravityview/adv_filter/field_filters'
					: 'gk/query-filters/field-filters',
				[ $this, 'maybe_add_non_default_filter_fields' ], 10, 2 );
		}

		add_action( 'gravityflow_post_process_workflow', [ $this, 'clear_cache_after_workflow' ], 10, 4 );

		add_filter( 'gravityview/extension/search/input_type', [ $this, 'add_workflow_user_fields_to_search' ], 10, 2 );

		add_filter( 'gk/gravityview/search/available-fields', [ $this, 'add_workflow_search_fields' ], 10, 2 );
	}

	/**
	 * Add Gravity Flow Workflow User fields to search.
	 *
	 * @param string $input_type The input type, for example, 'text', 'select', 'date', etc. {@see GravityView_Widget_Search::get_search_input_types()}.
	 * @param string $field_type The field type, for example, 'workflow_user', 'workflow_multi_user', etc.
	 *
	 * @return string The input type to use for the field.
	 */
	public function add_workflow_user_fields_to_search( $input_type, $field_type ) {

		if ( 'workflow_multi_user' === $field_type ) {
			return 'multi';
		}

		if (
			in_array(
				$field_type,
				[
					'workflow_user',
					'workflow_role',
				],
				true
			)
		) {
			return 'select';
		}

		return $input_type;
	}

	/**
	 * Adds Gravity Flow-specific search fields.
	 *
	 * @since $ver$
	 *
	 * @param array $fields  The current fields.
	 * @param int   $form_id The form ID.
	 *
	 * @return array The updated search fields.
	 */
	public function add_workflow_search_fields( $fields, $form_id ): array {
		if ( ! is_array( $fields ) ) {
			return [];
		}

		if ( ! is_int( $form_id ) ) {
			return $fields;
		}

		$gravity_flow_api = new Gravity_Flow_API( $form_id );
		$workflow_steps   = $gravity_flow_api->get_steps();

		if ( ! $workflow_steps ) {
			return $fields;
		}

		foreach ( $workflow_steps as $step ) {
			$fields[] = Search_Field_Gravity_Flow_Status_Step::from_step( $step );
		}

		$fields[] = Search_Field_Gravity_Flow_Step::from_configuration( [ 'form_id' => $form_id ] );
		$fields[] = Search_Field_Gravity_Flow_Final_Status::from_configuration( [ 'form_id' => $form_id ] );

		return $fields;
	}

	/**
	 * Clears GravityView entry cache after running a Gravity Flow Workflow
	 *
	 * @param array $form
	 * @param int   $entry_id
	 * @param int   $step_id
	 * @param int   $starting_step_id
	 *
	 * @return void
	 */
	public function clear_cache_after_workflow( $form, $entry_id, $step_id, $starting_step_id ) {
		do_action( 'gravityview_clear_form_cache', $form['id'] );
	}

	/**
	 * Get the available status choices from Gravity Flow
	 *
	 * @since 1.17.3
	 *
	 * @param int    $form_id
	 * @param string $status_key By default, get all statuses
	 *
	 * @uses  Gravity_Flow::get_entry_meta()
	 *
	 * @return array
	 */
	public static function get_status_options( $form_id = 0, $status_key = 'workflow_final_status' ) {
		if ( empty( $form_id ) ) {
			$form_id = GravityView_View::getInstance()->getFormId();
		}

		$entry_meta = gravity_flow()->get_entry_meta( [], $form_id );

		return (array) \GV\Utils::get( $entry_meta, $status_key . '/filter/choices' );
	}

	/**
	 * Add the current status timestamp field to available Advanced Filters.
	 */
	public function maybe_add_non_default_filter_fields( $fields, $view_id ) {
		if ( ( $insert_at = array_search( 'workflow_final_status', wp_list_pluck( $fields, 'key' ) ) ) !== false ) {
			$fields_end = array_splice( $fields, $insert_at + 1 );

			$fields[] = [
				'text'            => __( 'Workflow Current Status Timestamp', 'gk-gravityview' ),
				'operators'       => [ '>', '<' ],
				'placeholder'     => 'yyyy-mm-dd',
				'cssClass'        => 'datepicker ymd_dash',
				'key'             => 'workflow_current_status_timestamp',
				'preventMultiple' => false,
			];

			$fields = array_merge( $fields, $fields_end );
		}

		return $fields;
	}

	/**
	 * Add the current status timestamp field to available View configuration fields.
	 */
	public function maybe_add_non_default_fields( $fields, $form, $zone ) {
		if ( false !== strpos( implode( ' ', array_keys( $fields ) ), 'workflow' ) ) {
			$keys   = array_keys( $fields );
			$values = array_values( $fields );

			if ( ( $insert_at = array_search( 'workflow_final_status', $keys ) ) !== false ) {
				$keys_end   = array_splice( $keys, $insert_at + 1 );
				$values_end = array_splice( $values, $insert_at + 1 );

				$timestamp = GravityView_Fields::get( 'workflow_current_status_timestamp' );

				// If the field isn't found for some reason, don't add it.
				if ( ! $timestamp ) {
					return $fields;
				}

				$keys[]   = $timestamp->entry_meta_key;
				$values[] = [
					'label' => $timestamp->label,
					'type'  => $timestamp->name,
					'icon'  => $timestamp->icon,
				];

				$fields = array_combine( $keys, $values ) + array_combine( $keys_end, $values_end );
			}
		}

		return $fields;
	}
}

new GravityView_Plugin_Hooks_Gravity_Flow();
