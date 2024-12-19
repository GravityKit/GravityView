<?php
/**
 * The default field output template for CSVs.
 *
 * @since 2.0
 * @global Template_Context $gravityview
 */

use GV\Template_Context;
use GV\Utils;

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );

	return;
}

$field_id        = $gravityview->field->ID;
$field           = $gravityview->field->field;
$value           = $gravityview->value;
$form            = $gravityview->view->form->form;
$entry           = $gravityview->entry->as_entry();
$field_settings  = $gravityview->field->as_configuration();
$display_type    = Utils::get( $field_settings, 'choice_display' );
$is_single_input = floor( $field_id ) !== floatval( $field_id );
$output          = '';

// It's the parent field, not an input
if ( ! $is_single_input ) {
	/**
	 * The value used to separate multiple values in the CSV export.
	 *
	 * @since 2.4.2
	 *
	 * @param string $glue The glue. Default: ";" (semicolon).
	 * @param Template_Context $gravityview The context.
	 */
	$glue   = apply_filters( 'gravityview/template/field/csv/glue', ';', $gravityview );
	$output = implode( $glue, array_filter( $value ) );
} else {

	$field_value = $entry[ $field_id ] ?? '';

	switch ( $display_type ) {
		case 'label':
			$output = gravityview_get_field_label( $form, $field_id, $value );
			break;
		case 'tick':
		default:
			if ( $field_value ) {
				/**
				 * Change the output for a checkbox "check" symbol.
				 *
				 * @since $ver$
				 *
				 * @param string $output Checkbox "check" symbol. Default: "✓".
				 * @param array $entry Entry data.
				 * @param GF_Field_Checkbox $field GravityView field.
				 * @param Template_Context $gravityview The context.
				 */
				$output = apply_filters( 'gravityview/template/field/csv/tick', '✓', $entry, $field, $gravityview );
			}
			break;
	}
}

echo $output;
