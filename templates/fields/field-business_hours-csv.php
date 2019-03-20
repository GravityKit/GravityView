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

$field_id = $gravityview->field->ID;
$display_value = $gravityview->display_value;
$value = $gravityview->value;
$entry = $gravityview->entry->as_entry();

if ( $value = json_decode( $value ) ) {
	$output = array();
	foreach ( $value as $day ) {
		$output[] = sprintf( '%s %s - %s', $day->daylabel, $day->fromtime, $day->totimelabel );
	}

	echo implode( "\n", $output );
}
