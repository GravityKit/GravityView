<?php
/**
 * Display the textarea field type
 *
 * Use wpautop() to format paragraphs, as expected, instead of line breaks like Gravity Forms displays by default.
 *
 * @package GravityView
 */

global $gravityview_view;

extract( $gravityview_view->field_data );

if( !empty( $field_settings['trim_words'] ) ) {
	$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );
	$value = wp_trim_words( $value, $field_settings['trim_words'], $excerpt_more );
}

echo wpautop( $value );

