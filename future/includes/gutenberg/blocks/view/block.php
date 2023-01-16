<?php

namespace GravityKit\GravityView\Gutenberg\Blocks;

class View {
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
			'title'           => __( 'GravityView View', 'gk-gravityview' ),
			'render_callback' => [ $this, 'render' ],
			'localization'    => [
				'previewImage' => untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/preview.svg'
			]
		];
	}

	/**
	 * Renders [gravityview] shortcode.
	 *
	 * @since $ver$
	 *
	 * @param array $block_attributes
	 *
	 * @return string $output
	 */
	static function render( $block_attributes = [] ) {
		$block_to_shortcode_attributes_map = [
			'view_id'         => 'id',
			'page_size'       => 'page_size',
			'sort_field'      => 'sort_field',
			'sort_direction'  => 'sort_direction',
			'search_field'    => 'search_field',
			'search_value'    => 'search_value',
			'search_operator' => 'search_operator',
			'start_date'      => 'start_date',
			'end_date'        => 'end_date',
			'class_value'     => 'class',
			'offset'          => 'offset',
			'single_title'    => 'single_title',
			'back_link_label' => 'back_link_label',
			'post_id'         => 'post_id',
		];

		$shortcode_attributes = [];

		foreach ( $block_attributes as $attribute => $value ) {
			$value = esc_attr( sanitize_text_field( $value ) );

			if ( isset( $block_to_shortcode_attributes_map[ $attribute ] ) && ! empty( $value ) ) {
				$shortcode_attributes[] = "{$block_to_shortcode_attributes_map[$attribute]}={$value}";
			}
		}

		$shortcode = sprintf( '[gravityview %s]', implode( ' ', $shortcode_attributes ) );

		return do_shortcode( $shortcode );
	}
}
