<?php
/**
 * Generate output for the Source URL field
 * @package GravityView
 * @subpackage GravityView/templates/fields
 * @since 1.1.6
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

// If linking to the source URL
if( !empty( $field_settings['link_to_source'] ) ) {

	// If customizing the anchor text
	if( !empty( $field_settings['source_link_text'] ) ) {

		$link_text = GravityView_API::replace_variables( $field_settings['source_link_text'], $form, $entry );

	} else {

		// Otherwise, it's just the URL
		$link_text = esc_html( $value );

	}

	$output = gravityview_get_link( $value, $link_text );

} else {

	// Otherwise, it's just the URL
	$output = esc_url_raw( $value );

}

echo $output;
