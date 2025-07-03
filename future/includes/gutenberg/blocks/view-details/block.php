<?php

namespace GravityKit\GravityView\Gutenberg\Blocks;

use GravityKit\GravityView\Gutenberg\Blocks;
use GravityKit\GravityView\Foundation\Helpers\Arr;
use GVCommon;

class ViewDetails {
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
			'title'           => __( 'GravityView View Details', 'gk-gravityview' ),
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
		$block_to_shortcode_attributes_map = array(
			'viewId' => 'id',
			'detail' => 'detail',
			'secret' => 'secret',
		);

		$preview_as_shortcode = Arr::get( $block_attributes, 'previewAsShortcode' );
		$is_rest_request      = GVCommon::is_rest_request();

		$shortcode_attributes = array();

		foreach ( $block_attributes as $attribute => $value ) {
			$value = esc_attr( sanitize_text_field( $value ) );

			if ( isset( $block_to_shortcode_attributes_map[ $attribute ] ) && ! empty( $value ) ) {
				if ( 'secret' === $attribute && $preview_as_shortcode && $is_rest_request ) {
					$value = '*********';
				}

				$shortcode_attributes[] = sprintf(
					'%s="%s"',
					$block_to_shortcode_attributes_map[ $attribute ],
					str_replace( '"', '\"', $value )
				);
			}
		}

		$shortcode = sprintf( '[gravityview %s]', implode( ' ', $shortcode_attributes ) );

		if ( $preview_as_shortcode && $is_rest_request ) {
			return $shortcode;
		}

		$rendered_shortcode = Blocks::render_shortcode( $shortcode );

		return $rendered_shortcode['content'];
	}
}
