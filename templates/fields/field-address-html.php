<?php
/**
 * The default address field output template.
 *
 * @since future
 */
$field_id = $gravityview->field->ID;
$field = $gravityview->field->field;
$value = $gravityview->value;
$display_value = $gravityview->display_value;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

// If it's the full address
if ( floor( $field_id ) === floatval( $field_id ) ) {

	/**
	 * Make sure we're only showing enabled inputs.
	 */
	foreach ( $field->inputs as $input ) {
		if ( ! empty( $input['isHidden'] ) ) {
			unset( $value["{$input['id']}"] );
		}
	}

	/**
	 * Add map link if it's not set (default, back compat) or if it's set to yes
	 */
	if ( isset( $field_settings['show_map_link'] ) && ! $field_settings['show_map_link'] ) {
		/** Add the map link as another line. */
		add_filter( 'gform_disable_address_map_link', '__return_true' );
		$map_disabled = true;
	}

	/**
	 * Use Gravity Forms' method to get the full address.
	 */
	$value_with_newline = GFCommon::get_lead_field_display( $field, $value, "", false, 'html' );

	if ( ! empty( $map_disabled ) ) {
		remove_filter( 'gform_disable_address_map_link', '__return_true' );
	}

	if ( empty( $value_with_newline ) ) { return; }

	// Full address without the "Map It" link
	echo str_replace( "\n", '<br />', $value_with_newline );

} else {
	echo esc_html( gravityview_get_field_value( $entry, $field_id, $display_value ) );
}
