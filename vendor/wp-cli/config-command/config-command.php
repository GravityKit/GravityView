<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_config_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_config_autoloader ) ) {
	require_once $wpcli_config_autoloader;
}

WP_CLI::add_command( 'config', 'Config_Command' );
