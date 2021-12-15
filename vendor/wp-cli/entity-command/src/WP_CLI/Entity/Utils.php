<?php

namespace WP_CLI\Entity;

class Utils {

	/**
	 * Check whether any input is passed to STDIN.
	 *
	 * @return bool
	 */
	public static function has_stdin() {
		$handle  = fopen( 'php://stdin', 'r' );
		$read    = array( $handle );
		$write   = null;
		$except  = null;
		$streams = stream_select( $read, $write, $except, 0 );
		fclose( $handle );

		return 1 === $streams;
	}
}
