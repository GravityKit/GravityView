<?php
/**
 * The default date created field output template.
 *
 * @since future
 */
$value = $gravityview->value;
$field_settings = $gravityview->field->as_configuration();

echo GVCommon::format_date( $value, array( 'format' => rgar( $field_settings, 'date_display' ) ) );
