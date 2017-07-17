<?php
/**
 * Address field output, with "Map It" link removed
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 *
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

// If it's the full address
if( floor( $field_id ) === floatval( $field_id ) ) {

	/**
	 * Make sure we're only showing enabled inputs.
	 * @since 1.16.2
	 */
	foreach( $field->inputs as $input ) {
		if( ! empty( $input['isHidden'] ) ) {
			unset( $value["{$input['id']}"] );
		}
	}

	/** We shall output the map ourselves for now, suppress the output here. */
	add_filter( 'gform_disable_address_map_link', '__return_true' );

	// Use Gravity Forms' method to get the full address.
	$value_with_newline = GFCommon::get_lead_field_display( $field, $value, "", false, 'html' );

	remove_filter( 'gform_disable_address_map_link', '__return_true' );

	if( empty( $value_with_newline ) ) { return; }

	// Add map link if it's not set (default, back compat) or if it's set to yes
	if( !isset( $field_settings['show_map_link'] ) || !empty( $field_settings['show_map_link'] ) ){

		// Add the map link as another line
	    $value_with_newline .= "\n" . gravityview_get_map_link( $value_with_newline );

	}

	// Full address without the "Map It" link
	echo str_replace("\n", '<br />', $value_with_newline );

} else {

	echo esc_html( gravityview_get_field_value( $entry, $field_id, $display_value ) );

}
