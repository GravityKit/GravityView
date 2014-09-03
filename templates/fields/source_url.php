<?php
/**
 * Generate output for the Source URL field
 * @package GravityView
 * @since 1.1.6
 */

global $gravityview_view;

extract( $gravityview_view->field_data );

// If linking to the source URL
if( !empty( $field_settings['link_to_source'] ) ) {

	// If customizing the anchor text
	if( !empty( $field_settings['source_link_text'] ) ) {

		$link_text = GravityView_API::replace_variables( $field_settings['source_link_text'], $form, $entry );

	} else {

		// Otherwise, it's just the URL
		$link_text = $value;

	}

	$output = '<a href="'. esc_url( $value ) .'">'. esc_html( $link_text ) . '</a>';

} else {

	// Otherwise, it's just the URL
	$output = $value;

}

echo $output;
