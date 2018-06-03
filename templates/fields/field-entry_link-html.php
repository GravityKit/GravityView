<?php
/**
 * The default entry link field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */
$form = $gravityview->view->form->form;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

$link_text = empty( $field_settings['entry_link_text'] ) ? __( 'View Details', 'gravityview' ) : $field_settings['entry_link_text'];

$output = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ), $gravityview );

$tag_atts = array();

if ( ! empty( $field_settings['new_window'] ) ) {
	$tag_atts['target'] = '_blank';
}

global $post;

echo GravityView_API::entry_link_html( $entry, $output, $tag_atts, $field_settings, $post ? $post->ID : $gravityview->view->ID );
