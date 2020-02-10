<?php

if ( ! function_exists( 'gravityview_block_render_gvfield' ) ) {
	return;
}

/**
 * This function generates the gvfield shortcode.
 *
 * @param array $attributes
 *                         array['view_id']         string  The numeric View ID the entry should be displayed from
 *                         array['entry_id']        string  A numeric ID or slug referencing the entry. Or the last, first entry from the View. The View's sorting and filtering settings will be applied to the entries
 *                         array['field_id']        string  The field ID that should be ouput. Required. If this is a merge of several form feeds multiple fields can be provided separated by a comma
 *                         array['custom_label']    string  Custom label for the field
 *
 * @return string $output
 */
function gravityview_block_render_gvfield( $attributes ) {

	$shortcode = '[gvfield ';

	if ( ! empty( $attributes['view_id'] ) ) {
		$view_id   = esc_attr( sanitize_text_field( $attributes['view_id'] ) );
		$shortcode .= "view_id='$view_id' ";
	}

	if ( ! empty( $attributes['entry_id'] ) ) {
		$entry_id  = esc_attr( sanitize_text_field( $attributes['entry_id'] ) );
		$shortcode .= "entry_id='$entry_id' ";
	}

	if ( ! empty( $attributes['field_id'] ) ) {
		$field_id  = esc_attr( sanitize_text_field( $attributes['field_id'] ) );
		$shortcode .= "field_id='$field_id' ";
	}

	if ( ! empty( $attributes['custom_label'] ) ) {
		$custom_label = esc_attr( sanitize_text_field( $attributes['custom_label'] ) );
		$shortcode    .= "custom_label='$custom_label' ";
	}

	$shortcode .= "]";

	$output = do_shortcode( $shortcode );

	return $output;
}
