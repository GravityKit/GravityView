<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_super_admin_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_super_admin_autoloader ) ) {
	require_once $wpcli_super_admin_autoloader;
}

WP_CLI::add_command(
	'super-admin',
	'Super_Admin_Command',
	array(
		'before_invoke' => function () {
			if ( ! is_multisite() ) {
				WP_CLI::error( 'This is not a multisite installation.' );
			}
		},
	)
);
