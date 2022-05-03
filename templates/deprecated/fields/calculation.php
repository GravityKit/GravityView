<?php
/**
 * Calculation field output.
 */
$gravityview_view = GravityView_View::getInstance();

extract($gravityview_view->getCurrentField());

echo gravityview_get_field_value($entry, $field_id, $display_value);
