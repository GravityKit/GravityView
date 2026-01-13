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
 * Fields that will output as raw data in CSV mode.
 */
$raw_types = array(
	'email',
	'textarea',
	'website',
);

/**
 * Filters field types to output by value instead of display_value.
 *
 * @since 2.5
 *
 * @param bool                 $raw         Raw or not. By default, outputs raw for $raw_types.
 * @param \GV\Template_Context $gravityview The template context.
 */
$raw = apply_filters( 'gravityview/template/csv/field/raw', in_array( $gravityview->field->type, $raw_types, true ), $gravityview );

echo gravityview_get_field_value( $entry, $field_id, $raw ? $value : $display_value );
