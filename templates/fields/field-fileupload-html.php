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

$output = '';

if ( ! empty( $value ) ) {

	$gv_class = gv_class( $field, $form, $entry );

	$output_arr = gravityview_get_files_array( $value, $gv_class, $gravityview );

	// If the output array is just one item, let's not show a list.
	if ( 1 === sizeof( $output_arr ) ) {
		$output = $output_arr[0]['content'];
	}

	// There are multiple files
	else {

		// For each file, show as a list
		foreach ( $output_arr as $key => $item ) {

			// Fix empty lists
			if ( empty( $item['content'] ) ) {
				continue; }

			$output .= '<li>' . $item['content'] . '</li>';
		}

		if ( ! empty( $output ) ) {
			$output = sprintf( "<ul class='gv-field-file-uploads %s'>%s</ul>", $gv_class, $output );
		}
	}
}

echo $output;
