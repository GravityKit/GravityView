<?php

if ( ! function_exists( 'gravityview_block_render_gvfield' ) ) {
	return;
}

/**
 * This function generates the gvfield shortcode
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

	$accepted_attributes = array(
		'vew_id',
		'entry_id',
		'field_id',
		'custom_label',
	);

	$shortcode_attributes = array();

	foreach ( $attributes as $attribute => $value ) {
		$value = esc_attr( sanitize_text_field( $value ) );

		if ( in_array( $attribute, $accepted_attributes ) && ! empty( $value ) ) {
			$shortcode_attributes[] = "{$attribute}={$value}";
		}
	}

	$shortcode = sprintf( '[gvfield %s]', join( ' ', $shortcode_attributes ) );

	$output = do_shortcode( $shortcode );

	return $output;
}
