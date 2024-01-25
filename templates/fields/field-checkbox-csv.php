<?php
/**
 * The default field output template for CSVs.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field_id      = $gravityview->field->ID;
$display_value = $gravityview->display_value;
$value         = $gravityview->value;
$entry         = $gravityview->entry->as_entry();

/**
 * The value used to separate multiple values in the CSV export.
 *
 * @since 2.4.2
 *
 * @param string The glue. Default: ";" (semicolon)
 * @param \GV\Template_Context The context.
 */
$glue = apply_filters( 'gravityview/template/field/csv/glue', ';', $gravityview );

echo implode( $glue, array_filter( $value ) );
