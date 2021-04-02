<?php

namespace GV\Gutenberg;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GravityView Gutenberg Blocks
 *
 * @since 2.10.2
 */
class Blocks {
	const ASSETS_HANDLE = 'gv-blocks';

	function __construct() {
		if ( ! class_exists( 'GravityView_Plugin' ) || ! function_exists( 'register_block_type' ) ) {
			return;
		}

		require_once( plugin_dir_path( __FILE__ ) . 'blocks/block.php' );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );
		add_filter( 'block_categories', array( $this, 'add_block_category' ) );

		$this->load_blocks();
	}

	/**
	 * Register block renderers
	 *
	 * @since 2.10.2
	 *
	 * @return void
	 */
	public function load_blocks() {
		foreach ( glob( plugin_dir_path( __FILE__ ) . 'blocks/*/block.php' ) as $file ) {
			require_once( $file );

			$block_name  = basename( dirname( $file ) );
			$block_name  = explode( '-', $block_name );
			$block_name  = implode( '_', array_map( 'ucfirst', $block_name ) );
			$block_class = '\GV\Gutenberg\Blocks\Block\\' . $block_name;

			if ( ! is_callable( array( $block_class, 'render' ) ) ) {
				continue;
			}

			$block_class::register();
		}
	}

	/**
	 * Add GravityView category to Gutenberg editor
	 *
	 * @since 2.10.2
	 *
	 * @param array $categories
	 *
	 * @return array
	 */
	public function add_block_category( $categories ) {
		return array_merge(
			$categories,
			array(
				array( 'slug' => 'gravityview', 'title' => __( 'GravityView', 'gravityview' ) ),
			)
		);
	}

	/**
	 * Enqueue UI assets
	 *
	 * @since 2.10.2
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$script = 'assets/js/gv-blocks.js';
		$style  = 'assets/css/gv-blocks.css';

		wp_enqueue_script(
			self::ASSETS_HANDLE,
			plugins_url( '/', __FILE__ ) . $script,
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components', 'wp-hooks', 'jquery' ),
			filemtime( plugin_dir_path( __FILE__ ) . $script )
		);

		wp_enqueue_style(
			self::ASSETS_HANDLE,
			plugins_url( '/', __FILE__ ) . $style,
			array( 'wp-edit-blocks' ),
			filemtime( plugin_dir_path( __FILE__ ) . $style )
		);

		$views = \GVCommon::get_all_views(
			array(
				'orderby' => 'post_title',
				'order'   => 'ASC',
			)
		);

		$views_list_array = array_map( function ( $view ) {
			return array(
				'value' => $view->ID,
				'label' => $view->post_title,
			);
		}, $views );

		wp_localize_script(
			self::ASSETS_HANDLE,
			'GV_BLOCKS',
			array(
				'home_page' => home_url(),
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'img_url'   => plugins_url( '/', __FILE__ ) . 'assets/img/',
				'view_list' => $views_list_array,
			)
		);
	}
}

new Blocks();