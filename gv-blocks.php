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

/**
 * Register custom blocks for Gutenberg
 */
require_once plugin_dir_path( __FILE__ ) . 'blocks/setup.php';

