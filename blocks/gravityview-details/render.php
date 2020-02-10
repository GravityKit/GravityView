<?php
if ( ! function_exists( 'gravityview_block_render_gravityview_details' ) ) {
	return;
}

/**
 * This function generates the gravityview shortcode.
 *
 * @param array $attributes
 *                         array['id']     string  The ID of the View you want to display
 *                         array['detail']          string  Display specific information about a View. Valid values are total_entries, first_entry, last_entry, page_size
 *
 * @return string $output
 */
function gravityview_block_render_gravityview_details( $attributes ) {
	
	$shortcode = '[gravityview ';
	
	if ( ! empty( $attributes['id'] ) ) {
		$id        = esc_attr( sanitize_text_field( $attributes['id'] ) );
		$shortcode .= "id='$id' ";
	}
	
	if ( ! empty( $attributes['detail'] ) ) {
		$detail    = esc_attr( sanitize_text_field( $attributes['detail'] ) );
		$shortcode .= "detail='$detail' ";
	}
	
	$shortcode .= ']';
	
	$output = do_shortcode( $shortcode );
	return $output;
}
