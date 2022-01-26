<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_rewrite_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_rewrite_autoloader ) ) {
	require_once $wpcli_rewrite_autoloader;
}

WP_CLI::add_command( 'rewrite', 'Rewrite_Command' );
