<?php
/**
 * @since 2.0
 *
 * @global array $data
 */

$view_id      = \GV\Utils::get( $data, 'view_id', 0 );
$search_field = \GV\Utils::get( $data, 'search_field', [] );
$search_clear = \GV\Utils::get( $search_field, 'search_clear', false );
$custom_class = \GV\Utils::get( $search_field, 'custom_class', [] );

$submit_html_tag     = $search_field['tag'] ?? 'input';
$submit_button_id    = sprintf( 'gv_search_button_%d', $view_id );
$submit_button_label = $search_field['label'] ?? __( 'Search', 'gk-gravityview' );

?>
<div class="gv-search-box gv-search-box-submit <?php echo $custom_class; ?>">
	<?php

	if ( $search_clear ) {
		// Output the Clear button, if enabled
		$clear_button_params = [
			'url'     => remove_query_arg(
				( GravityView_Widget_Search::getInstance() )->add_reserved_args( [] )
			),
			'text'    => esc_html__( 'Clear', 'gk-gravityview' ),
			'view_id' => $view_id,
			'format'  => 'html',
			'atts'    => [ 'class' => 'button gv-search-clear' ],
		];

		/**
		 * Modifies search widget's Clear button parameters.
		 *
		 * @filter `gravityview/widget/search/clear-button/params`
		 *
		 * @since  2.21
		 *
		 * @param array{url: string, text: string, view_id: int, atts: array} $clear_button_params
		 */
		$clear_button_params = wp_parse_args(
			apply_filters( 'gk/gravityview/widget/search/clear-button/params', $clear_button_params ),
			$clear_button_params
		);

		if ( 'text' === $clear_button_params['format'] ) {
			echo $clear_button_params['url'];
		} else {
			echo gravityview_get_link(
				$clear_button_params['url'],
				$clear_button_params['text'],
				$clear_button_params['atts']
			);
		}
	}

	$args  = gv_get_query_args();
	$input = '<input type="hidden" name="%s" value="%s"/>';

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
