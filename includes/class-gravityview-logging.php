<?php

use GravityKit\GravityView\Foundation\Logger\Framework as LoggerFramework;

/**
 * @TODO (Foundation) Deprecate in future versions or write a wrapper for Foundation Logger so that all methods are accessible through gravity()->plugin->log() or something.
 */
final class GravityView_Logging {
	private static $_logger;

	function __construct() {
		// GravityKitFoundation may not yet be available at this point, so we use the included version of Foundation and then swap with the latest version once it's available.
		self::$_logger = LoggerFramework::get_instance( 'gravityview', 'GravityView' );

		add_action(
			'gk/foundation/initialized',
			function ( $foundation ) {
				self::$_logger = $foundation::logger( 'gravityview', 'GravityView' );
			}
		);

		// We're keeping `gravityview_log_*` actions for backward compatibility, but they are unnecessary as any plugin can simply use GravityKitFoundation::logger() functionality.
		add_action( 'gravityview_log_error', array( $this, 'log_error' ), 10, 2 );

		add_action( 'gravityview_log_debug', array( $this, 'log_debug' ), 10, 2 );
	}

	/**
	 * Get the name of the function to print messages for debugging
	 *
	 * This is necessary because `ob_start()` doesn't allow `print_r()` inside it.
	 *
	 * @return string "print_r" or "var_export"
	 */
	static function get_print_function() {
		if ( ob_get_level() > 0 ) {
			$function = 'var_export';
		} else {
			$function = 'print_r';
		}

		return $function;
	}

	static function log_debug( $message = '', $data = null ) {
		$function = self::get_print_function();

		$message = $function( $message, true ) . $function( $data, true );

		self::$_logger->debug( $message );
	}

	static function log_error( $message = '', $data = null ) {
		$function = self::get_print_function();

		$error = array(
			'message'   => $message,
			'data'      => $data,
			'backtrace' => function_exists( 'wp_debug_backtrace_summary' ) ? wp_debug_backtrace_summary( null, 3 ) : '',
		);

		$message = $function( $message, true ) . $function( $error, true );

		self::$_logger->error( $message );
	}
}

new GravityView_Logging();
