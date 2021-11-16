<?php

if ( ! defined( 'WP_CLI_ROOT' ) ) {
	define( 'WP_CLI_ROOT', dirname( __DIR__ ) );
}

/**
 * Compatibility with PHPUnit 6+
 */
if ( class_exists( 'PHPUnit\Runner\Version' ) ) {
	require_once __DIR__ . '/phpunit6-compat.php';
}

if ( file_exists( WP_CLI_ROOT . '/vendor/autoload.php' ) ) {
	define( 'WP_CLI_VENDOR_DIR', WP_CLI_ROOT . '/vendor' );
} elseif ( file_exists( dirname( dirname( WP_CLI_ROOT ) ) . '/autoload.php' ) ) {
	define( 'WP_CLI_VENDOR_DIR', dirname( dirname( WP_CLI_ROOT ) ) );
}

require_once WP_CLI_VENDOR_DIR . '/autoload.php';
require_once WP_CLI_ROOT . '/php/utils.php';

