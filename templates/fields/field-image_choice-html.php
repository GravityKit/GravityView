<?php
/**
 * The default image choice field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}
$field          = $gravityview->field->field;
$entry          = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

if ( 'image' === \GV\Utils::get( $field_settings, 'choice_display' ) ) {
	$gravityview_view = GravityView_View::getInstance();
	$form             = $gravityview_view->getForm();
	$image_choice     = new GravityView_Field_Image_Choice( $field );
	echo $image_choice->output_image_choice( $gravityview->value, $field, $form );
} else {
	/**
	 * Override whether to show the value or the label of a Image Choice field.
	 *
	 * @since TBD
	 * @param bool $show_label True: Show the label of the Choice; False: show the value of the Choice. Default: `false`
	 * @param array $entry GF Entry
	 * @param GF_Field_Select $field Gravity Forms Select field
	 * @param \GV\Template_Context $gravityview The context
	 */
	$show_label = apply_filters( 'gravityview/fields/image_choice/output_label', ( 'label' === \GV\Utils::get( $field_settings, 'choice_display' ) ), $entry, $field, $gravityview );

	$output = $field->get_value_entry_detail( $gravityview->value, '', $show_label );

	echo $output;
}
