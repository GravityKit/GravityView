<?php

namespace GravityKit\GravityView\Gutenberg\Blocks;

use GravityKit\GravityView\Gutenberg\Blocks;
use GravityKit\GravityView\Foundation\Helpers\Arr;

class EntryField {
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
			'title'           => __( 'GravityView Entry Field', 'gk-gravityview' ),
			'render_callback' => [ $this, 'render' ],
			'localization'    => [
				'previewImage' => untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/preview.svg'
			]
		];
	}

	/**
	 * Renders [gvfield] shortcode.
	 *
	 * @since $ver$
	 *
	 * @param array $block_attributes
	 *
	 * @return string $output
	 */
	static function render( $block_attributes = [] ) {
		$block_to_shortcode_attributes_map = [
			'viewId'                => 'view_id',
			'entryId'               => 'entry_id',
			'fieldId'               => 'field_id',
			'fieldSettingOverrides' => 'field_setting_overrides',
		];

		$shortcode_attributes = [];

		foreach ( $block_attributes as $attribute => $value ) {
			$value = esc_attr( sanitize_text_field( $value ) );

			if ( isset( $block_to_shortcode_attributes_map[ $attribute ] ) && ! empty( $value ) ) {
				if ( 'field_setting_overrides' === $attribute ) {
					$shortcode_attributes[] = $value;
				} else {
					$shortcode_attributes[] = sprintf(
						'%s="%s"',
						$block_to_shortcode_attributes_map[ $attribute ],
						str_replace( '"', '\"', $value )
					);
				}
			}
		}

		$shortcode = sprintf( '[gvfield %s]', implode( ' ', $shortcode_attributes ) );

		if ( Arr::get( $block_attributes, 'previewAsShortcode' ) ) {
			return $shortcode;
		}

		$rendered_shortcode = Blocks::render_shortcode( $shortcode );

		return $rendered_shortcode['content'];
	}
}
