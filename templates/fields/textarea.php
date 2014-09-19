<?php
/**
 * Display the textarea field type
 *
 * Use wpautop() to format paragraphs, as expected, instead of line breaks like Gravity Forms displays by default.
 *
 * @package GravityView
 */

global $gravityview_view;

extract( $gravityview_view->field_data );

echo wpautop( $value );
