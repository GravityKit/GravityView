<?php
/**
 * The default entry link field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

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

$href = $gravityview->entry->get_permalink( $gravityview->view, $gravityview->request, $tag_atts );

$link = gravityview_get_link( $href, $output, $tag_atts );

/**
 * @filter `gravityview_field_entry_link` Modify the link HTML (here for backward compatibility)
 * @param string $link HTML output of the link
 * @param string $href URL of the link
 * @param array  $entry The GF entry array
 * @param  array $field_settings Settings for the particular GV field
 */
$output = apply_filters( 'gravityview_field_entry_link', $link, $href, $entry, $field_settings );

echo $output;