<?php
/**
 * Display the Section field type
 *
 * @package GravityView
 */

global $gravityview_view;

extract( $gravityview_view->field_data );

echo GravityView_API::replace_variables( $field['description'], $form, $entry );
