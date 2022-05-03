<?php
/**
 * Display the Quiz field type.
 */

// Make sure the CSS file is enqueued
if (class_exists('GFSurvey') && is_callable(['GFSurvey', 'get_instance'])) {
    wp_register_style('gsurvey_css', GFSurvey::get_instance()->get_base_url().'/css/gsurvey.css');
    wp_print_styles('gsurvey_css');
}

echo GravityView_View::getInstance()->getCurrentField('display_value');
