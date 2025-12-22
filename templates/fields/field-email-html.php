<?php
/**
 * The default email field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$value          = $gravityview->value;
$form           = $gravityview->view->form->form;
$entry          = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

// If there's no email, don't bother continuing.
if ( empty( $value ) ) {
	return;
}

// Handle email fields with confirmation enabled.
if ( is_array( $value ) ) {
	$value = reset( $value );
}

// Default: plain email, no link
$output = $value;

if ( ! isset( $field_settings['emailmailto'] ) || ! empty( $field_settings['emailmailto'] ) ) {

	$params = array();

	// The default link is a mailto link
	$link = 'mailto:' . $value;

	// Is the subject set?
	if ( ! empty( $field_settings['emailsubject'] ) ) {

		$subject = GravityView_API::replace_variables( $field_settings['emailsubject'], $form, $entry );

		$subject = wp_strip_all_tags( trim( do_shortcode( $subject ) ) );

		$params[] = 'subject=' . str_replace( '+', '%20', urlencode( $subject ) );
	}

	// Is the body set?
	if ( ! empty( $field_settings['emailbody'] ) ) {

		$body = GravityView_API::replace_variables( $field_settings['emailbody'], $form, $entry );

		$body = wp_strip_all_tags( trim( do_shortcode( $body ) ) );

		$params[] = 'body=' . str_replace( '+', '%20', urlencode( $body ) );
	}

	// If the subject and body have been set, use them
	if ( ! empty( $params ) ) {
		$link .= '?' . implode( '&', $params );
	}

	// Generate the link HTML
	$output = gravityview_get_link( $link, $value );

}

/**
 * Prevent encrypting emails no matter what - this is handy for DataTables exports, for example.
 *
 * @since 1.1.6
 * @since 2.0 Added $gravityview parameter.
 *
 * @param bool                 $prevent_encrypt Whether to prevent email encryption.
 * @param \GV\Template_Context $gravityview     The template context object.
 */
$prevent_encrypt = apply_filters( 'gravityview_email_prevent_encrypt', false, $gravityview );

// If encrypting the link
if ( ! empty( $field_settings['emailencrypt'] ) && ! $prevent_encrypt ) {
	$output = GVCommon::js_encrypt( $output );
}

echo $output;
