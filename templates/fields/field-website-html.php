<?php
/**
 * The default website field output template.
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

if ( ! empty( $value ) && function_exists( 'gravityview_format_link' ) ) {

	$value = esc_url_raw( $value );

	/** @since 1.8 */
	$anchor_text = ! empty( $field_settings['anchor_text'] ) ? trim( rtrim( \GV\Utils::get( $field_settings, 'anchor_text', '' ) ) ) : false;

	// Check empty again, just in case trim removed whitespace didn't work
	if ( ! empty( $anchor_text ) ) {

		// Replace the variables
		$anchor_text = GravityView_API::replace_variables( $anchor_text, $form, $entry );

	} else {
		$anchor_text = empty( $field_settings['truncatelink'] ) ? $value : gravityview_format_link( $value );
	}

	$attributes = '';

	if ( empty( $field_settings['open_same_window'] ) && ! empty( $field_settings['new_window'] ) ) {
		$attributes = 'target=_blank';
	}

	echo gravityview_get_link( $value, $anchor_text, $attributes );
} else {
	echo esc_html( esc_url_raw( $value ) );
}
