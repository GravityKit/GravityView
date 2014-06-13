<?php
/**
 * Display the created_by field type
 *
 * @package GravityView
 */

global $gravityview_view;

extract( $gravityview_view->__get('field_data') );

// Get the user data for the passed User ID
$User = get_userdata($value);

// Display the user data, based on the settings
// `id`, `username`, or `display_name`
echo $User->{$field_settings['name_display']};
