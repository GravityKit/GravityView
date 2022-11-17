<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\WP_Action_Logger implementation.
 *
 * @TODO: (Foundation) Deprecate in future versions.
 *
 * Uses the old logging stuff for now.
 */
class WP_Action_Logger extends Logger {

	/**
	 * Logs with an arbitrary level using `do_action` and our
	 *  old action handlers.
	 *
	 * $context['data'] will be passed to the action.
	 *
	 * @param mixed $level The log level.
	 * @param string $message The message to log.
	 * @param array $context The context.
	 *
	 * @return void
	 */
	protected function log( $level, $message, $context ) {
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 );
		$location = $this->interpolate( "{class}{type}{function}", $backtrace[2] );
		$message = $this->interpolate( "[$level, $location] $message", $context );

		/** @see \GravityKit\GravityView\Monolog\Logger */
		\GravityKitFoundation::logger()->log( $level, $message, $context );
	}
}
