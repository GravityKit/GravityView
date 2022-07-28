<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_i18n_autoloader = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $wpcli_i18n_autoloader ) ) {
	require_once $wpcli_i18n_autoloader;
}

if ( class_exists( 'WP_CLI\Dispatcher\CommandNamespace' ) ) {
	WP_CLI::add_command( 'i18n', '\WP_CLI\I18n\CommandNamespace' );
}

WP_CLI::add_command(
	'i18n make-pot',
	'\WP_CLI\I18n\MakePotCommand',
	array(
		'before_invoke' => static function() {
			if ( ! function_exists( 'mb_ereg' ) ) {
				WP_CLI::error( 'The mbstring extension is required for string extraction to work reliably.' );
			}
		},
	)
);

WP_CLI::add_command( 'i18n make-json', '\WP_CLI\I18n\MakeJsonCommand' );

WP_CLI::add_command( 'i18n make-mo', '\WP_CLI\I18n\MakeMoCommand' );

WP_CLI::add_command( 'i18n update-po', '\WP_CLI\I18n\UpdatePoCommand' );
