<?php
/**
 * Display the HTML field type
 *
 * @package GravityView
 * @since  1.2
 */

global $gravityview_view;

extract( $gravityview_view->field_data );

// Tell the renderer not to wrap this field in an anchor tag.
$gravityview_view->field_data['field_settings']['show_as_link'] = false;

$field_settings['content'] = trim(rtrim($field_settings['content']));

// No custom content
if( empty( $field_settings['content'] ) ) {
	return;
}

// Replace the variables
$content = GravityView_API::replace_variables( $field_settings['content'], $form, $entry );

// Add paragraphs?
if( !empty( $field_settings['wpautop'] ) ) {

	$content = wpautop( $content );

}

// Process any shortcodes inside.
echo do_shortcode( $content );
