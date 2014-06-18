<?php
/**
 * Address field output
 *
 * @group GravityView
 */

global $gravityview_view;

extract( $gravityview_view->field_data );

$output = '';

if( floatval( $field_id ) === floor( floatval( $field_id ) ) ) {
	// For the complete field value
	$output = $display_value;
} else {
	// For part of the field value
	$entry_keys = array_keys( $entry );
	foreach( $entry_keys as $input_key ) {
		if( is_numeric( $input_key ) && floatval( $input_key ) === floatval( $field_id ) ) {
			$output = $entry[ $input_key ];
			break;
		}
	}
}

echo $output;
