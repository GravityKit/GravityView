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
	#include GRAVITYVIEW_DIR . 'vendor/autoload.php';
	include_once( GRAVITYVIEW_DIR . 'trustedlogin/autoload.php' );

	$config = new \GravityView\TrustedLogin\Config(array(
		'auth' => array(
			'public_key' => '6346688830182b64', // @todo Rename to `api_key` again, since we're fetching an encryption public key from the Vendor site…
			'license_key' => gravityview()->plugin->settings->get('license_key'),
		),
		'menu' => array(
			'slug' => 'edit.php?post_type=gravityview',
			'title' => esc_html__( 'Grant Support Access', 'gravityview' ),
			'priority' => 1400,
			'position' => 100, // TODO: This should be okay not being set, but it's throwing a warning about needing to be integer
		),
		'caps' => array(
			'add' => array(
				'gravityview_full_access' => esc_html__( 'We need access to Views to provide great support.', 'gravityview' ),
				'gform_full_access' => esc_html__( 'We will need to see and edit the forms, entries, and Gravity Forms settings to debug issues.', 'gravityview' ),
				'install_plugins' => esc_html__( 'We may need to manage plugins in order to debug conflicts on your site and add related GravityView functionality.', 'gravityview' ),
				'update_plugins' => '',
				'deactivate_plugins' => '',
				'activate_plugins' => '',
			),
			'remove' => array(
				'manage_woocommerce' => sprintf( esc_html__( 'We don\'t need to see your %1$s details to provide support (if %1$s is enabled).', 'gravityview' ), 'WooCommerce' ),
				'view_shop_reports'  => sprintf( esc_html__( 'We don\'t need to see your %1$s details to provide support (if %1$s is enabled).', 'gravityview' ), 'Easy Digital Downloads' ),
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
			'website' => 'https://trustedlogin.dev',
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
