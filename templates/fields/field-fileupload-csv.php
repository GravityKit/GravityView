<?php
/**
 * The default file upload field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field = $gravityview->field->field;
$value = $gravityview->value;
$form  = $gravityview->view->form->form;
$entry = $gravityview->entry->as_entry();

if ( ! empty( $value ) ) {
	$output_arr = gravityview_get_files_array( $value, '', $gravityview );

	/**
	 * The value used to separate multiple values in the CSV export.
	 *
	 * @since 2.4.2
	 *
	 * @param string               $glue        The glue. Default: ";" (semicolon).
	 * @param \GV\Template_Context $gravityview The template context.
	 */
	$glue = apply_filters( 'gravityview/template/field/csv/glue', ';', $gravityview );

	echo implode( $glue, wp_list_pluck( $output_arr, 'file_path' ) );
}
