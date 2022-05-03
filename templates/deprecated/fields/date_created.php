<?php
/**
 * Display the date_created field type.
 */
$gravityview_view = GravityView_View::getInstance();

extract($gravityview_view->getCurrentField());

echo GVCommon::format_date($value, 'format='.\GV\Utils::get($field_settings, 'date_display'));
