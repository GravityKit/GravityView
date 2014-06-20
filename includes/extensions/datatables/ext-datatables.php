<?php
/**
 * GravityView Extension Main File -- DataTables
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.4
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/** Constants */
if( !defined('GV_DT_FILE') )
	define( 'GV_DT_FILE', __FILE__ );
if ( !defined('GV_DT_URL') )
	define( 'GV_DT_URL', plugin_dir_url( __FILE__ ) );
if ( !defined('GV_DT_DIR') )
	define( 'GV_DT_DIR', plugin_dir_path( __FILE__ ) );

class GV_Extension_DataTables {

	const version = '1.0.0';

	public function __construct() {

		// load DataTables admin logic
		add_action( 'gravityview_include_backend_actions', array( $this, 'backend_actions' ) );

		// load DataTables core logic
		add_action( 'gravityview_init', array( $this, 'core_actions' ) );

		// Register specific template
		add_action( 'gravityview_init', array( $this, 'register_templates' ), 20 );

	}

	function backend_actions() {
		include_once( GV_DT_DIR . 'includes/class-admin-datatables.php' );
	}

	function core_actions() {
		include_once( GV_DT_DIR . 'includes/class-datatables-data.php' );
	}

	function register_templates() {
		include_once( GV_DT_DIR . 'includes/class-datatables-template.php' );
	}


}
new GV_Extension_DataTables;
