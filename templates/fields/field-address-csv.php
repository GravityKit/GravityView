<?php
/**
 * The default address field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

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

// If it's the full address
if ( ! $is_single_input ) {

	/**
	 * Make sure we're only showing enabled inputs.
	 */
	foreach ( $field->inputs as $input ) {
		if ( ! empty( $input['isHidden'] ) ) {
			unset( $value[ "{$input['id']}" ] );
		}
	}

	add_filter( 'gform_disable_address_map_link', '__return_true' );

	/**
	 * Use Gravity Forms' method to get the full address.
	 */
	$value_with_newline = GFCommon::get_lead_field_display( $field, $value, '', false, 'text' );

	remove_filter( 'gform_disable_address_map_link', '__return_true' );

	$address = explode( "\n", $value_with_newline );

	if ( empty( $address ) ) {
		return;
	}

	/**
	 * The address parts delimiter.
	 *
	 * @since 2.4
	 *
	 * @param string               $delimiter   The delimiter. Default: newline.
	 * @param \GV\Template_Context $gravityview The template context.
	 */
	$delimiter = apply_filters( 'gravityview/template/field/address/csv/delimiter', "\n", $gravityview );

	echo implode( $delimiter, $address );
} else {
	echo esc_html( gravityview_get_field_value( $entry, $field_id, $display_value ) );
}
