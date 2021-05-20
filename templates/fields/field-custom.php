<?php
/**
 * The default custom content field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

if ( ! $gravityview->field->form_id || ! ( $form = GFAPI::get_form( $gravityview->field->form_id ) ) ) {
	$form = $gravityview->view->form->form;
}

if ( $gravityview->entry->is_multi() ) {
	$entry = $gravityview->entry[ $form['id'] ];
	$entry = $entry->as_entry();
} else {
	$entry = $gravityview->entry->as_entry();
}

// Make sure the class is loaded in DataTables
if ( ! class_exists( 'GFFormDisplay' ) ) {
	include_once GFCommon::get_base_path() . '/form_display.php';
}

/**
 * @filter `gravityview/fields/custom/entry` Modify entry being displayed
 *
 * @param array $entry The current entry being displayed.
 * @param array $form The current form the custom content is using.
 * @param \GV\Template_Context The GravityView template context instance.
 */
$entry = apply_filters( 'gravityview/fields/custom/entry', $entry, $form, $gravityview );

/**
 * @filter `gravityview/fields/custom/form` Modify form that content is being pulled from
 *
 * @param array $form The current form the custom content is using.
 * @param array $entry The current entry being displayed.
 * @param \GV\Template_Context The GravityView template context instance.
 */
$form  = apply_filters( 'gravityview/fields/custom/form', $form, $entry, $gravityview );

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
