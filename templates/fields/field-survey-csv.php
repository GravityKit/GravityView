<?php
/**
 * The default survey field output template for CSVs.
 *
 * @since TBD
 *
 * @global \GV\Template_Context $gravityview
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', [ 'file' => __FILE__ ] );

	return;
}

/** @var \GV\Field $field */
$field         = $gravityview->field;
$display_value = $gravityview->display_value;
$input_id      = gravityview_get_input_id_from_id( $field->ID );
$form_id       = $gravityview->view->form->ID;
$value         = $gravityview->value;
$field_value   = gravityview_get_field_value( $gravityview->entry, $field->ID, $display_value );

// Backward compatibility for the `score` field setting checkbox before migrating to `choice_display` radio.
$default_display = $field->score ? 'score' : 'text';

$choice_display = \GV\Utils::get( $field, 'choice_display', $default_display );

switch ( $gravityview->field->field->inputType ) {
	case 'text':
	case 'textarea':
	case 'select':
	default:
		// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $display_value;

		return;
	case 'rank':
		if ( empty( $field_value ) || empty( $display_value ) ) {
			return;
		}

		$choices = [];

		if ( ! empty( $field->field->choices ) ) {
			foreach ( $field->field->get_ordered_choices( $value ) as $choice ) {
				$choices[] = trim( $choice['text'] );
			}
		}

		// Number the items.
		$formatted_items = [];
		$i               = 1;

		foreach ( $choices as $item ) {
			if ( ! empty( $item ) ) {
				$formatted_items[] = $i . '. ' . trim( $item );
				++$i;
			}
		}

		// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo implode( ', ', $formatted_items );

		return;

	case 'radio':
		// For radio fields, we want the plain text value.
		// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo RGFormsModel::get_choice_text( $field->field, $value, $field->ID );

		return;

	case 'checkbox':
		if ( empty( $field_value ) || empty( $display_value ) ) {
			return;
		}

		if ( 'tick' === $choice_display || 'default' === $choice_display ) {
			/**
			 * Filter to customize the check symbol used in CSV exports
			 *
			 * @since TBD
			 *
			 * @param string               $output_symbol The symbol to use for checked values. Default: "✓"
			 * @param array                $entry         The entry being displayed
			 * @param array                $field_config  Field configuration
			 * @param \GV\Template_Context $gravityview   Template context
			 */
			echo apply_filters( 'gravityview_field_tick', '✓', $gravityview->entry, $field->as_configuration(), $gravityview ); // @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			return;
		}

		$choices = [];

		foreach ( $field->field->choices as $choice ) {
			// Only include checked choices.
			$choice_value = $choice['value'];

			if ( is_array( $value ) && in_array( $choice_value, $value, true ) ) {
				$choices[] = trim( $choice['text'] );
			}
		}

		// Output with comma and space separation for CSV.
		// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo implode( ', ', $choices );

		return;

	case 'likert':
		// Force the non-multirow fields into the same formatting (row:column).
		$raw_value = is_array( $value ) ? $value : [ $field->ID => ':' . $value ];

		$output_values = [];

		foreach ( $raw_value as $row => $row_values ) {
			[ $_likert_row, $row_value ] = array_pad( explode( ':', $row_values ), 2, '' );

			// If we're displaying a single row, don't include other row values.
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

		// Ensure glue always has a space after semicolon.
		if ( false === strpos( $glue, '; ' ) && 0 === strpos( $glue, ';' ) ) {
			$glue = '; ';
		}

		// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo implode( $glue, $output_values );

		return;
	case 'rating':
		// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo RGFormsModel::get_choice_text( $field->field, $value, $input_id );

		return;
}
