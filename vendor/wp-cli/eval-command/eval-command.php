<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_eval_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_eval_autoloader ) ) {
	require_once $wpcli_eval_autoloader;
}

WP_CLI::add_command( 'eval', 'Eval_Command' );
WP_CLI::add_command( 'eval-file', 'EvalFile_Command' );
