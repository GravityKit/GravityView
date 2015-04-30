<?php
/**
 * Display the textarea field type
 *
 * Use wpautop() to format paragraphs, as expected, instead of line breaks like Gravity Forms displays by default.
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

if( !empty( $field_settings['trim_words'] ) ) {
	$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );
	$value = wp_trim_words( $value, $field_settings['trim_words'], $excerpt_more );
}

if( !empty( $field_settings['make_clickable'] ) ) {
    $value = make_clickable( $value );
}

echo wpautop( $value );

