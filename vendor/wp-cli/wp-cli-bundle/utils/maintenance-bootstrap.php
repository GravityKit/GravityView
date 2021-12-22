<?php

$wpcli_maintenance_autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( is_readable( $wpcli_maintenance_autoloader ) ) {
	require_once $wpcli_maintenance_autoloader;
}

WP_CLI::add_command( 'contrib-list', 'WP_CLI\Maintenance\Contrib_list_Command' );
WP_CLI::add_command( 'milestones-after', 'WP_CLI\Maintenance\Milestones_After_Command' );
WP_CLI::add_command( 'milestones-since', 'WP_CLI\Maintenance\Milestones_Since_Command' );
WP_CLI::add_command( 'release-date', 'WP_CLI\Maintenance\Release_Date_Command' );
WP_CLI::add_command( 'release-notes', 'WP_CLI\Maintenance\Release_Notes_Command' );
WP_CLI::add_command( 'replace-label', 'WP_CLI\Maintenance\Replace_Label_Command' );
