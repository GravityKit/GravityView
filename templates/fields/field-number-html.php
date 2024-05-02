<?php
/**
 * The default number field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$value          = $gravityview->value;
$form           = $gravityview->view->form->form;
$display_value  = $gravityview->display_value;
$field_settings = $gravityview->field->as_configuration();
$field          = $gravityview->field->field;

if ( '' !== $value ) {
	$decimals = ( isset( $field_settings['decimals'] ) && '' !== $field_settings['decimals'] ) ? $field_settings['decimals'] : '';

	if ( 'currency' === $field->numberFormat ) {
		echo $display_value;
	} else {
		if ( $decimals ) {
			$value = number_format( $value, (int) $decimals, '.', '' );
		}

		if ( ! empty( $field_settings['number_format'] ) ) {
			$value = $field->get_value_entry_list( $value, $gravityview->entry->as_entry(), $gravityview->field->id, array(), $form );
		}

		if ( ! $decimals && empty( $field_settings['number_format'] ) ) {
			echo $display_value;
		} else {
			echo $value;
		}
	}
} else {
	echo $display_value;
}
