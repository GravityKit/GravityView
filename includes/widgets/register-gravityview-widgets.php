<?php
/**
 * GravityView default widgets and generic widget class
 *
 * @package   GravityView
 * @license   GPL2+
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2020, Katz Web Services, Inc.
 */



/**
 * Register the default widgets
 *
 * @return void
 */
function gravityview_register_gravityview_widgets() {

	include_once GRAVITYVIEW_DIR . 'includes/widgets/class-gravityview-widget.php';

	include_once GRAVITYVIEW_DIR . 'includes/widgets/search-widget/class-search-widget.php';
	include_once GRAVITYVIEW_DIR . 'includes/widgets/class-gravityview-widget-custom-content.php';
	include_once GRAVITYVIEW_DIR . 'includes/widgets/class-gravityview-widget-export-link.php';
	include_once GRAVITYVIEW_DIR . 'includes/widgets/class-gravityview-widget-gravityforms.php';
	include_once GRAVITYVIEW_DIR . 'includes/widgets/class-gravityview-widget-page-size.php';
	include_once GRAVITYVIEW_DIR . 'includes/widgets/class-gravityview-widget-pagination-info.php';
	include_once GRAVITYVIEW_DIR . 'includes/widgets/class-gravityview-widget-page-links.php';

	if ( class_exists( 'GFPolls' ) ) {
		include_once GRAVITYVIEW_DIR . 'includes/widgets/poll/class-gravityview-widget-poll.php';
	}
}

// Load default widgets
add_action( 'init', 'gravityview_register_gravityview_widgets', 11 );
