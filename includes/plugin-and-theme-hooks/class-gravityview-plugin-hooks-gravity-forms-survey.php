<?php
/**
 * Customization for the Gravity Forms Survey Addon
 *
 * @file      class-gravityview-plugin-hooks-gravity-forms-survey.php
 * @package   GravityView
 * @license   GPL2
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 1.17
 */

/**
 * @inheritDoc
 * @since 1.17
 */
class GravityView_Plugin_Hooks_Gravity_Forms_Survey extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @type string Optional. Constant that should be defined by plugin or theme. Used to check whether plugin is active.
	 * @since 1.17
	 */
	protected $constant_name = 'GF_SURVEY_VERSION';

	protected function add_hooks() {
		add_filter( 'gravityview/edit_entry/form_fields', array( $this, 'fix_survey_fields' ), 10 );
		add_action( 'gravityview/edit-entry/render/before', array( $this, 'add_render_hooks' ) );
		add_action( 'gravityview/edit-entry/render/after', array( $this, 'remove_render_hooks' ) );

		add_filter( 'gravityview/extension/search/input_type', array( $this, 'modify_search_bar_input_type' ), 10, 2 );

	}

	/**
	 * Modify the search form input type for survey fields
	 *
	 * @since 1.17.3
	 *
	 * @param string $input_type Assign an input type according to the form field type. Defaults: `boolean`, `multi`, `select`, `date`, `text`
	 * @param string $field_type Gravity Forms field type (also the `name` parameter of GravityView_Field classes)
	 */
	function modify_search_bar_input_type( $input_type = 'text', $field_type = '' ) {

		$return = $input_type;

		if( 'survey' === $field_type ) {
			$return = 'select';
		}

		return $return;
	}

	/**
	 * Make sure Survey fields accept pre-populating values; otherwise existing values won't be filled-in
	 *
	 * @since 1.16.4
	 * @since 1.17 Moved to GravityView_Plugin_Hooks_Gravity_Forms_Survey class
	 *
	 * @param array $form
	 *
	 * @return array Form, with all fields set to `allowsPrepopulate => true`
	 */
	public function fix_survey_fields( $fields ) {

		/** @var GF_Field $field */
		foreach( $fields as &$field ) {
			if( 'survey' === $field->type ) {
				$field->allowsPrepopulate = true;
			}
		}

		return $fields;
	}

	/**
	 * Add filters before rendering the Edit Entry form
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	function add_render_hooks() {
		add_filter( 'gform_field_value', array( $this, 'fix_survey_field_value'), 10, 3 );
	}

	/**
	 * Remove the hooks added before rendering Edit Entry form
	 *
	 * @see add_render_hooks
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	function remove_render_hooks() {
		remove_filter( 'gform_field_value', array( $this, 'fix_survey_field_value'), 10 );
	}

	/**
	 * Survey fields inject their output using `gform_field_input` filter, but in Edit Entry, the values were empty.
	 * We filter the values here because it was the easiest access point: tell the survey field the correct value, GF outputs it.
	 *
	 * @since 1.16.4
	 * @since 1.17 Moved to GravityView_Plugin_Hooks_Gravity_Forms_Survey class
	 *
	 * @param string $value Existing value
	 * @param GF_Field $field
	 * @param string $name Field custom parameter name, normally blank.
	 *
	 * @return mixed
	 */
	public function fix_survey_field_value( $value, $field, $name ) {

		if( 'survey' === $field->type ) {

			$entry = GravityView_Edit_Entry::getInstance()->instances['render']->get_entry();

			// We need to run through each survey row until we find a match for expected values
			foreach ( $entry as $field_id => $field_value ) {

				if ( floor( $field_id ) !== floor( $field->id ) ) {
					continue;
				}

				if( rgar( $field, 'gsurveyLikertEnableMultipleRows' ) ) {
					list( $row_val, $col_val ) = explode( ':', $field_value, 2 );

					// If the $name matches the $row_val, we are processing the correct row
					if( $row_val === $name ) {
						$value = $field_value;
						break;
					}
				} else {
					// When not processing multiple rows, the value is the $entry[ $field_id ] value.
					$value = $field_value;
					break;
				}
			}
		}

		return $value;
	}

}

new GravityView_Plugin_Hooks_Gravity_Forms_Survey;