<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_server_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_server_autoloader ) ) {
	require_once $wpcli_server_autoloader;
}

WP_CLI::add_command(
	'server',
	'Server_Command',
	array(
		'before_invoke' => function() {
			$min_version = '5.4';
			if ( version_compare( PHP_VERSION, $min_version, '<' ) ) {
				WP_CLI::error( "The `wp server` command requires PHP {$min_version} or newer." );
			}
		},
	)
);
