<?php
/**
 * Functions related to importing exported Views.
 *
 * @since 2.12.1
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2021, Katz Web Services, Inc.
 * @package   GravityView
 */

add_action( 'wp_import_post_meta', 'gravityview_import_helper_fix_line_breaks', 10, 3 );

/**
 * Fixes broken serialization character counts when new line characters are in the exported XML
 *
 * The XML export file includes the line breaks, which are one character when interpreted by PHP ("\n").
 * For some reason, which I (Zack) cannot understand, the serialized data for the post meta counts both characters
 * when generating the string length calculations.
 *
 * For example, the following should be exported with a string length of two: an exclamation mark and a new line ("\n")
 * character. But it's stored in the serialized data as length of three:
 *
 * <example>
 * s:3:"!
 * ";
 * </example>
 *
 * So this function replaces new line characters ("\n") in the XML and replaces them with *two* line break characters
 * in order to match the expected number of characters. I chose this route instead of using placeholders like [newline]
 * in case the second part (updating the meta after import) doesn't work. Multiple new lines is a better outcome than
 * modified text.
 *
 * Replacing one new line with two makes the maybe_unserialize() function happy. I'm happy because we fixed the bug.
 *
 * Am I thrilled with this solution? No, no I am not.
 *
 * Does it work? Yes. Yes, it does.
 *
 * @since 2.12.1
 *
 * @param array $postmeta Copy of $post['postmeta'] to be filtered.
 * @param int   $post_id
 * @param array $post
 *
 * @return array Modified array, if GravityView
 */
function gravityview_import_helper_fix_line_breaks( $postmeta = array(), $post_id = 0, $post = array() ) {

	if ( empty( $post['postmeta'] ) ) {
		return $postmeta;
	}

	if ( 'gravityview' !== $post['post_type'] ) {
		return $postmeta;
	}

	$keys_to_fix = array(
		'_gravityview_directory_fields',
		'_gravityview_directory_widgets',
		'_gravityview_template_settings',
	);

	$performed_fix = false;

	foreach ( $postmeta as &$meta ) {
		$key = $meta['key'];

		if ( ! in_array( $key, $keys_to_fix, true ) ) {
			continue;
		}

		$is_valid_serialized_data = maybe_unserialize( $meta['value'] );

		// The values are not corrupted serialized data. No need to fix.
		if ( false !== $is_valid_serialized_data ) {
			continue;
		}

		$meta['value'] = str_replace( "\n", "\n\n", $meta['value'] );

		$performed_fix = true;
	}

	// Leave a note that this modification has been done. We'll use it later.
	if ( $performed_fix ) {
		$postmeta[] = array(
			'key'   => '_gravityview_fixed_import_serialization',
			'value' => 1,
		);
	}

	return $postmeta;
}

add_action( 'import_post_meta', 'gravityview_import_helper_restore_line_breaks', 10, 3 );

/**
 * Restores the single new line for imported Views that have been modified.
 *
 * @since 2.12.1
 *
 * @see gravityview_import_helper_fix_line_breaks()
 *
 * @param int    $post_id
 * @param string $key
 * @param mixed  $value
 */
function gravityview_import_helper_restore_line_breaks( $post_id, $key, $value ) {

	$keys_to_fix = array(
		'_gravityview_directory_fields',
		'_gravityview_directory_widgets',
		'_gravityview_template_settings',
	);

	if ( ! in_array( $key, $keys_to_fix, true ) ) {
		return;
	}

	if ( false === get_post_meta( $post_id, '_gravityview_fixed_import_serialization' ) ) {
		return;
	}

	if ( empty( $value ) || ! is_string( $value ) ) {
		return;
	}

	if ( false === strpos( $value, "\n\n" ) ) {
		return;
	}

	// Restore the single new line.
	$updated_value = str_replace( "\n\n", "\n", $value );

	update_post_meta( $updated_value, $key, $updated_value );
}
