<?php
/**
 * The default creditcard field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

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
			$output = esc_html( \GV\Utils::get( $gravityview->value, $field_id ) );
		default:
			/** For security reasons only masked number and type are shown. */
			break;
	}
}

echo $output;
