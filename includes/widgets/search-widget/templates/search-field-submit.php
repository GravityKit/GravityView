<?php
/**
 * @since 2.0
 *
 * @global array $data
 */

$view_id      = \GV\Utils::get( $data, 'view_id', 0 );
$search_mode  = \GV\Utils::get( $data, 'search_mode', 'any' );
$search_clear = \GV\Utils::get( $data, 'search_clear', false );
$search_field = \GV\Utils::get( $data, 'search_field', [] );

$submit_html_tag     = $search_field['tag'] ?? 'input';
$submit_button_id    = sprintf( 'gv_search_button_%d', $view_id );
$submit_button_label = $search_field['label'] ?? __( 'Search', 'gk-gravityview' );
?>
<div class="gv-search-box gv-search-box-submit">
	<?php

	// Output the Clear button, if enabled
	echo $search_clear;

	$args  = gv_get_query_args();
	$input = '<input type="hidden" name="%s" value="%s"/>';

	$args['mode'] = $search_mode;

	foreach ( $args as $key => $value ) {
		if ( 'gravityview' === $key ) {
			continue;
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				printf( $input, esc_attr( sprintf( '%s[%s]', $key, $k ) ), esc_attr( $v ) );
			}
		} else {
			printf( $input, esc_attr( $key ), esc_attr( $value ) );
		}
	}

	$button_html = 'button' === $submit_html_tag
		? '<button type="submit" class="button gv-search-button" id="%s">%s</button>'
		: '<input type="submit" class="button gv-search-button" id="%s" value="%s"/>';

	printf( $button_html, $submit_button_id, esc_attr( $submit_button_label ) );
	?>
</div>
