<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_search_replace_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_search_replace_autoloader ) ) {
	require_once $wpcli_search_replace_autoloader;
}

WP_CLI::add_command( 'search-replace', 'Search_Replace_Command' );
