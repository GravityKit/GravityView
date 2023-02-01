<?php

namespace GravityKit\GravityView\Gutenberg\Blocks;

use GravityKit\GravityView\Gutenberg\Blocks;
use GravityKit\GravityView\Foundation\Helpers\Arr;

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
			'viewId'         => 'id',
			'postId'         => 'post_id',
			'pageSize'       => 'page_size',
			'sortField'      => 'sort_field',
			'sortDirection'  => 'sort_direction',
			'searchField'    => 'search_field',
			'searchValue'    => 'search_value',
			'searchOperator' => 'search_operator',
			'startDate'      => 'start_date',
			'endDate'        => 'end_date',
			'classValue'     => 'class',
			'offset'         => 'offset',
			'singleTitle'    => 'single_title',
			'backLinkLabel'  => 'back_link_label',
		];

		$shortcode_attributes = [];

		foreach ( $block_attributes as $attribute => $value ) {
			$value = esc_attr( sanitize_text_field( $value ) );

			if ( isset( $block_to_shortcode_attributes_map[ $attribute ] ) && ! empty( $value ) ) {
				$shortcode_attributes[] = "{$block_to_shortcode_attributes_map[$attribute]}={$value}";
			}
		}

		$shortcode = sprintf( '[gravityview %s]', implode( ' ', $shortcode_attributes ) );

		if ( Arr::get( $block_attributes, 'previewAsShortcode' ) ) {
			return json_encode( [ 'content' => $shortcode, 'script' => '', 'styles' => '' ] );
		}

		$rendered_shortcode = Blocks::render_shortcode( $shortcode );

		return json_encode( $rendered_shortcode );
	}
}