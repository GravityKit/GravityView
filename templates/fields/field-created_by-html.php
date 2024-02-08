<?php
/**
 * The default created by field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$value          = $gravityview->value;
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

switch ( true ) :
	// column
	case in_array( $name_display, array( 'ID', 'user_login', 'display_name', 'user_email', 'user_registered' ), true ):
		echo esc_html( $user->$name_display );
		break;
	// meta
	case in_array( $name_display, array( 'nickname', 'description', 'first_name', 'last_name' ) ):
		echo esc_html( get_user_meta( $user->ID, $name_display, true ) );
		break;
	// misc
	case 'first_last_name':
		echo esc_html( trim( sprintf( '%s %s', get_user_meta( $user->ID, 'first_name', true ), get_user_meta( $user->ID, 'last_name', true ) ) ) );
		break;
	case 'last_first_name':
		echo esc_html( trim( sprintf( '%s %s', get_user_meta( $user->ID, 'last_name', true ), get_user_meta( $user->ID, 'first_name', true ) ) ) );
		break;
endswitch;
