<?php
/**
 * GravityView default widgets and generic widget class
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */



/**
 * Register the default widgets
 * @todo Move somehere logical
 * @return void
 */
function gravityview_register_gravityview_widgets() {

	include_once( GRAVITYVIEW_DIR .'includes/widgets/class-gravityview-widget.php' );

	include_once( GRAVITYVIEW_DIR .'includes/widgets/class-gravityview-widget-pagination-info.php' );
	include_once( GRAVITYVIEW_DIR .'includes/widgets/class-gravityview-widget-page-links.php' );
	include_once( GRAVITYVIEW_DIR .'includes/widgets/class-gravityview-widget-custom-content.php' );
	include_once( GRAVITYVIEW_DIR .'includes/widgets/search-widget/class-search-widget.php' );

	if( class_exists('GFPolls') ) {
		include_once( GRAVITYVIEW_DIR .'includes/widgets/poll/class-gravityview-widget-poll.php' );
	}

}

// Load default widgets
add_action( 'init', 'gravityview_register_gravityview_widgets', 11 );