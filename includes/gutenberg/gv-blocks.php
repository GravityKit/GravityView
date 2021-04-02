<?php
/**
 * Plugin Name:       GravityView Blocks
 * Description:       Add GravityView layouts to WordPress posts and pages using Gutenberg editor
 * Version:           1.0.0
 * Author:            GravityView
 * Author URI:        https://gravityview.co
 * Text Domain:       gv-blocks
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path:       /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GV_BLOCKS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'GV_BLOCKS_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
define( 'GV_BLOCKS_PLUGIN_NAME', 'GravityView Blocks' );
define( 'GV_BLOCKS_MIN_WP_VERSION', '5.0' );

add_action( 'plugins_loaded', 'gv_blocks_plugin_load', 1 );

/**
 * Load the plugin
 *
 * @since 1.0.0
 *
 * @return void
 */
function gv_blocks_plugin_load() {

	global $wp_version;

	// Require WordPress 5.0
	if ( version_compare( $wp_version, GV_IMPORT_ENTRIES_MIN_WP, '<' ) ) {
		$notice = sprintf( esc_html__( '%s requires WordPress version %s or newer.', 'gravityview-importer' ), GV_BLOCKS_PLUGIN_NAME, GV_BLOCKS_MIN_WP_VERSION );

		gv_blocks_display_notice( $notice );

		return;
	}

	// Require GravityView
	if ( ! class_exists( 'GravityView_Plugin' ) ) {
		$notice = sprintf( esc_html__( '%s requires GravityView to work.', 'gravityview-importer' ), GV_BLOCKS_PLUGIN_NAME );

		gv_blocks_display_notice( $notice );

		return;
	}

	/**
	 * Register custom blocks for Gutenberg
	 */
	require_once plugin_dir_path( __FILE__ ) . 'blocks/setup.php';
}

/**
 * Notice output in dashboard if WordPress is incompatible.
 *
 * @since 1.0.0
 *
 * @return void
 */
function gv_blocks_display_notice( $notice ) {

	$notice = wpautop( $notice );

	echo "<div class='error'>{$notice}</div>";
}
