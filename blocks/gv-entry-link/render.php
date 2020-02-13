<?php

if ( ! function_exists( 'gravityview_block_render_gv_entry_link' ) ) {
	return;
}

/**
 * This function generates the gv_entry_link shortcode
 *
 * @param array $attributes
 *                         array['view_id']         string  The ID for the View where the entry is displayed
 *                         array['entry_id']        string  ID of the entry to edit
 *                         array['action']          string  Define which type of link you want to display. valid values are : read, edit, delete
 *                         array['post_id']         string  If you want to have the Edit Entry link go to an embedded View, pass the ID of the post or page where the View is embedded
 *                         array['return']          string  What should the shortcode return. valid values are : html, url
 *                         array['link_atts']       string  Attributes to pass to the link tag generator to add to the <a> tag
 *                         array['field_values']    string  Parameters to pass URL arguments to the link
 *                         array['content']         string  The anchor text can be set by this attribute
 *
 * @return string $output
 */
function gravityview_block_render_gv_entry_link( $attributes ) {

	$accepted_attributes = array(
		'view_id',
		'entry_id',
		'action',
		'post_id',
		'return',
		'link_atts',
		'field_values',
		'content'
	);

	$shortcode_attributes = array();

	foreach ( $attributes as $attribute => $value ) {
		$value = esc_attr( sanitize_text_field( $value ) );

		if ( 'content' !== $attribute && in_array( $attribute, $accepted_attributes ) && ! empty( $value ) ) {
			$shortcode_attributes[] = "{$attribute}={$value}";
		}
	}

	if ( ! empty( $attributes['content'] ) ) {
		$shortcode = sprintf(
			'[gv_entry_link %s]%s[/gv_entry_link]',
			join( ' ', $shortcode_attributes ),
			wp_kses_post( $attributes['content'] ),
		);
	} else {
		$shortcode = sprintf( '[gv_entry_link %s/]', join( ' ', $shortcode_attributes ) );
	}

	$output = do_shortcode( $shortcode );

	return $output;
}
