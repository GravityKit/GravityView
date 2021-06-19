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

	// Check class_exists() to verify support for namespacing for clients running PHP 5.2.x
	if ( ! class_exists( '\GravityView\TrustedLogin\Client' ) ) {
		return;
	}

	$namespace = 'test';

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
			'namespace' => $namespace,
			'title' => 'GravityView',
			'email' => 'zack@gravityview.co',
			'website' => 'https://trustedlogin.dev',
			'support_url' => 'https://gravityview.co/support/',
			'display_name' => 'GravityView',
			'logo_url' => plugins_url( 'assets/images/GravityView.svg', GRAVITYVIEW_FILE ),
		),
		'webhook_url' => 'https://hooks.zapier.com/hooks/catch/28670/bbyi3l4'
	));

	try {
		$TL_Client = new \GravityView\TrustedLogin\Client( $config );

		$no_conflict = function( $scripts_or_styles ) use ( $config ) {
			$scripts_or_styles[] = 'trustedlogin-' . $config->ns();
			return $scripts_or_styles;
		};

		add_filter( 'gravityview_noconflict_scripts', $no_conflict );
		add_filter( 'gravityview_noconflict_styles', $no_conflict );

		add_filter( 'gravityview_is_admin_page', function( $is_admin = false ) use ( $namespace ) {
			global $current_screen;

			if( $current_screen && 'gravityview_page_grant-' . $namespace . '-access' === $current_screen->id ) {
				return true;
			}

			return $is_admin;
		});

		/**
		 * Add TrustedLogin Access Key to Support Port data
		 */
		add_filter( 'gravityview/support_port/localization_data', function ( $localization_data = array() ) use ( $TL_Client ) {
			$localization_data['data']['tl_access_key'] = $TL_Client->get_access_key();
			return $localization_data;
		});

	} catch ( \Exception $exception ) {
		gravityview()->log->error( $exception->getMessage() );
	}

});
