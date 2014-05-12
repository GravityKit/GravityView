<?php

global $gravityview_view;

extract( $gravityview_view->__get('field_data') );

$output = '';

if( apply_filters( 'gravityview_show_fulltext', true, $entry, $field_id ) ) {
	$long_text = '';

	if( isset( $entry[ $field_id ] ) && strlen( $entry[ $field_id ] ) >= GFORMS_MAX_FIELD_LENGTH ) {
	   $long_text = RGFormsModel::get_lead_field_value( $entry, RGFormsModel::get_field( $form, $field_id ));
	}
	if( isset( $entry[ $field_id ] ) ) {
		$output = !empty( $long_text ) ? $long_text : esc_html( $value );
	}
} else {
	$output = esc_html( $value );
}

if( apply_filters( 'gravityview_entry_value_wpautop', true, $entry, $field_id ) ) {
	$output = wpautop( $value );
};

echo $output;