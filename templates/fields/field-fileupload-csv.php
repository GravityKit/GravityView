<?php
/**
 * The default file upload field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */
$field = $gravityview->field->field;
$value = $gravityview->value;
$form = $gravityview->view->form->form;
$entry = $gravityview->entry->as_entry();

if ( ! empty( $value ) ) {
	$output_arr = gravityview_get_files_array( $value, '', $gravityview );
	echo implode( "\n", wp_list_pluck( $output_arr, 'file_path' ) );
}
