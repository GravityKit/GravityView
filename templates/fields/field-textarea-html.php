<?php
/**
 * The default textarea field output template.
 *
 * @since future
 */
$value = $gravityview->value;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

/**
 * @filter `gravityview/fields/textarea/allowed_kses` Allow the following HTML tags and strip everything else.
 * @since 1.21.5.1
 * @see $allowedposttags global in kses.php for an example of the format for passing an array of allowed tags and atts
 * @see wp_kses_allowed_html() For allowed contexts
 * @param array|string $allowed_html Context string (allowed strings are post, strip, data, entities, or the name of a field filter such as pre_user_description) or allowed tags array (see above). [Default: 'post']
 */
$allowed_html = apply_filters( 'gravityview/fields/textarea/allowed_kses', 'post' );

$value = wp_kses( $value, $allowed_html );

if ( ! empty( $field_settings['trim_words'] ) ) {

	/**
	 * @filter `gravityview_excerpt_more` Modify the "Read more" link used when "Maximum Words" setting is enabled and the output is truncated
	 * @since 1.16.1
	 * @param string $excerpt_more Default: ` ...`
	 */
	$excerpt_more = apply_filters( 'gravityview_excerpt_more', ' ' . '&hellip;' );

	$entry_link = GravityView_API::entry_link_html( $entry, $excerpt_more, array(), $field_settings );
	$value = wp_trim_words( $value, $field_settings['trim_words'], $entry_link );
	unset( $entry_link, $excerpt_more );
}

if ( ! empty( $field_settings['make_clickable'] ) ) {
    $value = make_clickable( $value );
}

if ( ! empty( $field_settings['new_window'] ) ) {
	$value = links_add_target( $value );
}

echo wpautop( $value );
