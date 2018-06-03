<?php
/**
 * Display the website field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

if( !empty( $value ) && function_exists( 'gravityview_format_link' ) ) {
	$value = esc_url_raw( $value );

	/** @since 1.8 */
	$anchor_text = !empty( $field_settings['anchor_text'] ) ? trim( rtrim( $field_settings['anchor_text'] ) ) : false;

	// Check empty again, just in case trim removed whitespace didn't work
	if( !empty( $anchor_text ) ) {

		// Replace the variables
		$anchor_text = GravityView_API::replace_variables( $anchor_text, $form, $entry );

	} else {
		$anchor_text = empty( $field_settings['truncatelink'] ) ? $value : gravityview_format_link( $value );
	}

	$attributes = empty( $field_settings['open_same_window'] ) ? 'target=_blank' : '';

	echo gravityview_get_link( $value, $anchor_text, $attributes );

} else {
	echo esc_html( esc_url_raw( $value ) );
}
