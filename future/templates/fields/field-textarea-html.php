<?php
/**
 * The default textarea field output template.
 *
 * @since future
 */
$value = $gravityview->value;
$entry = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

/** Escape! */
$value = esc_html( $value );

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

