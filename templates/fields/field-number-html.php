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
$field = GFFormsModel::get_field( $form, $gravityview->field->id );

if ( '' !== $value ) {
	$decimals = ( isset( $field_settings['decimals'] ) && '' !== $field_settings['decimals'] ) ? $field_settings['decimals'] : '';
	if ( empty( $field_settings['number_format'] ) && 'currency' === $gravityview->field->field->numberFormat ) {
		echo $display_value;
	} else {
		if($decimals){
			global $wp_locale;
			if($gravityview->field->field->numberFormat === 'decimal_comma'){
				$wp_locale->number_format['decimal_point'] = ',';
				$wp_locale->number_format['thousands_sep'] = '.';
			}else{
				$wp_locale->number_format['decimal_point'] = '.';
				$wp_locale->number_format['thousands_sep'] = ',';
			}

			$value = gravityview_number_format( $value, $decimals, ! empty( $field_settings['number_format'] ) );
		}

		echo $field->get_value_entry_list( $value, $gravityview->entry, $gravityview->field->id, [], $form );

	}
} else {
	echo $display_value;
}
