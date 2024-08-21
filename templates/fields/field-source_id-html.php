<?php
/**
 * The default source URL field output template.
 *
 * @since 2.0
 * @global \GV\Template_Context $gravityview
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );

	return;
}

$value          = esc_html( $gravityview->value );
$form           = $gravityview->view->form->form;
$entry          = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

$output = $value;

if ( empty( $value ) ) {
	return;
}

// No link to source. Just output the value.
if ( empty( $field_settings['link_to_source'] ) ) {
	echo $output;
	return;
}

switch( $field_settings['link_text'] ) {
	default:
	case 'source_id':
		$link_text = $value;
		break;
	case 'page_title':
		$link_text = get_the_title( $value );
		break;
	case 'custom':
		$link_text = $field_settings['source_link_text'];
		$link_text = GravityView_API::replace_variables( $field_settings['source_link_text'], $form, $entry );
		break;
}

$href = get_permalink( $value );

echo gravityview_get_link( $href, $link_text );
