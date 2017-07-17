<?php
/**
 * Display the product field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 *
 * @global string $display_value Existing value
 * @global string|array $value
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

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

	switch ( $input_id ) {
		case 2:
			$output = GFCommon::to_money( $output, rgar( $entry, 'currency' ) );
			break;
		case 3:
			$output = GFCommon::to_number( $output );
			break;
	}

	echo $output;
}
