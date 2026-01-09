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

/** @var \GV\GF_Form $gf_form */
$gf_form = isset( $gravityview->field->form_id ) ? \GV\GF_Form::by_id( $gravityview->field->form_id ) : $gravityview->view->form;
$form    = $gf_form->form;

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
 * Modify entry being displayed.
 *
 * @since 2.10.3
 *
 * @param array                $entry       The current entry being displayed.
 * @param array                $form        The current form the custom content is using.
 * @param \GV\Template_Context $gravityview The GravityView template context instance.
 */
$entry = apply_filters( 'gravityview/fields/custom/entry', $entry, $form, $gravityview );

/**
 * Modify form that content is being pulled from.
 *
 * @since 2.10.3
 *
 * @param array                $form        The current form the custom content is using.
 * @param array                $entry       The current entry being displayed.
 * @param \GV\Template_Context $gravityview The GravityView template context instance.
 */
$form = apply_filters( 'gravityview/fields/custom/form', $form, $entry, $gravityview );

/**
 * Modify Custom Content field output before Merge Tag processing.
 *
 * @since 1.6.2
 * @since 2.0 Added $gravityview parameter.
 *
 * @param string               $content     HTML content of field.
 * @param \GV\Template_Context $gravityview The GravityView template context instance.
 */
$content = apply_filters( 'gravityview/fields/custom/content_before', $gravityview->field->content, $gravityview );
$content = trim( rtrim( (string) $content ) );

// No custom content
if ( empty( $content ) ) {
	return;
}

// Replace the variables
$content = GravityView_API::replace_variables( $content, $form, $entry, false, true, false );

/**
 * Decode brackets in shortcodes, rendering them inert (escape brackets).
 *
 * @since 1.16.5
 * @since 2.0 Added $gravityview parameter.
 *
 * @param boolean              $decode      Enable/Disable decoding of brackets in the content. Default: false.
 * @param string               $content     HTML content of field.
 * @param \GV\Template_Context $gravityview The GravityView template context instance.
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
 * Modify Custom Content field output after Merge Tag variables get replaced, before shortcodes get processed.
 *
 * @since 1.6.2
 * @since 2.0 Added $gravityview parameter.
 *
 * @param string               $content     HTML content of field.
 * @param \GV\Template_Context $gravityview The GravityView template context instance.
 */
$content = apply_filters( 'gravityview/fields/custom/content_after', $content, $gravityview );

// Enqueue scripts needed for Gravity Form display, if form shortcode exists.
// Also runs `do_shortcode()`
echo GFCommon::gform_do_shortcode( $content );
