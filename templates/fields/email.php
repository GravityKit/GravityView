<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

// If there's no email, don't bother continuing.
if( empty( $value ) ) {
	return;
}

if( !isset( $field_settings['emailmailto'] ) || !empty( $field_settings['emailmailto'] ) ) {

	$params = array();

	// The default link is a mailto link
	$link = 'mailto:'.$value;

	// Is the subject set?
	if( !empty( $field_settings['emailsubject'] ) ) {

		$subject = GravityView_API::replace_variables( $field_settings['emailsubject'], $form, $entry );

		$params[] = 'subject='.str_replace('+', '%20', urlencode( $subject ) );
	}

	// Is the body set?
	if( !empty( $field_settings['emailbody'] ) ) {

		$body = GravityView_API::replace_variables( $field_settings['emailbody'], $form, $entry );

		$params[] = 'body='.str_replace('+', '%20', urlencode( $body ) );
	}

	// If the subject and body have been set, use them
	if( !empty( $params) ) {
		$link .= '?'.implode( '&', $params );
	}

	// Generate the link HTML
	$output = '<a href="'.esc_attr( $link ).'">'.$value.'</a>';

} else {

	// Plain email, no link
	$output = $value;

}

/**
 * Prevent encrypting emails no matter what - this is handy for DataTables exports, for example
 * @var boolean
 */
$prevent_encrypt = apply_filters( 'gravityview_email_prevent_encrypt', false );

// If not encrypting the link
if( empty( $field_settings['emailencrypt'] ) || $prevent_encrypt ) {

	echo $output;

} else {

	$enkoder = new StandalonePHPEnkoder;

	$enkoder->enkode_msg = __( 'Email hidden; Javascript is required.', 'gravityview' );

	$encrypted =  $enkoder->enkode( $output );

	echo $encrypted;
}
