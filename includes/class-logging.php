<?php

class GravityView_Logging {

	function __construct() {
		add_action( 'gravityview_log_error', array( $this, 'log_error') );
		add_action( 'gravityview_log_debug', array( $this, 'log_debug') );

		// Enable debug with Gravity Forms Logging Add-on
	    add_filter( 'gform_logging_supported', array( $this, 'enable_gform_logging' ) );
	}

	/**
	 * Enables debug with Gravity Forms logging add-on
	 * @param array $supported_plugins List of plugins
	 */
	public static function enable_gform_logging( $supported_plugins ) {
	    $supported_plugins['gravityview'] = 'GravityView';
	    return $supported_plugins;
	}

	function log_debug( $message = '' ) {
		if ( class_exists("GFLogging") ) {
			GFLogging::include_logger();
	        GFLogging::log_message( 'gravityview', $message, KLogger::DEBUG );
	    }
	}

	function log_error( $message = '' ) {
		if ( class_exists("GFLogging") ) {
		    GFLogging::include_logger();
		    GFLogging::log_message( 'gravityview', $message, KLogger::ERROR );
		}
	}

}

new GravityView_Logging;
