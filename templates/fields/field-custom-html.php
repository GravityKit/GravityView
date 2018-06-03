<?php
/**
 * The default custom content field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */
$form = $gravityview->view->form->form;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

/** Default to empty. */
if ( empty( $gravityview->field->content ) ) {
	$field_settings['content'] = '';
}

// Make sure the class is loaded in DataTables
if ( ! class_exists( 'GFFormDisplay' ) ) {
	include_once GFCommon::get_base_path() . '/form_display.php';
}

/**
 * @filter `gravityview/fields/custom/content_before` Modify Custom Content field output before Merge Tag processing
 * @since 1.6.2
 * @param string $content HTML content of field
 *
 * @since 2.0
 * @param \GV\Template_Context The gravityview template context instance.
 * @since 2.0
 * @param stdClass The gravityview template context object.
 */
$content = apply_filters( 'gravityview/fields/custom/content_before', $gravityview->field->content, $gravityview );
$content = trim( rtrim( $content ) );

// No custom content
if ( empty( $content ) ) {
	return;
}

// Replace the variables
$content = GravityView_API::replace_variables( $content, $form, $entry, false, true, false );

/**
 * @filter `gravityview/fields/custom/decode_shortcodes` Decode brackets in shortcodes, rendering them inert (escape brackets).
 * @since 1.16.5
 * @param boolean $decode Enable/Disable decoding of brackets in the content (default: false)
 * @param string $content HTML content of field
 *
 * @since 2.0
 * @param \GV\Template_Context The gravityview template context instance.
 */
if ( apply_filters( 'gravityview/fields/custom/decode_shortcodes', false, $content, $gravityview ) ) {
	$content = GVCommon::decode_shortcodes( $content );
}

// oEmbed?
if ( ! empty( $gravityview->field->oembed ) ) {
	$content = $GLOBALS['wp_embed']->autoembed( $content );
}

// Add paragraphs?
if ( ! empty( $gravityview->field->wpautop ) ) {
	$content = wpautop( $content );
}

/**
 * @filter `gravityview/fields/custom/content_after` Modify Custom Content field output after Merge Tag variables get replaced, before shortcodes get processed
 * @since 1.6.2
 * @param string $content HTML content of field
 *
 * @since 2.0
 * @param \GV\Template_Context The gravityview template context instance.
 */
$content = apply_filters( 'gravityview/fields/custom/content_after', $content, $gravityview );

// Enqueue scripts needed for Gravity Form display, if form shortcode exists.
// Also runs `do_shortcode()`
echo GFCommon::gform_do_shortcode( $content );
