<?php

if ( ! function_exists( 'gravityview_block_render_gventry' ) ) {
	return;
}

/**
 * This function generates the gventry shortcode
 *
 * @param array $attributes
 *                         array['view_id']     string  The numeric View ID the entry should be displayed from.
 *                         array['id']          string  A numeric ID or slug referencing the entry. Or the last, first entry from the View. The View's sorting and filtering settings will be applied to the entries
 *
 * @return string $output
 */
function gravityview_block_render_gventry( $attributes ) {

	$accepted_attributes = array(
		'id',
		'view_id',
	);

	$shortcode_attributes = array();

	foreach ( $attributes as $attribute => $value ) {
		$value = esc_attr( sanitize_text_field( $value ) );

		if ( in_array( $attribute, $accepted_attributes ) && ! empty( $value ) ) {
			$shortcode_attributes[] = "{$attribute}={$value}";
		}
	}

	$shortcode = sprintf( '[gventry %s]', join( ' ', $shortcode_attributes ) );

	$output = do_shortcode( $shortcode );

	return $output;
}
