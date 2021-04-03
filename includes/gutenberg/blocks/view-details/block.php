<?php

namespace GV\Gutenberg\Blocks\Block;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class View_Details extends Block {
	const BLOCK_NAME = 'view-details';

	/**
	 * Generate `[gravityview]` shortcode
	 *
	 * @param array $attributes
	 *                         array['id']     string  The ID of the View you want to display
	 *                         array['detail'] string  Display specific information about a View. Valid values are total_entries, first_entry, last_entry, page_size
	 *
	 * @return string $output
	 */
	static function render( $attributes = array() ) {
		$accepted_attributes = array(
			'id',
			'detail',
		);

		$shortcode_attributes = array();

		foreach ( $attributes as $attribute => $value ) {
			$value = esc_attr( sanitize_text_field( $value ) );

			if ( in_array( $attribute, $accepted_attributes ) && ! empty( $value ) ) {
				$shortcode_attributes[] = "{$attribute}={$value}";
			}
		}

		$shortcode = sprintf( '[gravityview %s]', implode( ' ', $shortcode_attributes ) );

		$output = do_shortcode( $shortcode );

		return $output;
	}
}
