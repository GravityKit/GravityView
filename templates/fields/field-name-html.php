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

if ( floatval( $field_id ) != intval( $field_id ) ) {
	echo esc_html( gravityview_get_field_value( $entry, $field_id, $display_value ) );
} else {
	echo gravityview_get_field_value( $entry, $field_id, $display_value );
}
