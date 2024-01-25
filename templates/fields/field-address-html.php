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

	/**
	 * Disable internal Gravity Forms map link.
	 * Use our own legacy code and filter.
	 */
	add_filter( 'gform_disable_address_map_link', '__return_true' );

	/**
	 * Use Gravity Forms' method to get the full address.
	 */
	$value_with_newline = GFCommon::get_lead_field_display( $field, $value, '', false, 'html' );

	remove_filter( 'gform_disable_address_map_link', '__return_true' );

	if ( empty( $value_with_newline ) ) {
		return; }

	/**
	 * Add map link if it's not set (default, back compat) or if it's set to yes
	 */
	if ( $gravityview->field->show_map_link ) {
		$atts = array();

		if ( $gravityview->field->show_map_link_new_window ) {
			$atts['target'] = '_blank';
		}

		/** Add the map link as another line. */
		$value_with_newline = "$value_with_newline\n" . gravityview_get_map_link( $value_with_newline, $atts );
	}

	echo str_replace( "\n", '<br />', $value_with_newline );

} else {
	echo esc_html( gravityview_get_field_value( $entry, $field_id, $display_value ) );
}
