<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_maintenance_mode_autoloader = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $wpcli_maintenance_mode_autoloader ) ) {
	require_once $wpcli_maintenance_mode_autoloader;
}

WP_CLI::add_command( 'maintenance-mode', '\WP_CLI\MaintenanceMode\MaintenanceModeCommand' );
