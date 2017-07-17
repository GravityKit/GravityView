<?php
/**
 * Checkbox field output
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 *
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

$is_single_input = floor( $field_id ) !== floatval( $field_id );

$output = '';

if( ! $is_single_input ) {
	$output = gravityview_get_field_value( $entry, $field_id, $display_value );
} else {

	$display_type = rgar( $field_settings, 'choice_display' );

	$field_value = gravityview_get_field_value( $entry, $field_id, $display_value );

	switch( $display_type ) {
		case 'value':
			$output = $field_value;
			break;
		case 'label':
			$output = gravityview_get_field_label( $form, $field_id, $value );
			break;
		case 'tick':
		default: // Backward compatibility
			if( '' !== $field_value ) {
				/**
				 * @filter `gravityview_field_tick` Change the output for a checkbox "check" symbol. Default is the "dashicons-yes" icon
				 * @see https://developer.wordpress.org/resource/dashicons/#yes
				 *
				 * @param string $output HTML span with `dashicons dashicons-yes` class
				 * @param array $entry Gravity Forms entry array
				 * @param array $field GravityView field array
				 */
				$output = apply_filters( 'gravityview_field_tick', '<span class="dashicons dashicons-yes"></span>', $entry, $field );
			}
			break;
	}

}

echo $output;
