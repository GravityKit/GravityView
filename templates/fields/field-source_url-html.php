<?php
/**
 * The default source URL field output template.
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
$entry          = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

// If linking to the source URL
if ( ! empty( $field_settings['link_to_source'] ) ) {

	// If customizing the anchor text
	if ( ! empty( $field_settings['source_link_text'] ) ) {

		$link_text = GravityView_API::replace_variables( $field_settings['source_link_text'], $form, $entry );

	} else {

		// Otherwise, it's just the URL
		$link_text = esc_html( $value );

	}

	$output = gravityview_get_link( $value, $link_text );

} else {

	// Otherwise, it's just the URL
	$output = esc_url_raw( $value );

}

echo $output;
