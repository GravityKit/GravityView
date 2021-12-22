<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_db_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_db_autoloader ) ) {
	require_once $wpcli_db_autoloader;
}

WP_CLI::add_command( 'db', 'DB_Command' );
