<?php
/**
 * The default Image Choice field output template.
 *
 * @since 2.31.0
 *
 * @global Template_Context $gravityview
 */

use GV\Template_Context;
use GV\Utils;

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', [ 'file' => __FILE__ ] );

	return;
}
$field          = $gravityview->field->field;
$entry          = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

if ( 'image' === Utils::get( $field_settings, 'choice_display' ) ) {
	$gravityview_view = GravityView_View::getInstance();
	$form             = $gravityview_view->getForm();
	$image_choice     = new GravityView_Field_Image_Choice();

	echo $image_choice->output_image_choice( $gravityview->value, $field, $form );
} else {
	/**
	 * Overrides whether to show the value or the label of an Image Choice field.
	 *
	 * @since 2.31.0
	 *
	 * @param bool                             $show_label  True to display the label of the choice; false to display the value. Default is false.
	 * @param array                            $entry       The Gravity Forms entry.
	 * @param GF_Field_Checkbox|GF_Field_Radio $field       The Gravity Forms field (can be either a radio or checkbox field).
	 * @param Template_Context                 $gravityview The GravityView template context.
	 */
	$show_label = apply_filters( 'gravityview/fields/image_choice/output_label', ( 'label' === Utils::get( $field_settings, 'choice_display' ) ), $entry, $field, $gravityview );

	echo $field->get_value_entry_detail( $gravityview->value, '', $show_label );
}
