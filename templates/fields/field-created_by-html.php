<?php
/**
 * The default created by field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */
$value = $gravityview->value;
$field_settings = $gravityview->field->as_configuration();

// There was no logged in user.
if ( empty( $value ) ) {
	return;
}

// Get the user data for the passed User ID
$user = get_userdata( $value );

if ( ! $user ) {
	return;
}

// Display the user data, based on the settings `id`, `username`, or `display_name`
$name_display = empty( $field_settings['name_display'] ) ? 'display_name' : $field_settings['name_display'];

echo esc_html( $user->$name_display );
