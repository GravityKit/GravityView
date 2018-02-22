<?php
/**
 * The default edit link field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since future
 */
$form = $gravityview->view->form->form;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

if ( ! class_exists( 'GravityView_Edit_Entry' ) ) {
	return;
}

/** The state still haunts us... BOO! */
\GV\Mocks\Legacy_Context::push( array(
	'view' => $gravityview->view,
) );

global $post;

/** Set the containing post ID if available. */
if ( $post ) {
	\GV\Mocks\Legacy_Context::push( array(
		'post' => $post,
	) );
}

// Only show the link to logged-in users.
if ( ! GravityView_Edit_Entry::check_user_cap_edit_entry( $entry ) ) {
	return;
}

$link_text = empty( $field_settings['edit_link'] ) ? __( 'Edit Entry', 'gravityview' ) : $field_settings['edit_link'];

$link_atts = array();
if ( ! empty( $field_settings['new_window'] ) ) {
	$link_atts['target'] = '_blank';
}

$output = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ) );

$href = GravityView_Edit_Entry::get_edit_link( $entry, $gravityview->view->ID );

echo gravityview_get_link( $href, $output, $link_atts );

\GV\Mocks\Legacy_Context::pop();
