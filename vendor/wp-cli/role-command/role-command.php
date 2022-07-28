<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_role_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_role_autoloader ) ) {
	require_once $wpcli_role_autoloader;
}

WP_CLI::add_command( 'cap', 'Capabilities_Command' );
WP_CLI::add_command( 'role', 'Role_Command' );
