<?php
/**
 * The default entry link field output template.
 *
 * @since future
 */
$form = $gravityview->view->form->form;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

/** The state still haunts us... BOO! */
$_restore = GravityView_View::getInstance()->getViewId();
GravityView_View::getInstance()->setViewId( $gravityview->view->ID );

$link_text = empty( $field_settings['entry_link_text'] ) ? __( 'View Details', 'gravityview' ) : $field_settings['entry_link_text'];

$output = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ) );

$tag_atts = array();

if ( ! empty( $field_settings['new_window'] ) ) {
	$tag_atts['target'] = '_blank';
}

echo GravityView_API::entry_link_html( $entry, $output, $tag_atts, $field_settings );

GravityView_View::getInstance()->setViewId( $_restore );
