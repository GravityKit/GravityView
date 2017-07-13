<?php
/**
 * The default phone field output template.
 *
 * @since future
 */
$value = $gravityview->value;
$field_settings = $gravityview->field->as_configuration();

$value = esc_attr( $value );

if( ! empty( $field_settings['link_phone'] ) && ! empty( $value ) ) {
	echo gravityview_get_link( 'tel:' . $value, $value );
} else {
	echo $value;
}
