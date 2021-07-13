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

/** @var \GF_Field $field */
$field = $gravityview->field;
$display_value = $gravityview->display_value;
$input_id = gravityview_get_input_id_from_id( $field->ID );

// An empty single column
if ( '' === \GV\Utils::get( $gravityview->value, $field->ID ) ) {
	echo '';
	return;
}

$input_value = $gravityview->value;

switch ( $gravityview->field->field->inputType ) {
	case 'text':
	case 'textarea':
	case 'radio':
	case 'rating':
		echo $display_value;
		return;
	case 'checkbox':
		$input_value = gravityview_get_field_value( $gravityview->value, $field->ID, $display_value );
		break;
	case 'likert':
		if ( class_exists( 'GFSurvey' ) && is_callable( array('GFSurvey', 'get_instance') ) ) {
			wp_register_style( 'gsurvey_css', GFSurvey::get_instance()->get_base_url() . '/css/gsurvey.css' );
			wp_print_styles( 'gsurvey_css' );
		}

		// Multiple Row survey fields are formatted with colon-separated information
		if ( $input_id && false !== strpos( $gravityview->value[ $field->ID ], ':' ) ) {
			list( $_likert_row, $input_value ) = explode( ':', $gravityview->value[ $field->ID ] );
		}
		break;
}

$display_values = array();
$break_early = ! $field->gsurveyLikertEnableMultipleRows;
foreach( $field->field->choices as $choice ) {

	if ( $input_id && $input_value !== $choice['value'] ) {
		continue;
	}

	// Back-compatibility with prior 'score' field setting
	if ( ! empty( $field->score ) && isset( $choice['score'] ) ) {
		$display_values[] = $choice['score'];
		continue;
	}

	switch( $field->choice_display ) {
		case 'text':
		case 'label':
			$display_values[] = RGFormsModel::get_choice_text( $field->field, $choice['value'], $input_value );
			break;
		case 'score':
			$display_values[] = $choice['score'];
			break;
		case 'default':
		default:

			// Return early if displaying the parent field, not a single input
			if ( ! $input_id ) {
				echo $display_value;
				return;
			}

			switch( $gravityview->field->field->inputType ) {

				// When displaying a single input, render as if multiple rows were disabled
				case 'likert':
					/** @var GF_Field_Likert $single_input_field */
					$single_input_field                                  = clone $field->field;
					$single_input_field->id                              = $field->ID;
					$single_input_field->gsurveyLikertEnableMultipleRows = false;
					$display_values[]                                    = $single_input_field->get_field_input( $gravityview->form, $choice['value'] );
					break;
				case 'checkbox':
					/**
					 * Filter is defined in /templates/fields/field-checkbox-html.php
					 */
					$display_values[] = apply_filters( 'gravityview_field_tick', '<span class="dashicons dashicons-yes"></span>', $gravityview->entry, $field->as_configuration(), $gravityview );
					break;
				default:
					$display_values[] = $display_value;
					break;
			}
			break;
	}

	if ( $break_early ) {
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

echo implode( $glue, $display_values );
