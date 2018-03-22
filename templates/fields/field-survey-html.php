<?php
/**
 * The default survey field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */
$display_value = $gravityview->display_value;

// Make sure the CSS file is enqueued
if ( class_exists( 'GFSurvey' ) && is_callable( array('GFSurvey', 'get_instance') ) ) {
	wp_register_style( 'gsurvey_css', GFSurvey::get_instance()->get_base_url() . '/css/gsurvey.css' );
	wp_print_styles( 'gsurvey_css' );
}

echo $display_value;
