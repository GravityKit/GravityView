<?php

use WP_CLI\Utils;

class Server_Command extends WP_CLI_Command {

	/**
	 * Launches PHP's built-in web server for a specific WordPress installation.
	 *
	 * Uses `php -S` to launch a web server serving the WordPress webroot.
	 * <http://php.net/manual/en/features.commandline.webserver.php>
	 *
	 * Importantly, PHP's built-in web server doesn't support `.htaccess` files.
	 * If this is a requirement, please use a more advanced web server.
	 *
	 * ## OPTIONS
	 *
	 * [--host=<host>]
	 * : The hostname to bind the server to.
	 * ---
	 * default: localhost
	 * ---
	 *
	 * [--port=<port>]
	 * : The port number to bind the server to.
	 * ---
	 * default: 8080
	 * ---
	 *
	 * [--docroot=<path>]
	 * : The path to use as the document root. If the path global parameter is
	 * set, the default value is it.
	 *
	 * [--config=<file>]
	 * : Configure the server with a specific .ini file.
	 *
	 * ## EXAMPLES
	 *
	 *     # Make the instance available on any address (with port 8080)
	 *     $ wp server --host=0.0.0.0
	 *     PHP 5.6.9 Development Server started at Tue May 24 01:27:11 2016
	 *     Listening on http://0.0.0.0:8080
	 *     Document root is /
	 *     Press Ctrl-C to quit.
	 *
	 *     # Run on port 80 (for multisite)
	 *     $ wp server --host=localhost.localdomain --port=80
	 *     PHP 5.6.9 Development Server started at Tue May 24 01:30:06 2016
	 *     Listening on http://localhost1.localdomain1:80
	 *     Document root is /
	 *     Press Ctrl-C to quit.
	 *
	 *     # Configure the server with a specific .ini file
	 *     $ wp server --config=development.ini
	 *     PHP 7.0.9 Development Server started at Mon Aug 22 12:09:04 2016
	 *     Listening on http://localhost:8080
	 *     Document root is /
	 *     Press Ctrl-C to quit.
	 *
	 * @when before_wp_load
	 */
	public function __invoke( $_, $assoc_args ) {
		$defaults   = array(
			'host'    => 'localhost',
			'port'    => 8080,
			'docroot' => ! is_null( WP_CLI::get_runner()->config['path'] ) ? WP_CLI::get_runner()->config['path'] : false,
			'config'  => get_cfg_var( 'cfg_file_path' ),
		);
		$assoc_args = array_merge( $defaults, $assoc_args );

		$docroot = $assoc_args['docroot'];

		if ( ! $docroot ) {
			$config_path = WP_CLI::get_runner()->project_config_path;

			if ( ! $config_path ) {
				$docroot = ABSPATH;
			} else {
				$docroot = dirname( $config_path );
			}
		}

		// Get the path to the router file
		$command_root = Utils\phar_safe_path( dirname( __DIR__ ) );
		$router_path  = $command_root . '/router.php';
		if ( ! file_exists( $router_path ) ) {
			WP_CLI::error( "Couldn't find router.php" );
		}
		$cmd = Utils\esc_cmd(
			'%s -S %s -t %s -c %s %s',
			WP_CLI::get_php_binary(),
			$assoc_args['host'] . ':' . $assoc_args['port'],
			$docroot,
			$assoc_args['config'],
			Utils\extract_from_phar( $router_path )
		);

		$descriptors = array( STDIN, STDOUT, STDERR );

		// https://bugs.php.net/bug.php?id=60181
		$options = array();
		if ( Utils\is_windows() ) {
			$options['bypass_shell'] = true;
		}

		exit( proc_close( proc_open( $cmd, $descriptors, $pipes, null, null, $options ) ) );
	}
}
