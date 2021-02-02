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

if ( ! $gravityview->field->form_id || ! ( $form = GFAPI::get_form( $gravityview->field->form_id ) ) ) {
	$form = $gravityview->view->form->form;
}

if ( $gravityview->entry->is_multi() ) {
	$entry = $gravityview->entry[ $form['id'] ];
	$entry = $entry->as_entry();
} else {
	$entry = $gravityview->entry->as_entry();
}

$field_settings = $gravityview->field->as_configuration();

$link_text = empty( $field_settings['entry_link_text'] ) ? esc_html__( 'View Details', 'gravityview' ) : $field_settings['entry_link_text'];

$output = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ), $gravityview );

$tag_atts = array();

if ( ! empty( $field_settings['new_window'] ) ) {
	$tag_atts['target'] = '_blank';
}

global $post;

$href = $gravityview->entry->get_permalink( $gravityview->view, $gravityview->request, $tag_atts );

/**
 * @filter `gravityview/entry_link/add_query_args` Modify whether to include passed $_GET parameters to the end of the url
 * @since 2.10
 * @param bool $add_query_params Whether to include passed $_GET parameters to the end of the Entry Link URL. Default: true.
 */
$add_query_args = apply_filters( 'gravityview/entry_link/add_query_args', true );

if ( $add_query_args ) {
	$href = add_query_arg( gv_get_query_args(), $href );
}

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
