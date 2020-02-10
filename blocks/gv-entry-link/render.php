<?php

if ( ! function_exists( 'gravityview_block_render_gv_entry_link' ) ) {
	return;
}

/**
 * This function generates the gv_entry_link shortcode.
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

	$shortcode = '[gv_entry_link ';

	if ( ! empty( $attributes['view_id'] ) ) {
		$view_id   = esc_attr( sanitize_text_field( $attributes['view_id'] ) );
		$shortcode .= "view_id='$view_id' ";
	}

	if ( ! empty( $attributes['entry_id'] ) ) {
		$entry_id  = esc_attr( sanitize_text_field( $attributes['entry_id'] ) );
		$shortcode .= "entry_id='$entry_id' ";
	}

	if ( ! empty( $attributes['action'] ) ) {
		$action    = esc_attr( sanitize_text_field( $attributes['action'] ) );
		$shortcode .= "action='$action' ";
	}

	if ( ! empty( $attributes['post_id'] ) ) {
		$post_id   = esc_attr( sanitize_text_field( $attributes['post_id'] ) );
		$shortcode .= "post_id='$post_id' ";
	}

	if ( ! empty( $attributes['return'] ) ) {
		$return    = esc_attr( sanitize_text_field( $attributes['return'] ) );
		$shortcode .= "return='$return' ";
	}

	if ( ! empty( $attributes['link_atts'] ) ) {
		$link_atts = esc_attr( sanitize_text_field( $attributes['link_atts'] ) );
		$shortcode .= "link_atts='$link_atts'";
	}

	if ( ! empty( $attributes['field_values'] ) ) {
		$field_values = esc_attr( sanitize_text_field( $attributes['field_values'] ) );
		$shortcode    .= "$field_values='$field_values'";
	}

	if ( ! empty( $attributes['content'] ) ) {
		$content   = wp_kses_post( $attributes['content'] );
		$shortcode .= ']' . $content . '[/gv_entry_link]';
	} else {
		$shortcode .= '/]';
	}

	$output = do_shortcode( $shortcode );

	return $output;
}
