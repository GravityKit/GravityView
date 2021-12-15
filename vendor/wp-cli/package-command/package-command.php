<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_package_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_package_autoloader ) && ! class_exists( 'Package_Command' ) ) {
	require_once $wpcli_package_autoloader;
}
WP_CLI::add_command( 'package', 'Package_Command' );
