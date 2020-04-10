<?php
/**
 * Gravatar field output for HTML rendering
 *
 * @global \GV\Template_Context $gravityview
 * @since TODO
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

/**
 * @filter `gravityview/fields/gravatar/settings` Modify the Gravatar settings for the field
 * @param[in,out] $settings array Settings passed to {@see GravityView_Field_Gravatar::get_gravatar} for parameters.
 * @param \GV\Template_Context $gravityview Current context
 */
$settings = apply_filters( 'gravityview/fields/gravatar/settings', $settings, $gravityview );

echo GravityView_Field_Gravatar::get_gravatar(
	\GV\Utils::get( $settings, 'email' ),
	\GV\Utils::get( $settings, 'size', 80 ),
	\GV\Utils::get( $settings, 'default', 'mp' ),
	\GV\Utils::get( $settings, 'rating', 'g' ),
	\GV\Utils::get( $settings, 'image', true ),
	\GV\Utils::get( $settings, 'atts', array() ), // You can override via filter
);
