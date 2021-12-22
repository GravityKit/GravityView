<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_core_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_core_autoloader ) ) {
	require_once $wpcli_core_autoloader;
}

WP_CLI::add_command( 'core', 'Core_Command' );
