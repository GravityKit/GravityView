<?php

namespace GV\Gutenberg\Blocks\Block;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block class extended by individual blocks
 *
 * @since 2.10.2
 */
abstract class Block {

	const BLOCK_NAME = 'block';

	/**
	 * Render block shortcode
	 *
	 * @since 2.10.2
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function render( $attributes = array() ) {
		return '';
	}

	/**
	 * Register block
	 *
	 * @since 2.10.2
	 *
	 * @return void
	 */
	public static function register() {
		$block_class = get_called_class();

		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type( 'gv-blocks/' . $block_class::BLOCK_NAME, array(
			'render_callback' => array( $block_class, 'render' ),
			'attributes'      => $block_class::get_block_attributes(),
		) );
	}

	/**
	 * Get block attributes
	 *
	 * @see   https://developer.wordpress.org/block-editor/reference-guides/block-api/block-attributes/
	 *
	 * @since 2.10.2
	 *
	 * @return array
	 */
	public static function get_block_attributes() {
		$reflector       = new \ReflectionClass( get_called_class() );
		$attributes_file = dirname( $reflector->getFileName() ) . '/config.json';

		if ( ! file_exists( $attributes_file ) ) {
			return array();
		}

		try {
			$attributes = json_decode( file_get_contents( $attributes_file ), true );
		} catch ( \Exception $e ) {
			$attributes = array();
		}

		return $attributes;
	}
}
