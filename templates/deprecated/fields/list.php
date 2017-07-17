<?php
/**
 * Display the list field type
 *
 * @todo Confirm it works with http://gravitywiz.com/use-list-field-choices-gravity-forms/
 * @package GravityView
 * @subpackage GravityView/templates/fields
 * @global GF_Field_List $field
 * @global string $field_id ID of the field
 * @global string $value Gravity Forms serializes the list field values
 * @global string $display_value Field output HTML. For list fields with columns, it's a table. Otherwise, an unordered list
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

$column_id = gravityview_get_input_id_from_id( $field_id );

if( $field->enableColumns && false !== $column_id ) {

	/**
	 * @filter `gravityview/fields/list/column-format` Format of single list column output of a List field with Multiple Columns enabled
	 * @since 1.14
	 * @param string $format `html` (for <ul> list), `text` (for CSV output)
	 */
	$format = apply_filters( 'gravityview/fields/list/column-format', 'html' );

	echo GravityView_Field_List::column_value( $field, $value, $column_id, $format );

} else {
	echo $display_value;
}