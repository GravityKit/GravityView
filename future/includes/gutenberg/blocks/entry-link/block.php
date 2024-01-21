<?php

namespace GravityKit\GravityView\Gutenberg\Blocks;

use GravityKit\GravityView\Gutenberg\Blocks;
use GravityKit\GravityView\Foundation\Helpers\Arr;

class EntryLink {
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
		return [
			'title'           => __( 'GravityView Entry Link', 'gk-gravityview' ),
			'render_callback' => [ $this, 'render' ],
			'localization'    => [
				'previewImage' => untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/preview.svg'
			]
		];
	}

	/**
	 * Renders [gv_entry_link] shortcode.
	 *
	 * @since 2.17
	 *
	 * @param array $block_attributes
	 *
	 * @return string $output
	 */
	static function render( $block_attributes = [] ) {
		$block_to_shortcode_attributes_map = [
			'viewId'       => 'view_id',
			'entryId'      => 'entry_id',
			'action'       => 'action',
			'postId'       => 'post_id',
			'returnFormat' => 'return',
			'linkAtts'     => 'link_atts',
			'fieldValues'  => 'field_values',
			'content'      => 'content',
		];

		$shortcode_attributes = [];

		foreach ( $block_attributes as $attribute => $value ) {
			$value = esc_attr( sanitize_text_field( $value ) );

			if ( isset( $block_to_shortcode_attributes_map[ $attribute ] ) && ! empty( $value ) ) {
				$shortcode_attributes[] = sprintf(
					'%s="%s"',
					$block_to_shortcode_attributes_map[ $attribute ],
					str_replace( '"', '\"', $value )
				);
			}
		}

		if ( ! empty( $block_attributes['content'] ) ) {
			$shortcode = sprintf(
				'[gv_entry_link %s]%s[/gv_entry_link]',
				implode( ' ', $shortcode_attributes ),
				wp_kses_post( $block_attributes['content'] )
			);
		} else {
			$shortcode = sprintf( '[gv_entry_link %s/]', implode( ' ', $shortcode_attributes ) );
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			add_filter( 'gravityview/entry_link/add_query_args', '__return_false' );
		}

		if ( Arr::get( $block_attributes, 'previewAsShortcode' ) ) {
			return $shortcode;
		}

		$rendered_shortcode = Blocks::render_shortcode( $shortcode );

		return $rendered_shortcode['content'];
	}
}
