<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This function generates the [gravityview] shortcode
 *
 * @param array $attributes
 *                         array['id']     string  The ID of the View you want to display
 *                         array['detail'] string  Display specific information about a View. Valid values are total_entries, first_entry, last_entry, page_size
 *
 * @return string $output
 */
function gv_blocks_render_view_details( $attributes ) {

	$accepted_attributes = array(
		'id',
		'detail',
	);

	foreach ( $attributes as $attribute => $value ) {
		$value = esc_attr( sanitize_text_field( $value ) );

		if ( in_array( $attribute, $accepted_attributes ) && ! empty( $value ) ) {
			$shortcode_attributes[] = "{$attribute}={$value}";
		}
	}

	$shortcode = sprintf( '[gravityview %s]', join( ' ', $shortcode_attributes ) );

	$output = do_shortcode( $shortcode );

	return $output;
}
