<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_media_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_media_autoloader ) ) {
	require_once $wpcli_media_autoloader;
}

/**
 * Check for Imagick and GD extensions availability.
 *
 * @throws \WP_CLI\ExitException
 */
$wpcli_media_assert_image_editor_support = function () {
	if ( ! wp_image_editor_supports() ) {
		WP_CLI::error(
			'No support for generating images found. '
			. 'Please install the Imagick or GD PHP extensions.'
		);
	}
};

WP_CLI::add_command(
	'media',
	'Media_Command',
	[ 'before_invoke' => $wpcli_media_assert_image_editor_support ]
);
