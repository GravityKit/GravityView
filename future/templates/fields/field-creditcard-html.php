<?php
/**
 * The default creditcard field output template.
 *
 * @since future
 */
$field_id = $gravityview->field->ID;

$is_single_input = floor( $field_id ) !== floatval( $field_id );

$output = '';

if ( ! $is_single_input ) {
	/** Only allow the linebreak. */
	$output = wp_kses( $gravityview->display_value, array( 'br' => array() ) );
} else {
	switch ( gravityview_get_input_id_from_id( $field_id ) ) {
		case 1:
		case 4:
			$output = esc_html( rgar( $gravityview->value, $field_id ) );
		default:
			/** For security reasons only masked number and type are shown. */
			break;
	}
}

echo $output;
