<?php
/**
 * The default checkbox field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */
$field_id = $gravityview->field->ID;
$field = $gravityview->field->field;
$value = $gravityview->value;
$form = $gravityview->view->form->form;
$display_value = $gravityview->display_value;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

$is_single_input = floor( $field_id ) !== floatval( $field_id );

$output = '';

if ( ! $is_single_input ) {
	$output = gravityview_get_field_value( $entry, $field_id, $display_value );
} else {

	$display_type = \GV\Utils::get( $field_settings, 'choice_display' );

	$field_value = gravityview_get_field_value( $entry, $field_id, $display_value );

	switch ( $display_type ) {
		case 'value':
			$output = $field_value;
			break;
		case 'label':
			$output = gravityview_get_field_label( $form, $field_id, $value );
			break;
		case 'tick':
		default: // Backward compatibility
			if ( '' !== $field_value ) {
				/**
				 * @filter `gravityview_field_tick` Change the output for a checkbox "check" symbol. Default is the "dashicons-yes" icon
				 * @see https://developer.wordpress.org/resource/dashicons/#yes
				 *
				 * @param string $output HTML span with `dashicons dashicons-yes` class
				 * @param array $entry Gravity Forms entry array
				 * @param array $field GravityView field array
				 *
				 * @since 2.0
				 * @param \GV\Template_Context The template context.
				 */
				$output = apply_filters( 'gravityview_field_tick', '<span class="dashicons dashicons-yes"></span>', $entry, $field, $gravityview );
			}
			break;
	}

}

echo $output;
