<?php
/**
 * The default date created field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$value          = $gravityview->value;
$field_settings = $gravityview->field->as_configuration();

echo GVCommon::format_date( $value, array( 'format' => \GV\Utils::get( $field_settings, 'date_display' ) ) );
