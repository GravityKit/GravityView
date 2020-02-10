<?php

if ( ! function_exists( 'gravityview_block_render_gravityview' ) ) {
	return;
}

/**
 * This function generates the gravityview shortcode.
 *
 * @param array $attributes
 *                         array['id']                  string  The ID of the View you want to display
 *                         array['page_size']           string  Number of entries to show at a time
 *                         array['sort_field']          string  What form field id should be used to sort?
 *                         array['sort_direction']      string  ASC / DESC
 *                         array['search_field']        string  Only display entries with this text in the value
 *                         array['search_value']        string  Change the type of search to be performed.
 *                         array['search_operator']     string  Possible values include: 'is', 'isnot', '<>', 'not in', 'in', '>', '<', 'contains', 'starts_with', 'ends_with', 'like', '>=', '<='
 *                         array['start_date']          string  Filter the results by date. This sets a limit on the earliest results shown. In YYYY-MM-DD format.
 *                         array['end_date']            string   Filter the results by date. This sets a limit on the latest results shown. In YYYY-MM-DD format.
 *                         array['class']               string   Add a HTML class to the view wrapper
 *                         array['offset']              string  This is the start point in the current data set (Count starts with 0)
 *                         array['single_title']        string  Define a custom single entry view title (default: post/page title)
 *                         array['back_link_label']     string  Define a custom single entry back link label (default: ← Go back)
 *                         array['post_id']             string  When using the shortcode in a widget or template, you may want to specify a page where a View is embedded as the base URL for entry links.
 *
 * @return string $output
 */
function gravityview_block_render_gravityview( $attributes ) {

	$shortcode = '[gravityview ';

	if ( ! empty( $attributes['id'] ) ) {
		$id        = esc_attr( sanitize_text_field( $attributes['id'] ) );
		$shortcode .= "id='$id' ";
	}

	if ( ! empty( $attributes['page_size'] ) ) {
		$page_size = esc_attr( sanitize_text_field( $attributes['page_size'] ) );
		$shortcode .= "page_size='$page_size' ";
	}

	if ( ! empty( $attributes['sort_field'] ) ) {
		$sort_field = esc_attr( sanitize_text_field( $attributes['sort_field'] ) );
		$shortcode  .= "sort_field='$sort_field' ";
	}

	if ( ! empty( $attributes['sort_direction'] ) ) {
		$sort_direction = esc_attr( sanitize_text_field( $attributes['sort_direction'] ) );
		$shortcode      .= "sort_direction='$sort_direction' ";
	}

	if ( ! empty( $attributes['search_field'] ) ) {
		$search_field = esc_attr( sanitize_text_field( $attributes['search_field'] ) );
		$shortcode    .= "search_field='$search_field' ";
	}

	if ( ! empty( $attributes['search_value'] ) ) {
		$search_value = esc_attr( sanitize_text_field( $attributes['search_value'] ) );
		$shortcode    .= "search_value='$search_value' ";
	}

	if ( ! empty( $attributes['search_operator'] ) ) {
		$search_operator = esc_attr( sanitize_text_field( $attributes['search_operator'] ) );
		$shortcode       .= "search_operator='$search_operator' ";
	}

	if ( ! empty( $attributes['start_date'] ) ) {
		$start_date = esc_attr( sanitize_text_field( $attributes['start_date'] ) );
		$shortcode  .= "start_date='$start_date' ";
	}

	if ( ! empty( $attributes['end_date'] ) ) {
		$end_date  = esc_attr( sanitize_text_field( $attributes['end_date'] ) );
		$shortcode .= "end_date='$end_date' ";
	}

	if ( ! empty( $attributes['class'] ) ) {
		$class     = esc_attr( sanitize_text_field( $attributes['class'] ) );
		$shortcode .= "class='$class' ";
	}

	if ( ! empty( $attributes['offset'] ) ) {
		$offset    = esc_attr( sanitize_text_field( $attributes['offset'] ) );
		$shortcode .= "offset='$offset' ";
	}

	if ( ! empty( $attributes['single_title'] ) ) {
		$single_title = esc_attr( sanitize_text_field( $attributes['single_title'] ) );
		$shortcode    .= "single_title='$single_title' ";
	}

	if ( ! empty( $attributes['back_link_label'] ) ) {
		$back_link_label = esc_attr( sanitize_text_field( $attributes['back_link_label'] ) );
		$shortcode       .= "back_link_label='$back_link_label' ";
	}

	if ( ! empty( $attributes['post_id'] ) ) {
		$post_id   = esc_attr( sanitize_text_field( $attributes['post_id'] ) );
		$shortcode .= "post_id='$post_id' ";
	}

	$shortcode .= ']';

	$output = do_shortcode( $shortcode );

	return $output;
}
