<?php
/**
 * The default survey field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

/** @var \GV\Field $field */
$field = $gravityview->field;
$display_value = $gravityview->display_value;
$input_id = gravityview_get_input_id_from_id( $field->ID );

// Backward compatibility for the `score` field setting checkbox before migrating to `choice_display` radio
$default_display = $field->score ? 'score' : 'default';

$choice_display = \GV\Utils::get( $field, 'choice_display', $default_display );

switch ( $gravityview->field->field->inputType ) {
	case 'text':
	case 'textarea':
	case 'radio':
	case 'rank':
	case 'select':
	default:
		echo $display_value;
		return;  // Return early
	case 'checkbox':

		// Display the <ul>
		if ( ! $input_id ) {
			echo $display_value;
			return;
		}

		if ( 'tick' === $choice_display || 'default' === $choice_display ) {
			/**
			 * Filter is defined in /templates/fields/field-checkbox-html.php
			 */
			echo apply_filters( 'gravityview_field_tick', '<span class="dashicons dashicons-yes"></span>', $gravityview->entry, $field->as_configuration(), $gravityview );

			return; // Return early
		}

		echo RGFormsModel::get_choice_text( $field->field, $gravityview->value, $field->ID );

		return; // Return early
	case 'likert':

		if ( class_exists( 'GFSurvey' ) && is_callable( array('GFSurvey', 'get_instance') ) ) {
			wp_register_style( 'gsurvey_css', GFSurvey::get_instance()->get_base_url() . '/css/gsurvey.css' );
			wp_print_styles( 'gsurvey_css' );
		}

		// Gravity Forms-generated Likert table output
		if ( 'default' === $choice_display || empty( $choice_display ) ) {

			// Default is the likert table; show it and return early.
			if( $field->field->gsurveyLikertEnableMultipleRows && ! $input_id ) {
				echo $display_value;
				return;  // Return early
			}
		}

		// Force the non-multirow fields into the same formatting (row:column)
		$raw_value = is_array( $gravityview->value ) ? $gravityview->value : array( $field->ID => ':' . $gravityview->value );

		$output_values = array();
		foreach( $raw_value as $row => $row_values ) {
			list( $_likert_row, $row_value ) = array_pad( explode( ':', $row_values ), 2, '' );

			// If we're displaying a single row, don't include other row values
			if ( $input_id && $row !== $field->ID ) {
				continue;
			}

			switch( $choice_display ) {
				case 'score':
					$output_values[] = GravityView_Field_Survey::get_choice_score( $field->field, $row_value, $row );
					break;
				case 'text':
					$output_values[] = RGFormsModel::get_choice_text( $field->field, $row_value, $row );
					break;
				case 'default':
				default:
					// When displaying a single input, render as if multiple rows were disabled
					/** @var GF_Field_Likert $single_input_field */
					$single_input_field                                  = clone $field->field;
					$single_input_field->id                              = $field->ID;
					$single_input_field->gsurveyLikertEnableMultipleRows = false;
					$output_values[] = $single_input_field->get_field_input( array( 'id' => $field->form_id ), $row_value );
					break;
			}
		}

		/**
		 * @filter `gravityview/template/field/survey/glue` The value used to separate multiple values in the Survey field output
		 * @since 2.10.4
		 *
		 * @param[in,out] string The glue. Default: "; " (semicolon with a trailing space)
		 * @param \GV\Template_Context The context.
		 */
		$glue = apply_filters( 'gravityview/template/field/survey/glue', '; ', $gravityview );

		echo implode( $glue, $output_values );

		return; // Return early

	case 'rating':

		if( 'stars' === $choice_display ) {

			// Don't use __return_true because other code may also be using it.
			$return_true = function() { return true; };

			// Disable the stars from being clickable
			add_filter( 'gform_is_form_editor', $return_true, 10000 );

			/** @see GF_Field_Rating::get_field_input() */
			echo $field->field->get_field_input( array( 'id' => $field->form_id ), $gravityview->value );

			remove_filter( 'gform_is_form_editor', $return_true );

			return; // Return early
		}

		echo RGFormsModel::get_choice_text( $field->field, $gravityview->value, $input_id );

		return;
}
