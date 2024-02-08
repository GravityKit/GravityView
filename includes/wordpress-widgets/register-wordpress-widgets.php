<?php
/**
 * GravityView WP Widgets
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.6
 */


/**
 * Register GravityView widgets
 *
 * @since 1.6
 * @return void
 */
function gravityview_register_widgets() {

	/** @define "GRAVITYVIEW_DIR" "../../" */
	require_once GRAVITYVIEW_DIR . 'includes/wordpress-widgets/class-gravityview-recent-entries-widget.php';

	register_widget( 'GravityView_Recent_Entries_Widget' );

	require_once GRAVITYVIEW_DIR . 'includes/wordpress-widgets/class-gravityview-search-wp-widget.php';

	register_widget( 'GravityView_Search_WP_Widget' );
}

add_action( 'widgets_init', 'gravityview_register_widgets' );
