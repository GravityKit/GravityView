<?php
/**
 * Display the HTML field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 * @since  1.2
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

// Make sure the class is loaded in DataTables
if( !class_exists( 'GFFormDisplay' ) ) {
	include_once( GFCommon::get_base_path() . '/form_display.php' );
}

// Tell the renderer not to wrap this field in an anchor tag.
$gravityview_view->setCurrentFieldSetting('show_as_link', false);

/**
 * @filter `gravityview/fields/custom/content_before` Modify Custom Content field output before Merge Tag processing
 * @since 1.6.2
 * @param string $content HTML content of field
 */
$field_settings['content'] = apply_filters( 'gravityview/fields/custom/content_before', $field_settings['content'] );

$field_settings['content'] = trim( rtrim( $field_settings['content'] ) );

// No custom content
if( empty( $field_settings['content'] ) ) {
	return;
}

// Replace the variables
$content = GravityView_API::replace_variables( $field_settings['content'], $form, $entry );

/**
 * @filter `gravityview/fields/custom/decode_shortcodes` Decode brackets in shortcodes
 * @since 1.16.5
 * @param boolean $decode Enable/Disable decoding of brackets in the content (default: false)
 * @param string $content HTML content of field
 */
if( apply_filters( 'gravityview/fields/custom/decode_shortcodes', false, $content ) ) {
	$content = GVCommon::decode_shortcodes( $content );
}

// Add paragraphs?
if( !empty( $field_settings['wpautop'] ) ) {
	$content = wpautop( $content );
}

/**
 * @filter `gravityview/fields/custom/content_after` Modify Custom Content field output after Merge Tag variables get replaced, before shortcodes get processed
 * @since 1.6.2
 * @param string $content HTML content of field
 */
$content = apply_filters( 'gravityview/fields/custom/content_after', $content );

// Enqueue scripts needed for Gravity Form display, if form shortcode exists.
// Also runs `do_shortcode()`
echo GFCommon::gform_do_shortcode( $content );
