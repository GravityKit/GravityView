<?php
/**
 * Display the product field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
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
	echo gravityview_get_field_value( $entry, $field_id, $display_value );
}
