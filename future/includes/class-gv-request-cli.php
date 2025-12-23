<?php

namespace GV;

use WP_CLI;

/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Represents a Request originating from the CLI.
 *
 * @since $ver$
 */
final class CLI_Request extends Request {
	/**
	 * Returns the arguments passed to the command line.
	 *
	 * @since $ver$
	 *
	 * @return array<string, string> The arguments as an associative array.
	 */
	public function get_arguments(): array {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return (array) WP_CLI::get_config();
		}

		return $this->parse_argv();
	}

	/**
	 * Parses command line arguments from $argv.
	 *
	 * Supports:
	 * - Long options: --option=value or --option value.
	 * - Short options: -o value.
	 * - Flags: --flag or -f (set to true).
	 * - Positional arguments: collected in numeric keys.
	 *
	 * @since $ver$
	 *
	 * @return array<string, mixed> The parsed arguments.
	 */
	private function parse_argv(): array {
		global $argv;

		if ( empty( $argv ) || ! is_array( $argv ) ) {
			return [];
		}

		$args   = array_values( array_slice( $argv, 1 ) ); // Skip script name.
		$result = [];
		$skip   = [];
		$count  = count( $args );

		for ( $i = 0; $i < $count; $i++ ) {
			if ( isset( $skip[ $i ] ) ) {
				continue;
			}

			$arg = $args[ $i ];

			// Long option (--option=value or --option value or --flag).
			if ( 0 === strpos( $arg, '--' ) ) {
				$option = substr( $arg, 2 );

				if ( false !== strpos( $option, '=' ) ) {
					[ $key, $value ] = explode( '=', $option, 2 );

					$result[ $key ] = $value;
					continue;
				}

				if ( isset( $args[ $i + 1 ] ) && 0 !== strpos( $args[ $i + 1 ], '-' ) ) {
					$result[ $option ] = $args[ $i + 1 ];
					$skip[ $i + 1 ]    = true;
					continue;
				}

				$result[ $option ] = true;

				continue;
			}

			// Short option (-o value or -f).
			if ( 0 === strpos( $arg, '-' ) && strlen( $arg ) > 1 ) {
				$option = substr( $arg, 1 );

				if ( isset( $args[ $i + 1 ] ) && 0 !== strpos( $args[ $i + 1 ], '-' ) ) {
					$result[ $option ] = $args[ $i + 1 ];
					$skip[ $i + 1 ]    = true;
				} else {
					$result[ $option ] = true;
				}

				continue;
			}

			// Positional argument.
			$result[] = $arg;
		}

		return $result;
	}
}
