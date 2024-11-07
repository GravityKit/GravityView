<?php
/**
 * @file class-gravityview-field-survey.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Survey extends GravityView_Field {

	var $name = 'survey';

	var $_gf_field_class_name = 'GF_Field_Survey';

	var $is_searchable = false;

	var $group = 'advanced';

	var $icon = 'dashicons-forms';

	public function __construct() {
		$this->label = esc_html__( 'Survey', 'gk-gravityview' );

		add_action( 'gravityview/template/field/survey/rating/before', array( __CLASS__, 'output_frontend_css' ) );

		parent::__construct();
	}

	/**
	 * Returns the score for a choice at $value
	 *
	 * A sister method to {@see RGFormsModel::get_choice_text}
	 *
	 * @since 2.11
	 *
	 * @param GF_Field_Likert $field
	 * @param string|array    $value
	 * @param int|string      $input_id ID of the field or input (for example, 7.3 or 7)
	 *
	 * @return mixed|string
	 */
	public static function get_choice_score( $field, $value, $input_id = 0 ) {

		if ( ! $field->gsurveyLikertEnableScoring ) {
			return '';
		}

		if ( ! is_array( $field->choices ) ) {
			return $value;
		}

		foreach ( $field->choices as $choice ) {
			if ( is_array( $value ) && RGFormsModel::choice_value_match( $field, $choice, $value[ $input_id ] ) ) {
				return $choice['score'];
			} elseif ( ! is_array( $value ) && RGFormsModel::choice_value_match( $field, $choice, $value ) ) {
				return $choice['score'];
			}
		}

		return is_array( $value ) ? '' : $value;
	}

	/**
	 * Gets field choices directly from the database (To avoid issues where choices are reversed by the survey plugin).
	 *
	 * @since 2.30.0
	 *
	 * @param int $form_id The ID of the form.
	 * @param int $field_id The ID of the field.
	 * 
	 * @return array The choices for the field.
	 */
	public static function get_field_choices($form_id, $field_id) {
		global $wpdb;
		$table_name = GFFormsModel::get_meta_table_name();
		$form_row   = $wpdb->get_row( $wpdb->prepare( "SELECT display_meta FROM {$table_name} WHERE form_id=%d", $form_id ), ARRAY_A );
		$form_meta 	= GFFormsModel::unserialize( rgar( $form_row, 'display_meta' ) );

		if ( ! $form_meta ) {
			return [];
		}

		foreach ( $form_meta['fields'] as $form_field ) {
			if ( $form_field['id'] == $field_id ) {
				return $form_field['choices'];
			}
		}
		
		return [];
	}


	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		unset( $field_options['search_filter'] );

		if ( 'edit' === $context ) {
			return $field_options;
		}

		$field       = \GV\GF_Field::by_id( \GV\GF_Form::by_id( $form_id ), $field_id );
		$input_id    = gravityview_get_input_id_from_id( $field_id );
		$add_options = array();

		$glue                 = apply_filters( 'gravityview/template/field/survey/glue', '; ' );
		$multiple_rows_suffix = sprintf( _x( ' (separated by %s)', 'text added to a label if multiple rows are enabled for the field)', 'gk-gravityview' ), esc_html( trim( $glue ) ) );

		if ( 'likert' === $field->field->inputType ) {

			$show_suffix = $input_id || empty( $field->field->gsurveyLikertEnableMultipleRows );

			$likert_display_options = array(
				'default' => __( 'A table (default Gravity Forms formatting)', 'gk-gravityview' ),
				'text'    => __( 'Text value of the selected choice', 'gk-gravityview' ) . ( $show_suffix ? '' : $multiple_rows_suffix ),
			);

			if ( $field->field->gsurveyLikertEnableScoring ) {
				$likert_display_options['score'] = __( 'Score value of the selected choice', 'gk-gravityview' ) . ( $show_suffix ? '' : $multiple_rows_suffix );
			}

			// Maintain for back-compatibility
			$add_options['score'] = array(
				'type'  => 'hidden',
				'value' => '',
				'group' => 'display',
			);

			$add_options['choice_display'] = array(
				'type'       => 'radio',
				'label'      => __( 'What should be displayed:', 'gk-gravityview' ),
				'options'    => $likert_display_options,
				'desc'       => '',
				'group'      => 'display',
				'class'      => 'block',
				'value'      => 'default',
				'merge_tags' => false,
			);
		}

		if ( 'checkbox' === $field->field->inputType && $input_id ) {
			$field_options['choice_display'] = array(
				'type'     => 'radio',
				'class'    => 'vertical',
				'label'    => __( 'What should be displayed:', 'gk-gravityview' ),
				'value'    => 'tick',
				'desc'     => '',
				'choices'  => array(
					'tick' => __( 'A check mark, if the input is checked', 'gk-gravityview' ),
					'text' => __( 'Text value of the selected choice', 'gk-gravityview' ),
				),
				'group'    => 'display',
				'priority' => 100,
			);
		}

		if ( 'rating' === $field->field->inputType ) {
			$field_options['choice_display'] = array(
				'type'     => 'radio',
				'class'    => 'vertical',
				'label'    => __( 'What should be displayed:', 'gk-gravityview' ),
				'value'    => 'default',
				'desc'     => '',
				'choices'  => array(
					'default' => __( 'Text value of the selected choice', 'gk-gravityview' ),
					'stars'   => __( 'Stars (default Gravity Forms formatting)', 'gk-gravityview' ),
				),
				'group'    => 'display',
				'priority' => 100,
			);
		}

		return $add_options + $field_options;
	}


	/**
	 * Output CSS for star ratings.
	 *
	 * @since 2.16
	 *
	 * @return void
	 */
	static function output_frontend_css() {

		static $did_output;

		// Only output once.
		if ( $did_output ) {
			return;
		}

		$star0 = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-1107 844.2 51.6 51.6"><style type="text/css">.st0{fill:#EEEEEE;}.st1{fill:#CCCCCC;}</style><circle class="st0" cx="-1081.2" cy="870" r="17.9"/><path class="st1" d="M-1081.2 895.8c-14.2 0-25.8-11.5-25.8-25.8s11.6-25.8 25.8-25.8c14.2 0 25.8 11.6 25.8 25.8S-1066.9 895.8-1081.2 895.8zM-1081.2 849.2c-11.5 0-20.8 9.3-20.8 20.8s9.3 20.8 20.8 20.8 20.8-9.3 20.8-20.8S-1069.7 849.2-1081.2 849.2z"/><path class="st1" d="M-1076.4 871.8l4.8-4.6 -6.6-1 -3-6 -3 6 -6.6 1 4.8 4.6 -1.1 6.6 5.9-3.1 5.9 3.1L-1076.4 871.8zM-1068.2 866.2c0 0.2-0.1 0.5-0.4 0.8l-5.7 5.5 1.3 7.8c0 0.1 0 0.2 0 0.3 0 0.5-0.2 0.8-0.6 0.8 -0.2 0-0.4-0.1-0.6-0.2l-7-3.7 -7 3.7c-0.2 0.1-0.4 0.2-0.6 0.2 -0.2 0-0.4-0.1-0.5-0.2s-0.2-0.3-0.2-0.6c0-0.1 0-0.2 0-0.3l1.3-7.8 -5.7-5.5c-0.3-0.3-0.4-0.5-0.4-0.8 0-0.4 0.3-0.6 0.9-0.7l7.8-1.1 3.5-7.1c0.2-0.4 0.5-0.6 0.8-0.6 0.3 0 0.6 0.2 0.8 0.6l3.5 7.1 7.8 1.1C-1068.5 865.6-1068.2 865.8-1068.2 866.2L-1068.2 866.2z"/></svg>';
		$star1 = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-1107 844.2 51.6 51.6"><style type="text/css">.st0{fill:#EEEEEE;}.st1{fill:#CCCCCC;}.st2{fill:#FF9800;}</style><circle class="st0" cx="-1081.2" cy="870" r="17.9"/><path class="st1" d="M-1081.2 895.8c-14.2 0-25.8-11.5-25.8-25.8s11.6-25.8 25.8-25.8c14.2 0 25.8 11.6 25.8 25.8S-1066.9 895.8-1081.2 895.8zM-1081.2 849.2c-11.5 0-20.8 9.3-20.8 20.8s9.3 20.8 20.8 20.8 20.8-9.3 20.8-20.8S-1069.7 849.2-1081.2 849.2z"/><path class="st2" d="M-1068.2 866.3c0 0.2-0.1 0.5-0.4 0.8l-5.7 5.5 1.3 7.8c0 0.1 0 0.2 0 0.3 0 0.2-0.1 0.4-0.2 0.6 -0.1 0.2-0.3 0.2-0.5 0.2 -0.2 0-0.4-0.1-0.6-0.2l-7-3.7 -7 3.7c-0.2 0.1-0.4 0.2-0.6 0.2 -0.2 0-0.4-0.1-0.5-0.2 -0.1-0.2-0.2-0.3-0.2-0.6 0-0.1 0-0.2 0-0.3l1.3-7.8 -5.7-5.5c-0.3-0.3-0.4-0.5-0.4-0.8 0-0.4 0.3-0.6 0.9-0.7l7.8-1.1 3.5-7.1c0.2-0.4 0.5-0.6 0.8-0.6 0.3 0 0.6 0.2 0.8 0.6l3.5 7.1 7.8 1.1C-1068.5 865.7-1068.2 865.9-1068.2 866.3L-1068.2 866.3z"/></svg>';

		?>
		<style>
			.gv-field-survey-star-filled,
			.gv-field-survey-star-empty {
				width: 18px;
				height: 18px;
				display: inline-block;
				background: transparent url( 'data:image/svg+xml;base64,<?php echo base64_encode( $star0 ); ?>') left top no-repeat;
				background-size: contain;
			}
			.gv-field-survey-star-filled {
				background-image: url( 'data:image/svg+xml;base64,<?php echo base64_encode( $star1 ); ?>');
			}
			.gv-field-survey-screen-reader-text {
				border: 0;
				clip: rect(0 0 0 0);
				clip-path: inset(50%);
				height: 1px;
				margin: -1px;
				overflow: hidden;
				padding: 0;
				position: absolute;
				width: 1px;
				white-space: nowrap;
			}
			.gv-field-survey-screen-reader-text.focusable {
			.gv-field-survey-screen-reader-text:active,
			.gv-field-survey-screen-reader-text:focus {
				clip: auto;
				clip-path: none;
				height: auto;
				margin: 0;
				overflow: visible;
				position: static;
				width: auto;
				white-space: inherit;
			}
			}
		</style>
		<?php

		$did_output = true;
	}
}

new GravityView_Field_Survey();
