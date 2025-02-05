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

$field_id      = $gravityview->field->ID;
$field         = $gravityview->field->field;
$display_value = $gravityview->display_value;
$entry         = $gravityview->entry->as_entry();


$field_settings = $gravityview->field->as_configuration();

if ( floatval( $field_id ) != intval( $field_id ) ) {
	$display_value = esc_html( gravityview_get_field_value( $entry, $field_id, $display_value ) );
} else {
	$display_value = gravityview_get_field_value( $entry, $field_id, $display_value );
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
