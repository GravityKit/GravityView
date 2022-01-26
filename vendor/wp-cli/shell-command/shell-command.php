<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_shell_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_shell_autoloader ) ) {
	require_once $wpcli_shell_autoloader;
}

WP_CLI::add_command( 'shell', 'Shell_Command' );
