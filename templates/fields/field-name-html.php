<?php
/**
 * The default name field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

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


if ( !empty( $field_settings['only_initials'] ) ) {
	$names = explode( ' ', $display_value );
	$initials = '';

	foreach ( $names as $name ) {
		$initials .= strtoupper( $name[0] ) . '.';
	}

	$display_value = trim($initials);
}

echo apply_filters('gk/gravityview/fields/name/initials', $display_value, $gravityview);