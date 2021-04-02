<?php

namespace GV\Gutenberg\Blocks\Block;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Entry extends Block {
	const BLOCK_NAME = 'entry';

	/**
	 * Generate `[gventry]` shortcode
	 *
	 * @param array $attributes
	 *                         array['view_id']     string  The numeric View ID the entry should be displayed from.
	 *                         array['id']          string  A numeric ID or slug referencing the entry. Or the last, first entry from the View. The View's sorting and filtering settings will be applied to the entries
	 *
	 * @return string $output
	 */
	static function render( $attributes = array() ) {
		$accepted_attributes = array(
			'id',
			'view_id',
		);

		$shortcode_attributes = array();

		foreach ( $attributes as $attribute => $value ) {
			$value = esc_attr( sanitize_text_field( $value ) );

			if ( in_array( $attribute, $accepted_attributes ) && ! empty( $value ) ) {
				$shortcode_attributes[] = "{$attribute}={$value}";
			}
		}

		$shortcode = sprintf( '[gventry %s]', implode( ' ', $shortcode_attributes ) );

		$output = do_shortcode( $shortcode );

		return $output;
	}
}
