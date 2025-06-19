<?php

namespace GravityKit\GravityView\Gutenberg\Blocks;

use GravityKit\GravityView\Gutenberg\Blocks;
use GravityKit\GravityView\Foundation\Helpers\Arr;
use GFFormDisplay;

class View {
	/**
	 * Modifies block meta.
	 *
	 * This method is called by class-gv-gutenberg.php before registering the block.
	 *
	 * @since 2.17
	 *
	 * @param array $block_meta
	 *
	 * @return array
	 */
	public function modify_block_meta( $block_meta ) {
		return array(
			'title'           => __( 'GravityView View', 'gk-gravityview' ),
			'render_callback' => array( $this, 'render' ),
			'localization'    => array(
				'previewImage' => untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/preview.svg',
			),
		);
	}

	/**
	 * Renders [gravityview] shortcode.
	 *
	 * @since 2.17
	 *
	 * @param array $block_attributes
	 *
	 * @return string $output
	 */
	static function render( $block_attributes = array() ) {
		$shortcode_attributes        = [];
		$mapped_shortcode_attributes = \GV\Shortcodes\gravityview::map_block_atts_to_shortcode_atts( $block_attributes );

		foreach ( $mapped_shortcode_attributes as $attribute => $value ) {
			$value = esc_attr( sanitize_text_field( $value ) );
			if ( empty( $value ) ) {
				continue;
			}

			$shortcode_attributes[] = sprintf(
				'%s="%s"',
				$attribute,
				str_replace( '"', '\"', $value )
			);
		}

		$shortcode = sprintf( '[gravityview %s]', implode( ' ', $shortcode_attributes ) );

		if ( Arr::get( $block_attributes, 'previewAsShortcode' ) ) {
			return wp_json_encode(
				array(
					'content' => $shortcode,
					'script'  => '',
					'styles'  => '',
				)
			);
		}

		$rendered_shortcode = Blocks::render_shortcode( $shortcode );

		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return $rendered_shortcode['content'];
		}

		return json_encode( $rendered_shortcode );
	}
}
