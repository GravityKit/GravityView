<?php

if ( ! function_exists( 'gravityview_block_render_gravityview' ) ) {
	return;
}

/**
 * This function generates the gravityview shortcode
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
 *                         array['end_date']            string  Filter the results by date. This sets a limit on the latest results shown. In YYYY-MM-DD format.
 *                         array['class']               string  Add a HTML class to the view wrapper
 *                         array['offset']              string  This is the start point in the current data set (Count starts with 0)
 *                         array['single_title']        string  Define a custom single entry view title (default: post/page title)
 *                         array['back_link_label']     string  Define a custom single entry back link label (default: ← Go back)
 *                         array['post_id']             string  When using the shortcode in a widget or template, you may want to specify a page where a View is embedded as the base URL for entry links
 *
 * @return string $output
 */
function gravityview_block_render_gravityview( array $attributes ) {

	$accepted_attributes = array(
		'id',
		'page_size',
		'sort_field',
		'sort_direction',
		'search_field',
		'search_value',
		'search_operator',
		'start_date',
		'end_date',
		'class',
		'offset',
		'single_title',
		'back_link_label',
		'post_id',
	);

	$shortcode_attributes = array();

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
