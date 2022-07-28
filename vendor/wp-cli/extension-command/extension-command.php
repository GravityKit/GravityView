<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_extension_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_extension_autoloader ) ) {
	require_once $wpcli_extension_autoloader;
}

$wpcli_extension_requires_wp_5_5 = [
	'before_invoke' => static function () {
		if ( WP_CLI\Utils\wp_version_compare( '5.5', '<' ) ) {
			WP_CLI::error( 'Requires WordPress 5.5 or greater.' );
		}
	},
];

WP_CLI::add_command( 'plugin', 'Plugin_Command' );
WP_CLI::add_command( 'plugin auto-updates', 'Plugin_AutoUpdates_Command', $wpcli_extension_requires_wp_5_5 );
WP_CLI::add_command( 'theme', 'Theme_Command' );
WP_CLI::add_command( 'theme auto-updates', 'Theme_AutoUpdates_Command', $wpcli_extension_requires_wp_5_5 );
WP_CLI::add_command( 'theme mod', 'Theme_Mod_Command' );
