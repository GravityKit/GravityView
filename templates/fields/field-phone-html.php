<?php
/**
 * The default phone field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$value          = $gravityview->value;
$field_settings = $gravityview->field->as_configuration();

$value = esc_attr( $value );

if ( ! empty( $field_settings['link_phone'] ) && ! empty( $value ) ) {
	echo gravityview_get_link( 'tel:' . $value, $value );
} else {
	echo $value;
}
