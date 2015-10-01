<?php

final class GravityView_Logging {

	private static $errors = array();
	private static $notices = array();

	function __construct() {

		add_action( 'gravityview_log_error', array( $this, 'log_error'), 10, 2 );

		add_action( 'gravityview_log_debug', array( $this, 'log_debug'), 10, 2 );

		// Enable debug with Gravity Forms Logging Add-on
	    add_filter( 'gform_logging_supported', array( $this, 'enable_gform_logging' ) );

	    // Load Debug Bar integration
	    add_filter( 'debug_bar_panels', array( $this, 'add_debug_bar' ) );

	}

	/**
	 * Add integration with the Debug Bar plugin. It's awesome.
	 *
	 * @link http://wordpress.org/plugins/debug-bar/
	 */
	function add_debug_bar( $panels ) {

		if(!class_exists('Debug_Bar_Panel')) { return; }

		if(!class_exists('GravityView_Debug_Bar')) {
			include_once( GRAVITYVIEW_DIR . 'includes/class-debug-bar.php' );
		}

		$panels[] = new GravityView_Debug_Bar;

		return $panels;
	}

	/**
	 * Enables debug with Gravity Forms logging add-on
	 * @param array $supported_plugins List of plugins
	 */
	public function enable_gform_logging( $supported_plugins ) {
	    $supported_plugins['gravityview'] = 'GravityView';
	    return $supported_plugins;
	}

	static function get_notices() {
		return self::$notices;
	}

	static function get_errors() {
		return self::$errors;
	}

	/**
	 * Get the name of the function to print messages for debugging
	 *
	 * This is necessary because `ob_start()` doesn't allow `print_r()` inside it.
	 *
	 * @return string "print_r" or "var_export"
	 */
	static function get_print_function() {
		if( ob_get_level() > 0 ) {
			$function = 'var_export';
		} else {
			$function = 'print_r';
		}

		return $function;
	}

	static function log_debug( $message = '', $data = null ) {

		$function = self::get_print_function();

		self::$notices[] = array(
			'message' => $function( $message, true ),
			'data' => $data
		);

		if ( class_exists("GFLogging") ) {
			GFLogging::include_logger();
	        GFLogging::log_message( 'gravityview', $function( $message, true ) . $function($data, true), KLogger::DEBUG );
	    }
	}

	static function log_error( $message = '', $data = null  ) {

		$function = self::get_print_function();

		self::$errors[] = array( 'message' => $message, 'data' => $data );

		if ( class_exists("GFLogging") ) {
		    GFLogging::include_logger();
		    GFLogging::log_message( 'gravityview', $function ( $message, true ) . $function ($data, true), KLogger::ERROR );
		}
	}

}

new GravityView_Logging;
