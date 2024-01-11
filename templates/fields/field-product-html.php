<?php
/**
 * The default product field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field_id      = $gravityview->field->ID;
$value         = $gravityview->value;
$display_value = $gravityview->display_value;
if ( '' == $display_value ) {
	$display_value = $value;
}
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
if ( ! empty( $value ) ) {
	$input_id = gravityview_get_input_id_from_id( $field_id );

	/**
	 * If a product has 0 quantity, don't output any of it.
	 * https://github.com/gravityview/GravityView/issues/1263
	 *
	 * @since develop
	 */
	$hide_empty_products = ! in_array( $gravityview->field->inputType, array( 'select', 'radio', 'price', 'hidden' ) );

	if ( $hide_empty_products && $gravityview->view->settings->get( 'hide_empty' ) ) {
		$_field_id = intval( $field_id );

		$quantity_found = false;

		foreach ( $gravityview->fields->all() as $_field ) {
			if ( 'quantity' == $_field->type ) {
				if ( $_field->productField == $_field_id ) {
					$quantity_found = ! empty( $entry[ $_field->ID ] );
					break;
				}
			}
		}

		if ( ! $quantity_found && ! $gravityview->field->disableQuantity && empty( $entry[ "$_field_id.3" ] ) ) {
			return;
		}
	}

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
