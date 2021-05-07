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
	const MIN_WP_VERSION = '5.2';

	function __construct() {
		global $wp_version;

		if ( ! class_exists( 'GravityView_Plugin' ) ||
		     ! function_exists( 'register_block_type' ) ||
		     version_compare( $wp_version, self::MIN_WP_VERSION, '<' )
		) {
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
		$script     = 'assets/js/gv-blocks.js';
		$style      = 'assets/css/gv-blocks.css';
		$asset_file = include( gravityview()->plugin->dir() . 'assets/js/gv-blocks.asset.php' );

		wp_enqueue_script(
			self::ASSETS_HANDLE,
			gravityview()->plugin->url() . $script,
			$asset_file['dependencies'],
			filemtime( gravityview()->plugin->dir() . $script )
		);

		wp_enqueue_style(
			self::ASSETS_HANDLE,
			gravityview()->plugin->url() . $style,
			array( 'wp-edit-blocks' ),
			filemtime( gravityview()->plugin->dir() . $style )
		);

		$views = \GVCommon::get_all_views(
			array(
				'orderby' => 'post_title',
				'order'   => 'ASC',
			)
		);

		$views_list_array = array_map( function ( $view ) {

			$post_title = empty( $view->post_title ) ? __('(no title)', 'gravityview') : $view->post_title;
			$post_title = esc_html( sprintf('%s #%d', $post_title, $view->ID ) );

			return array(
				'value' => $view->ID,
				'label' => $post_title,
			);
		}, $views );

		wp_localize_script(
			self::ASSETS_HANDLE,
			'GV_BLOCKS',
			array(
				'home_page' => home_url(),
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'img_url'   => gravityview()->plugin->url( 'assets/images/' ),
				'view_list' => $views_list_array,
			)
		);
	}
}

new Blocks();
