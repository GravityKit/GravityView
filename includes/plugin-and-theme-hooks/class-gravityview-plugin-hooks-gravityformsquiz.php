<?php
/**
 * Add Gravity Forms Quiz customizations
 *
 * @file      class-gravityview-plugin-hooks-gravityformsquiz.php
 * @since     1.17
 * @license   GPL2
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

/**
 * @inheritDoc
 * @since 1.17
 */
class GravityView_Plugin_Hooks_Gravity_Forms_Quiz extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.17
	 */
	protected $constant_name = 'GF_QUIZ_VERSION';

	/**
	 * @since 1.17
	 */
	protected function add_hooks() {
		parent::add_hooks();

		add_filter( 'gravityview/common/get_form_fields', array( $this, 'add_form_fields' ), 10, 3 );
	}

	/**
	 * If a form has quiz fields, add the fields to the field picker
	 *
	 * @since 1.17
	 *
	 * @param array $fields               Associative array of fields, with keys as field type
	 * @param array $form                 GF Form array
	 * @param bool  $include_parent_field Whether to include the parent field when getting a field with inputs
	 *
	 * @return array $fields with quiz fields added, if exist. Unmodified if form has no quiz fields.
	 */
	function add_form_fields( $fields = array(), $form = array(), $include_parent_field = true ) {

		$quiz_fields = GFAPI::get_fields_by_type( $form, 'quiz' );

		if ( ! empty( $quiz_fields ) ) {

			$fields['gquiz_score']   = array(
				'label' => __( 'Quiz Score Total', 'gk-gravityview' ),
				'type'  => 'quiz_score',
				'desc'  => __( 'Displays the number of correct Quiz answers the user submitted.', 'gk-gravityview' ),
				'icon'  => 'dashicons-forms',
			);
			$fields['gquiz_percent'] = array(
				'label' => __( 'Quiz Percentage Grade', 'gk-gravityview' ),
				'type'  => 'quiz_percent',
				'desc'  => __( 'Displays the percentage of correct Quiz answers the user submitted.', 'gk-gravityview' ),
				'icon'  => 'dashicons-forms',
			);
			$fields['gquiz_grade']   = array(
				/* translators: This is a field type used by the Gravity Forms Quiz Addon. "A" is 100-90, "B" is 89-80, "C" is 79-70, etc.  */
				'label' => __( 'Quiz Letter Grade', 'gk-gravityview' ),
				'type'  => 'quiz_grade',
				'desc'  => __( 'Displays the Grade the user achieved based on Letter Grading configured in the Quiz Settings.', 'gk-gravityview' ),
				'icon'  => 'dashicons-forms',
			);
			$fields['gquiz_is_pass'] = array(
				'label' => __( 'Quiz Pass/Fail', 'gk-gravityview' ),
				'type'  => 'quiz_is_pass',
				'desc'  => __( 'Displays either Passed or Failed based on the Pass/Fail settings configured in the Quiz Settings.', 'gk-gravityview' ),
				'icon'  => 'dashicons-forms',
			);

		}

		return $fields;
	}
}

new GravityView_Plugin_Hooks_Gravity_Forms_Quiz();
