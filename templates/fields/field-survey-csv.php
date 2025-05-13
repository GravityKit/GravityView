<?php
/**
 * The default survey field output template for CSVs.
 *
 * @global \GV\Template_Context $gravityview
 * @since TBD
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

/** @var \GV\Field $field */
$field         = $gravityview->field;
$display_value = $gravityview->display_value;
$input_id      = gravityview_get_input_id_from_id( $field->ID );
$form_id       = $gravityview->view->form->ID;
$value         = $gravityview->value;

// Backward compatibility for the `score` field setting checkbox before migrating to `choice_display` radio
$default_display = $field->score ? 'score' : 'text';

$choice_display = \GV\Utils::get( $field, 'choice_display', $default_display );
$test = get_option('test554');

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
		// Force the non-multirow fields into the same formatting (row:column)
		$raw_value = is_array( $value ) ? $value : array( $field->ID => ':' . $value );

		$output_values = array();
		foreach ( $raw_value as $row => $row_values ) {
			list( $_likert_row, $row_value ) = array_pad( explode( ':', $row_values ), 2, '' );

			// If we're displaying a single row, don't include other row values
			if ( $input_id && $row !== $field->ID ) {
				continue;
			}

			switch ( $choice_display ) {
				case 'score':
					$output_values[] = GravityView_Field_Survey::get_choice_score( $field->field, $row_value, $row );
					break;
				case 'text':
				default:
					$output_values[] = RGFormsModel::get_choice_text( $field->field, $row_value, $row );
					break;
			}
		}

		/**
		 * The value used to separate multiple values in the CSV export.
		 * Must include a space after the semicolon to prevent CSV format issues.
		 * Without the space, CSV parsers may interpret the values incorrectly and create
		 * unwanted extra columns when displaying multiple Likert field values.
		 *
		 * @since TBD
		 *
		 * @param string The glue. Default: "; " (semicolon with space)
		 * @param \GV\Template_Context The context.
		 */
		$glue = apply_filters( 'gravityview/template/field/csv/glue', '; ', $gravityview );

		// Ensure glue always has a space after semicolon
		if (strpos($glue, '; ') === false && strpos($glue, ';') === 0) {
			$glue = '; ';
		}


		echo implode( $glue, $output_values );
		return; // Return early

	case 'rating':
		$choice_text = RGFormsModel::get_choice_text( $field->field, $value, $input_id );
		echo $choice_text;
		return;
}
