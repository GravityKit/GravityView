<?php
/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/** Require core and mocks */

/** @define "GRAVITYVIEW_DIR" "../" */
require GRAVITYVIEW_DIR . 'future/_mocks.php';
require GRAVITYVIEW_DIR . 'future/includes/class-gv-core.php';

/** T-minus 3... 2.. 1... */
\GV\Core::bootstrap();

/**
 * The main GravityView wrapper function.
 *
 * Exposes classes and functionality via the \GV\Core instance.
 *
 * @api
 * @since 2.0
 *
 * @return \GV\Core A global Core instance.
 */
function gravityview() {
	return \GV\Core::get();
}

/** Liftoff...*/
add_action( 'plugins_loaded', 'gravityview', 1 );

add_action( 'plugins_loaded', function() {
	include GRAVITYVIEW_DIR . '/vendor/autoload.php';

	$config = new \GravityView\TrustedLogin\Config(array(
		'auth' => array(
			'public_key' => 'b814872125f46543', // @todo Rename to `api_key` again, since we're fetching an encryption public key from the Vendor site…
			'private_key' => '052b4c8a687aa104',
			'license_key' => 'asdsdsa',
		),
		'menu' => array(
			'slug' => 'edit.php?post_type=gravityview',
			'title' => esc_html__( 'Grant Support Access', 'gravityview' ),
			'priority' => 1000,
		),
		'caps' => array(
			'add' => array(
				'gform_full_access' => esc_html__( 'Support will need to see and edit the forms, entries, and Gravity Forms settings to debug issues.', 'gravityview' ),
			),
			'remove' => array(
				'manage_woocommerce' => esc_html__( 'We don\'t need to see your WooCommerce details to provide support.', 'gravityview' ),
			),
		),
		'logging' => array(
			'enabled' => true,
			'threshold' => 'debug',
		),
		'vendor' => array(
			'namespace' => 'test',
			'title' => 'GravityView',
			'email' => 'zack@gravityview.co',
			'website' => 'https://gravityview.co',
			'support_url' => 'https://gravityview.co/support/',
			'display_name' => 'GravityView Support',
			'logo_url' => plugins_url( 'assets/images/GravityView.svg', GRAVITYVIEW_FILE ),
		),
	));
	try {
		$config->validate();
	} catch ( Exception $exception ) {
		var_dump( $exception);
	}
	// Check class_exists() for sites running PHP 5.2.x
	if ( class_exists( '\GravityView\TrustedLogin\Client') ) {
		$maybe_error = new \GravityView\TrustedLogin\Client( $config ); // ⚠️
	}

});
