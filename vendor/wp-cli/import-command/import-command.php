<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_import_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_import_autoloader ) ) {
	require_once $wpcli_import_autoloader;
}

WP_CLI::add_command( 'import', 'Import_Command' );
