<?php

// Exit if accessed directly or if Gutenberg is not enabled
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'register_block_type' ) ) {
	exit;
}

/**
 * Enqueue UI assets
 */
add_action( 'enqueue_block_editor_assets', 'gv_blocks_enqueue_assets' );

function gv_blocks_enqueue_assets() {

	$script = 'assets/js/gv-blocks.js';
	$style  = 'assets/css/gv-blocks.css';

	wp_enqueue_script(
		'gv-blocks-js',
		GV_BLOCKS_PLUGIN_URL . $script,
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components', 'wp-hooks', 'jquery' ),
		filemtime( GV_BLOCKS_PLUGIN_PATH . $script ),
	);

	wp_enqueue_style(
		'gv-blocks-css',
		GV_BLOCKS_PLUGIN_URL . $style,
		array( 'wp-edit-blocks' ),
		filemtime( GV_BLOCKS_PLUGIN_PATH . $style ),
	);

	$views_list_array = array();

	$views = GVCommon::get_all_views(
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
		'gv-blocks-js',
		'GV_BLOCKS',
		array(
			'home_page' => home_url(),
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
			'img_url'   => GV_BLOCKS_PLUGIN_URL . 'assets/img/',
			'view_list' => $views_list_array,
		)
	);

}

/**
 * Register blocks
 */
add_filter( 'block_categories', function ( $categories, $post ) {

	return array_merge(
		$categories,
		array(
			array( 'slug' => 'gravityview', 'title' => __( 'GravityView', 'gv-blocks' ) ),
		)
	);
}, 10, 2 );

/**
 * Register block renderers
 */
foreach ( glob( GV_BLOCKS_PLUGIN_PATH . 'blocks/*/render.php' ) as $file ) {
	include $file;

	$block_path      = dirname( $file );
	$block_cat       = basename( $block_path );
	$block_name      = 'gv-blocks/' . $block_cat;
	$block_callback  = 'gv_blocks_render_' . str_replace( '-', '_', $block_cat );
	$attributes_file = file_get_contents( $block_path . '/config.json' );
	$attributes      = json_decode( $attributes_file, true );

	if ( function_exists( $block_callback ) ) {
		register_block_type( $block_name, array(
			'render_callback' => $block_callback,
			'attributes'      => $attributes,
		) );
	}
}
