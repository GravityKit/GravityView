<?php
/**
 * Display the fileupload field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

$output = '';

if( !empty( $value ) ){

	$gv_class = gv_class( $field, $gravityview_view->getForm(), $entry );

	$output_arr = gravityview_get_files_array( $value, $gv_class );

	// If the output array is just one item, let's not show a list.
	if( sizeof( $output_arr ) === 1 ) {

		$output = $output_arr[0]['content'];

	}

	// There are multiple files
	else {

		// For each file, show as a list
		foreach ( $output_arr as $key => $item) {

			// Fix empty lists
			if( empty( $item['content'] ) ) { continue; }

			$output .= '<li>' . $item['content'] . '</li>';
		}

		if( !empty( $output ) ) {

			$output = sprintf("<ul class='gv-field-file-uploads %s'>%s</ul>", $gv_class, $output );

		}
	}

}

echo $output;
