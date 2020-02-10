<?php
if ( ! function_exists( 'gravityview_block_render_gventry' ) ) {
	return;
}

/**
 * This function generates the gventry shortcode.
 *
 * @param array $attributes
 *                         array['view_id']     string  The numeric View ID the entry should be displayed from.
 *                         array['id']          string  A numeric ID or slug referencing the entry. Or the last, first entry from the View. The View's sorting and filtering settings will be applied to the entries
 *
 * @return string $output
 */
function gravityview_block_render_gventry( $attributes ) {
	
	$shortcode = '[gventry ';
	
	if ( ! empty( $attributes['view_id'] ) ) {
		$view_id   = esc_attr( sanitize_text_field( $attributes['view_id'] ) );
		$shortcode .= "view_id='$view_id' ";
	}
	
	if ( ! empty( $attributes['id'] ) ) {
		$id        = esc_attr( sanitize_text_field( $attributes['id'] ) );
		$shortcode .= "id='$id' ";
	}
	
	$shortcode .= ']';
	
	$output = do_shortcode( $shortcode );
	
	return $output;
}
