<?php

class GravityView_Logging {

	function __construct() {
		add_action( 'gravityview_log_error', array( $this, 'log_error') );
		add_action( 'gravityview_log_debug', array( $this, 'log_debug') );
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
