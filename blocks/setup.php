<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Bail if Gutenberg is not activated.
if ( ! function_exists( 'register_block_type' ) ) {
	return;
}

/**
 * Register required script and style files for Gutenberg editor.
 */
function gv_gut_plugin_gutenberg_assets() {

	// Scripts.
	wp_enqueue_script(
		'gv_gut-gutenberg-js',
		// plugins_url ( 'assets/js/gutenberg.min.js', dirname ( __FILE__ ) ),
		plugins_url( 'assets/js/blocks.js', dirname( __FILE__ ) ),
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components', 'wp-hooks', 'jquery' )
	);

	$views_list_array = array();

	if ( class_exists( 'GVCommon' ) ) {

		$views = GVCommon::get_all_views(
			array(
				'orderby' => 'post_title',
				'order'   => 'ASC'
			)
		);

		$views_list_array = array_map( function ( $view ) {

			return array(
				'value' => $view->ID,
				'label' => $view->post_title
			);
		}, $views );
	}

	wp_localize_script(
		'gv_gut-gutenberg-js',
		'wp_gv_gut_php',
		array(
			'home_page' => home_url(),
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
			'img_url'   => GRAVITYVIEW_GUTENBERG_PLUGIN_URL . 'assets/img/',
			'view_list' => $views_list_array
		)
	);

	wp_enqueue_style(
		'gv_gut-gutenberg-css',
		plugins_url( 'assets/css/gutenberg.min.css', dirname( __FILE__ ) ),
		array( 'wp-edit-blocks' )
	);
}

// Hook: Editor assets.
add_action( 'enqueue_block_editor_assets', 'gv_gut_plugin_gutenberg_assets' );

/**
 * Register categories
 */
add_filter( 'block_categories', function ( $categories, $post ) {

	return array_merge(
		$categories,
		array(
			array( 'slug' => 'gravityview', 'title' => __( 'GravityView', 'gravityviewgutenberg' ) )
		)
	);
}, 10, 2 );

// Include all components
foreach ( glob( GRAVITYVIEW_GUTENBERG_PLUGIN_PATH . 'components/*/*.php' ) as $file ) {
	include $file;
}

// Include all render files
foreach ( glob( GRAVITYVIEW_GUTENBERG_PLUGIN_PATH . 'blocks/*/render.php' ) as $file ) {
	include $file;
	$block_path      = dirname( $file );
	$block_cat       = basename( $block_path );
	$block_name      = 'gravityview/' . $block_cat;
	$block_callback  = 'gravityview_block_render_' . str_replace( '-', '_', $block_cat );
	$attributes_file = file_get_contents( $block_path . '/config.json' );
	$attributes      = json_decode( $attributes_file );

	if ( function_exists( $block_callback ) ) {
		register_block_type( $block_name, array(
			'render_callback' => $block_callback,
			'attributes'      => gv_gut_objectToArray( $attributes ),
		) );
	}
}

/**
 * Convert Objects to array
 *
 * @param object $d
 *
 * @return array $d
 */
function gv_gut_objectToArray( $d ) {

	if ( is_object( $d ) ) {
		$d = get_object_vars( $d );
	}

	if ( is_array( $d ) ) {
		return array_map( __FUNCTION__, $d );
	} else {
		return $d;
	}
}
