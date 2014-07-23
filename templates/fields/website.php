<?php
/**
 * Display the website field type
 *
 * @package GravityView
 */

global $gravityview_view;

extract( $gravityview_view->field_data );

if( !empty( $field_settings['truncatelink'] ) && function_exists( 'gravityview_format_link' ) ) {
	$anchor_text = gravityview_format_link( $value );
	echo "<a href='".esc_attr($value)."' target='_blank'>$anchor_text</a>";
} else {
	echo $display_value;
}
