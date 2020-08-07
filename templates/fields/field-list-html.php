<?php
/**
 * The default list field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field_id = $gravityview->field->ID;
$field = $gravityview->field->field;
$value = $gravityview->value;
$display_value = $gravityview->display_value;

$column_id = gravityview_get_input_id_from_id( $field_id );

if ( $field->enableColumns && false !== $column_id ) {

	/**
	 * @filter `gravityview/fields/list/column-format` Format of single list column output of a List field with Multiple Columns enabled
	 * @since 1.14
	 * @param string $format `html` (for <ul> list), `text` (for CSV output)
	 * @since 2.0
	 * @param \GV\Template_Context $gravityview The context
	 */
	$format = apply_filters( 'gravityview/fields/list/column-format', 'html', $gravityview );

	echo GravityView_Field_List::column_value( $field, $value, $column_id, $format );

} else {
	echo $display_value;
}
