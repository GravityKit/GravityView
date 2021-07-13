<?php
/**
 * The default survey field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field = $gravityview->field;

$display_value = $gravityview->display_value;

// An empty single column
if ( '' === \GV\Utils::get( $gravityview->value, $field->ID ) ) {
	echo '';
	return;
}

$is_single_column = isset( $gravityview->value[ $field->ID ] );

// Multiple Row survey fields are formatted with colon-separated information
if ( $is_single_column && false !== strpos( $gravityview->value[ $field->ID ], ':' ) ) {
	list( $row, $column ) = explode( ':', $gravityview->value[ $field->ID ] );
} else {
	$column = $gravityview->value;
}

$display_values = array();
foreach( $field->field->choices as $choice ) {
	if ( ! $is_single_column || $column === $choice['value'] ) {

		if ( $field->score ) {
			$display_values[] = $choice['score'];
		} else {
			$display_values[] = RGFormsModel::get_choice_text( $field->field, $choice['value'], $column );
		}

		if( $is_single_column) {
			break;
		}
	}
}

$display_value = implode( '; ', $display_values );

echo $display_value;
