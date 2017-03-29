<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\WP_Action_Logger implementation.
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

		$backtrace = debug_backtrace();
		$location = $this->interpolate( "{class}{type}{function}", $backtrace[2] );

		$message = $this->interpolate( "[$level, $location] $message", $context );

		switch ( $level ):
			case LogLevel::EMERGENCY:
			case LogLevel::ALERT:
			case LogLevel::CRITICAL:
			case LogLevel::ERROR:
				$action = 'error';
				break;
			case LogLevel::WARNING:
			case LogLevel::NOTICE:
			case LogLevel::INFO:
			case LogLevel::DEBUG:
				$action = 'debug';
				break;
		endswitch;

		if ( defined( 'DOING_GRAVITYVIEW_TESTS' ) ) {
			/** Let's make this testable! */
			do_action(
				sprintf( 'gravityview_log_%s_test', $action ),
				$this->interpolate( $message, $context ),
				empty( $context['data'] ) ? array() : $context['data']
			);
		}
		
		do_action(
			sprintf( 'gravityview_log_%s', $action ),
			$this->interpolate( $message, $context ),
			empty( $context['data'] ) ? array() : $context['data']
		);
	}
}
