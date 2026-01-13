<?php
/**
 * The default checkbox field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field_id       = $gravityview->field->ID;
$field          = $gravityview->field->field;
$value          = $gravityview->value;
$form           = $gravityview->view->form->form;
$display_value  = $gravityview->display_value;
$entry          = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

$is_single_input = floor( $field_id ) !== floatval( $field_id );

$output = '';

$display_type   = \GV\Utils::get( $field_settings, 'choice_display' );
$display_format = \GV\Utils::get( $field_settings, 'display_format', 'default' );

// It's the parent field, not an input.
if ( ! $is_single_input ) {
	if ( 'csv' === $display_format ) {
		// Use helper method for CSV formatting.
		$show_label = ( 'label' === $display_type );
		$output     = GravityView_Field_Checkbox::format_checkbox_csv( $value, $field, $show_label, $entry, $gravityview );
	} elseif ( 'label' === $display_type ) {
		// Use standard GF formatting (bulleted list).
		$output = $field->get_value_entry_detail( $value, '', true );
	} else {
		$output = gravityview_get_field_value( $entry, $field_id, $display_value );
	}
} else {

	$field_value = gravityview_get_field_value( $entry, $field_id, $display_value );

	switch ( $display_type ) {
		case 'value':
			$output = $field_value;
			break;
		case 'label':
			$output = gravityview_get_field_label( $form, $field_id, $value );
			break;
		case 'tick':
		default: // Backward compatibility.
			if ( '' !== $field_value ) {
				/**
				 * Change the output for a checkbox "check" symbol. Default is the "dashicons-yes" icon.
				 *
				 * @since 1.0-beta
				 * @since 2.0 Added $gravityview parameter.
				 *
				 * @see https://developer.wordpress.org/resource/dashicons/#yes
				 *
				 * @param string               $output      HTML span with `dashicons dashicons-yes` class.
				 * @param array                $entry       Gravity Forms entry array.
				 * @param array                $field       GravityView field array.
				 * @param \GV\Template_Context $gravityview The template context.
				 */
				$output = apply_filters( 'gravityview_field_tick', '<span class="dashicons dashicons-yes"></span>', $entry, $field, $gravityview );
			}
			break;
	}
}

// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped by GF or our helper method
echo $output;
