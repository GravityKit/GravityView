<?php
/**
 * The default Chained Select field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field_value = gravityview_get_field_value( $gravityview->entry->as_entry(), $gravityview->field->ID, $gravityview->display_value );

// The Chained Selects add-on is not active. This is a rudimentary fallback.
if ( is_array( $field_value ) ) {
	$field_value = array_filter( $field_value, 'gravityview_is_not_empty_string' );

	echo implode( '; ', $field_value );
} else {
	echo $field_value;
}
