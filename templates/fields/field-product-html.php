<?php
/**
 * The default product field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */
$field_id = $gravityview->field->ID;
$value = $gravityview->value;
$display_value = $gravityview->display_value;
$entry = $gravityview->entry->as_entry();

/**
 * See if there are any values in the product array GF provides. (product name, quantity, price)
 *
 * If not, Gravity Forms displays a useless string (", Qty: , Price:") instead of an empty value. This prevents
 * the "Hide empty fields" setting from working.
 *
 * @since 1.12
 */
$value = is_array( $value ) ? array_filter( $value, 'gravityview_is_not_empty_string' ) : $value;

// If so, then we have something worth showing
if ( !empty( $value ) ) {

	$input_id = gravityview_get_input_id_from_id( $field_id );

	$output = gravityview_get_field_value( $entry, $field_id, $display_value );

	/**
	 * The old format used the store the data as Product|Price|Quantity.
	 * See if we can detect this and save the day.
	 */
	if ( $input_id && ! isset( $entry[ $field_id ] ) ) {
		$value = explode( '|', $value );

		if ( isset( $value[ $input_id - 1 ] ) ) {
			$output = $value[ $input_id - 1 ];
		}
	}

	switch ( $input_id ) {
		case 2:
			$output = GFCommon::to_money( $output, \GV\Utils::get( $entry, 'currency' ) );
			break;
		case 3:
			$output = GFCommon::to_number( $output );
			break;
		default:
			$output = esc_html( $output );
			break;
	}

	echo $output;
}
