<?php
/**
 * The default textarea field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$value          = $gravityview->value;
$entry          = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

/**
 * Allow the following HTML tags and strip everything else.
 *
 * @since 2.8
 *
 * @param bool                 $allow_html  Whether to allow rendering HTML submitted in the textarea field. If disabled, output will be escaped with esc_html(). Default: true.
 * @param \GV\Template_Context $gravityview The template context.
 */
$allow_html = apply_filters( 'gravityview/fields/textarea/allow_html', \GV\Utils::get( $field_settings, 'allow_html', true ), $gravityview );

if ( $allow_html ) {

	/**
	 * Allow the following HTML tags and strip everything else.
	 *
	 * @since 1.21.5.1
	 * @since 2.0 Added $gravityview parameter.
	 *
	 * @see wp_kses_allowed_html() For allowed contexts.
	 * @see $allowedposttags global in kses.php for an example of the format for passing an array of allowed tags and atts.
	 *
	 * @param array|string         $allowed_html Context string (allowed strings are post, strip, data, entities, or the name of a field filter such as pre_user_description) or allowed tags array (see above). Default: 'post'.
	 * @param \GV\Template_Context $gravityview  The template context.
	 */
	$allowed_html = apply_filters( 'gravityview/fields/textarea/allowed_kses', 'post', $gravityview );

	$value = wp_kses( $value, $allowed_html );

} else {
	$value = esc_html( $value );
}

if ( ! empty( $field_settings['trim_words'] ) ) {

	/**
	 * Modify the "Read more" link used when "Maximum Words" setting is enabled and the output is truncated.
	 *
	 * @since 1.16.1
	 * @param string $excerpt_more Default: ` ...`
	 */
	$excerpt_more = apply_filters( 'gravityview_excerpt_more', ' ' . '&hellip;' );

	global $post;

	$entry_link = GravityView_API::entry_link_html( $entry, $excerpt_more, array(), $field_settings, $post ? $post->ID : $gravityview->view->ID );
	$value      = wp_trim_words( $value, $field_settings['trim_words'], $entry_link );
	unset( $entry_link, $excerpt_more );
}

if ( ! empty( $field_settings['make_clickable'] ) ) {
	$value = make_clickable( $value );
}

if ( ! empty( $field_settings['new_window'] ) ) {
	$value = links_add_target( $value );
}

echo wpautop( $value );
