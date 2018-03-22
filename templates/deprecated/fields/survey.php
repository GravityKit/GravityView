<?php
/**
 * Display the Quiz field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

// Make sure the CSS file is enqueued
if( class_exists( 'GFSurvey' ) && is_callable( array('GFSurvey', 'get_instance') ) ) {
	wp_register_style( 'gsurvey_css', GFSurvey::get_instance()->get_base_url() . '/css/gsurvey.css' );
	wp_print_styles( 'gsurvey_css' );
}

echo GravityView_View::getInstance()->getCurrentField( 'display_value' );