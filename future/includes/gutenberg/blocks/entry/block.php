<?php

namespace GravityKit\GravityView\Gutenberg\Blocks;

class Entry {
	/**
	 * Modifies block meta.
	 *
	 * This method is called by class-gv-gutenberg.php before registering the block.
	 *
	 * @since $ver$
	 *
	 * @param array $block_meta
	 *
	 * @return array
	 */
	public function modify_block_meta( $block_meta ) {
		return [
			'title'           => __( 'GravityView Entry', 'gk-gravityview' ),
			'render_callback' => [ $this, 'render' ],
			'localization'    => [
				'previewImage' => untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/preview.svg'
			]
		];
	}

	/**
	 * Renders [gventry] shortcode.
	 *
	 * @since $ver$
	 *
	 * @param array $block_attributes
	 *
	 * @return string $output
	 */
	static function render( $block_attributes = [] ) {
		$block_to_shortcode_attributes_map = [
			'entry_id' => 'id',
			'view_id'  => 'view_id',
		];

		$shortcode_attributes = [];

		foreach ( $block_attributes as $attribute => $value ) {
			$value = esc_attr( sanitize_text_field( $value ) );

			if ( isset( $block_to_shortcode_attributes_map[ $attribute ] ) && ! empty( $value ) ) {
				$shortcode_attributes[] = sprintf( '%s="%s"', $block_to_shortcode_attributes_map[ $attribute ], $value );
			}
		}

		$shortcode = sprintf( '[gventry %s]', implode( ' ', $shortcode_attributes ) );

		return do_shortcode( $shortcode ) ?: __( 'No entry found.', 'gk-gravityview' );
	}
}
