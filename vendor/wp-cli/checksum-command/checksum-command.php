<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_checksum_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_checksum_autoloader ) ) {
	require_once $wpcli_checksum_autoloader;
}

WP_CLI::add_command( 'core', 'Core_Command_Namespace' );
WP_CLI::add_command( 'core verify-checksums', 'Checksum_Core_Command' );

WP_CLI::add_command( 'plugin', 'Plugin_Command_Namespace' );
WP_CLI::add_command( 'plugin verify-checksums', 'Checksum_Plugin_Command' );

