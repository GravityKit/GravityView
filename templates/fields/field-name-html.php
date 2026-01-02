<?php
/**
 * The default name field output template.
 *
 * @global Template_Context $gravityview
 * @since 2.0
 */

use GV\Template_Context;

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field_id        = $gravityview->field->ID;
$field           = $gravityview->field->field;
$value           = $gravityview->value;
$display_value   = $gravityview->display_value;
$entry           = $gravityview->entry->as_entry();
$field_settings  = $gravityview->field->as_configuration();
$is_single_input = floor( $field_id ) !== floatval( $field_id );

if ( $is_single_input ) {
	// Single input (e.g., just First Name): get the specific entry value.
	$display_value = esc_html( gravityview_get_field_value( $entry, $field_id, $display_value ) );
} else {
	// Full name field: filter out hidden inputs based on GF field settings.
	if ( is_array( $field->inputs ) ) {
		foreach ( $field->inputs as $input ) {
			if ( ! empty( $input['isHidden'] ) ) {
				unset( $value[ "{$input['id']}" ] );
			}
		}
	}

	$display_value = GFCommon::get_lead_field_display( $field, $value, '', false, 'html' );

	if ( empty( $display_value ) ) {
		return;
	}
}

if ( ! empty( $field_settings['show_as_initials'] ) ) {
	$display_value = GravityView_Field_Name::convert_to_initials( $display_value );
}

/**
 * Overrides the Name field display value.
 *
 * @filter `gk/gravityview/field/name/display`
 *
 * @since  2.29.0
 *
 * @param string           $display_value Name or initials to display.
 * @param Template_Context $gravityview   The GravityView template context.
 */
echo apply_filters( 'gk/gravityview/field/name/display', $display_value, $gravityview );
