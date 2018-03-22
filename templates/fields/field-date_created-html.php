<?php
/**
 * The default date created field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */
$value = $gravityview->value;
$field_settings = $gravityview->field->as_configuration();

echo GVCommon::format_date( $value, array( 'format' => \GV\Utils::get( $field_settings, 'date_display' ) ) );
