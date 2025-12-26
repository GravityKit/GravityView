<?php
/**
 * The default delete link field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

/** @var \GV\GF_Form $gf_form */
$gf_form = isset( $gravityview->field->form_id ) ? \GV\GF_Form::by_id( $gravityview->field->form_id ) : $gravityview->view->form;
$form    = $gf_form->form;

if ( $gravityview->entry->is_multi() ) {
	$entry = $gravityview->entry->from_field( $gravityview->field );
	$entry = $entry->as_entry();
} else {
	$entry = $gravityview->entry->as_entry();
}

$field_settings = $gravityview->field->as_configuration();

global $post;

if ( ! class_exists( 'GravityView_Delete_Entry' ) ) {
	return;
}

// Only show the link to logged-in users with the right caps.
if ( ! GravityView_Delete_Entry::check_user_cap_delete_entry( $entry, $field_settings, $gravityview->view->ID ) ) {
	return;
}

$link_text = empty( $field_settings['delete_link'] ) ? __( 'Delete Entry', 'gk-gravityview' ) : $field_settings['delete_link'];

/**
 * Modify the entry link anchor text.
 *
 * @since 1.0-beta
 *
 * @param string               $link_text   The link anchor text after merge tag replacement.
 * @param \GV\Template_Context $gravityview The template context.
 */
$link_text = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ), $gravityview );

$href = GravityView_Delete_Entry::get_delete_link( $entry, $gravityview->view->ID, $post ? $post->ID : null );

$attributes = array(
	'onclick' => GravityView_Delete_Entry::get_confirm_dialog(),
);

echo gravityview_get_link( $href, $link_text, $attributes );
