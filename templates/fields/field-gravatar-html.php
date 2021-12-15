<?php
/**
 * Gravatar field output for HTML rendering
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.8
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field_settings = $gravityview->field->as_configuration();

// There was no email field setting defined somehow.
if ( empty( $field_settings['email_field'] ) ) {
	return;
}

$settings = $field_settings;

$settings['email'] = GravityView_Field_Gravatar::get_email( $field_settings, $gravityview->entry->as_entry() );

$settings['args'] = array(
	'force_display' => true,
);

/**
 * @filter `gravityview/fields/gravatar/settings` Modify the Gravatar settings for the field
 * @param[in,out] $settings array Settings passed to {@see get_avatar()} for parameters.
 * @param \GV\Template_Context $gravityview Current context
 */
$settings = apply_filters( 'gravityview/fields/gravatar/settings', $settings, $gravityview );

echo get_avatar(
	\GV\Utils::get( $settings, 'email' ),
	\GV\Utils::get( $settings, 'size', 96 ),
	\GV\Utils::get( $settings, 'default', '' ),
	\GV\Utils::get( $settings, 'alt', '' ),
	\GV\Utils::get( $settings, 'args', array() ) // You can set via filter above
);
