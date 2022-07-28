<?php

use WP_CLI\Utils;

class Eval_Command extends WP_CLI_Command {

	/**
	 * Executes arbitrary PHP code.
	 *
	 * Note: because code is executed within a method, global variables need
	 * to be explicitly globalized.
	 *
	 * ## OPTIONS
	 *
	 * <php-code>
	 * : The code to execute, as a string.
	 *
	 * [--skip-wordpress]
	 * : Execute code without loading WordPress.
	 *
	 * ## EXAMPLES
	 *
	 *     # Display WordPress content directory.
	 *     $ wp eval 'echo WP_CONTENT_DIR;'
	 *     /var/www/wordpress/wp-content
	 *
	 *     # Generate a random number.
	 *     $ wp eval 'echo rand();' --skip-wordpress
	 *     479620423
	 *
	 * @when before_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {

		if ( null === Utils\get_flag_value( $assoc_args, 'skip-wordpress' ) ) {
			WP_CLI::get_runner()->load_wordpress();
		}

		eval( $args[0] );
	}
}
