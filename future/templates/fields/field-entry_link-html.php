<?php
/**
 * The default entry link field output template.
 *
 * @since future
 */
$field_id = $gravityview->field->ID;
$field = $gravityview->field->field;
$value = $gravityview->value;
$form = $gravityview->view->form->form;
$display_value = $gravityview->display_value;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

$link_text = empty( $field_settings['entry_link_text'] ) ? __( 'View Details', 'gravityview' ) : $field_settings['entry_link_text'];

$output = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ) );

$tag_atts = array();

if ( ! empty( $field_settings['new_window'] ) ) {
	$tag_atts['target'] = '_blank';
}

echo GravityView_API::entry_link_html( $entry, $output, $tag_atts, $field_settings );
