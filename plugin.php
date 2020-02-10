<?php
/**
 * Plugin Name: GravityView Gutenberg
 * Description: The best, easiest way to display Gravity Forms entries on your website.
 * Text Domain: gravityviewgutenberg
 * Author: Yoosef Ahkami
 * Version: 1.0.0
 * License: ISC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


define( 'GRAVITYVIEW_GUTENBERG_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'GRAVITYVIEW_GUTENBERG_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
define( 'GRAVITYVIEW_GUTENBERG_PLUGIN_ASSETS_URL', GRAVITYVIEW_GUTENBERG_PLUGIN_URL . 'assets/' );

/**
 * Register custom blocks for Gutenberg.
 */
require_once plugin_dir_path( __FILE__ ) . 'blocks/setup.php';

