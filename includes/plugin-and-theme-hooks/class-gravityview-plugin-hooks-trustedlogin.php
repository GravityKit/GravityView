<?php
/**
 * Integrate TrustedLogin with GravityView
 *
 * @file      class-gravityview-plugin-hooks-trustedlogin.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
 * @link      https://gravityview.co
 * @copyright Copyright 2021, Katz Web Services, Inc.
 *
 * @since 1.15.2
 */

use GravityView\TrustedLogin\Client;
use GravityView\TrustedLogin\Config;

/**
 * @inheritDoc
 * @since 2.11
 */
class GravityView_Plugin_Hooks_TrustedLogin extends GravityView_Plugin_and_Theme_Hooks {

	const TRUSTEDLOGIN_NAMESPACE = 'test';

	const TRUSTEDLOGIN_API_KEY = '07abf14e66223832';

	const TRUSTEDLOGIN_CLASS_NAME = '\GravityView\TrustedLogin\Client';

	/**
	 * @var Client
	 */
	static private $TL_Client;

	/**
	 * Always run this!
	 * @var string
	 */
	public $function_name = 'gravityview';

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $style_handles = array(
		'trustedlogin-' . GravityView_Plugin_Hooks_TrustedLogin::TRUSTEDLOGIN_NAMESPACE,
	);

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $script_handles = array(
		'trustedlogin-' . GravityView_Plugin_Hooks_TrustedLogin::TRUSTEDLOGIN_NAMESPACE,
	);

	private function get_trustedlogin_config() {
		return array(
			'auth' => array(
				'api_key' => self::TRUSTEDLOGIN_API_KEY,
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
				'namespace' => self::TRUSTEDLOGIN_NAMESPACE,
				'title' => 'GravityView',
				'email' => 'zack+{hash}@gravityview.co',
				'website' => 'https://trustedlogin.dev',
				'support_url' => 'https://gravityview.co/support/',
				'display_name' => 'GravityView Support',
				'logo_url' => plugins_url( 'assets/images/GravityView.svg', GRAVITYVIEW_FILE ),
			),
			'webhook_url' => 'https://hooks.zapier.com/hooks/catch/28670/bbyi3l4',
		);
	}

	public function filter_is_admin_page( $is_admin = false ) {
		global $current_screen;

		if( $current_screen && 'gravityview_page_grant-' . self::TRUSTEDLOGIN_NAMESPACE . '-access' === $current_screen->id ) {
			return true;
		}

		return $is_admin;
	}

	protected function add_hooks() {
		parent::add_hooks();

		include_once( GRAVITYVIEW_DIR . 'trustedlogin/autoload.php' );

		// Check class_exists() to verify support for namespacing for clients running PHP 5.2.x
		if ( ! class_exists( self::TRUSTEDLOGIN_CLASS_NAME ) ) {
			return;
		}

		add_filter( 'gravityview_is_admin_page', array( $this, 'filter_is_admin_page' ) );

		add_filter( 'gravityview/support_port/localization_data', array( $this, 'add_localization_data' ) );

		$config = new Config( self::get_trustedlogin_config() );

		try {
			self::$TL_Client = new Client( $config );
		} catch ( \Exception $exception ) {
			gravityview()->log->error( $exception->getMessage() );
		}
	}

	/**
	 * Adds the TrustedLogin Access Key to shared Support Port data.
	 */
	public function add_localization_data( $localization_data = array() ) {

		if ( ! self::$TL_Client ) {
			return $localization_data;
		}

		$localization_data['data']['tl_access_key'] = self::$TL_Client->get_access_key();

		return $localization_data;
	}

}

new GravityView_Plugin_Hooks_TrustedLogin;
