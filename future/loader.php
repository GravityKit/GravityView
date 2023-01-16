<?php
/**
 * Just an early preloader for the future code.
 *
 * Compatible with all PHP versions syntax-wise.
 */

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Do not allow activation if PHP version is lower than GV_MIN_PHP_VERSION
 */
if ( version_compare( phpversion(), GV_MIN_PHP_VERSION, '<' ) ) {
	$php_notices = array(
		esc_html__( 'GravityView requires PHP [php_required_version] or newer.', 'gk-gravityview' ),
		esc_html__( 'You are using version [php_installed_version].', 'gk-gravityview' ),
		esc_html__( 'Please ask your host to upgrade PHP on the server.', 'gk-gravityview' ),
	);

	foreach ( $php_notices as &$notice ) {
		$notice = strtr(
			$notice,
			array(
				'[php_required_version]'  => GV_MIN_PHP_VERSION,
				'[php_installed_version]' => phpversion()
			)
		);
	}

	if ( 'cli' === php_sapi_name() ) {
		printf( join( ' ', $php_notices ) );
	} else {
		add_action( 'admin_notices', function () use ( $php_notices ) {
			$floaty_image     = plugins_url( 'assets/images/astronaut-200x263.png', GRAVITYVIEW_FILE );
			$floaty_image_alt = esc_attr__( 'The GravityKit Astronaut Says:', 'gk-gravityview' );

			list( $requires, $installed, $call_to_action ) = $php_notices;

			echo <<<HTML
<div class="error">
	<div style="margin-top: 1em;">
		<img src="{$floaty_image}" alt="{$floaty_image_alt}" style="float: left; height: 5em; margin-right: 1em;" />
		<h3 style="font-size:16px; margin: 0 0 8px 0;">
			{$requires}
		</h3>
		<p>{$installed}</p>
		<p>{$call_to_action}</p>
	</div>
</div>
HTML;
		} );
	}

	deactivate_plugins( GRAVITYVIEW_FILE );

	return;
}

require_once GRAVITYVIEW_DIR . 'vendor/autoload.php';
require_once GRAVITYVIEW_DIR . 'vendor_prefixed/autoload.php';

GravityKit\GravityView\Foundation\Core::register( GRAVITYVIEW_FILE );

/** @define "GRAVITYVIEW_DIR" "../" */
require GRAVITYVIEW_DIR . 'future/gravityview.php';
