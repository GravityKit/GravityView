<?php
/**
 * The default custom content field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field_settings = $gravityview->field->as_configuration();

// Set the field's start number from field settings.
$gravityview->field->start = is_numeric( $field_settings['start'] ?? null ) ? (int) $field_settings['start'] : 1;

// Set the field's reverse setting from field settings  .
$gravityview->field->reverse = ! empty( $field_settings['reverse'] );

echo $gravityview->field->field->get_sequence( $gravityview );
