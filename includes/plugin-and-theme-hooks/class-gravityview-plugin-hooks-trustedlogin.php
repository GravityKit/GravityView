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
 * @since 2.13
 */

use GravityView\TrustedLogin\Client;
use GravityView\TrustedLogin\Config;

/**
 * @inheritDoc
 * @since 2.13
 */
class GravityView_Plugin_Hooks_TrustedLogin extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @var string The TL namespace. Used to differentiate admin pages, hook names, CSS, JS. Unique per-plugin.
	 */
	const TRUSTEDLOGIN_NAMESPACE = 'gravityview';

	/**
	 * @var string The public API key from TrustedLogin.
	 */
	const TRUSTEDLOGIN_API_KEY = '3b3dc46c0714cc8e';

	/**
	 * The full namespaced class name for the TL Client class.
	 */
	const TRUSTEDLOGIN_CLASS_NAME = '\GravityView\TrustedLogin\Client';

	/**
	 * @var Client
	 */
	static private $TL_Client;

	/**
	 * This function always exists, so we will always run this class!
	 * @var string
	 */
	public $function_name = 'gravityview';

	/**
	 * @inheritDoc
	 */
	protected $style_handles = array(
		'trustedlogin-' . GravityView_Plugin_Hooks_TrustedLogin::TRUSTEDLOGIN_NAMESPACE,
	);

	/**
	 * @inheritDoc
	 */
	protected $script_handles = array(
		'trustedlogin-' . GravityView_Plugin_Hooks_TrustedLogin::TRUSTEDLOGIN_NAMESPACE,
	);

	/**
	 * Returns the configuration array passed to TrustedLogin
	 *
	 * @see TrustedLogin\Config
	 *
	 * @return array The configuration array for GravityView
	 */
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
			'role' => 'administrator',
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
				'threshold' => 'warning',
			),
			'vendor' => array(
				'namespace' => self::TRUSTEDLOGIN_NAMESPACE,
				'title' => 'GravityView',
				'email' => 'support+{hash}@gravityview.co',
				'website' => 'https://gravityview.co',
				'support_url' => 'https://gravityview.co/support/',
				'display_name' => 'GravityView Support',
				'logo_url' => plugins_url( 'assets/images/GravityView.svg', GRAVITYVIEW_FILE ),
			),
			'paths' => array(
				'css' => plugins_url( 'assets/css/trustedlogin.css', GRAVITYVIEW_FILE ),
			),
			'webhook_url' => 'https://hooks.zapier.com/hooks/catch/28670/bbyi3l4',
		);
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

		add_action( 'trustedlogin/' . self::TRUSTEDLOGIN_NAMESPACE . '/logging/log', array( $this, 'log' ), 10, 4 );

		$config = new Config( self::get_trustedlogin_config() );

		add_filter( 'code_snippets_cap', array( $this, 'filter_code_snippets_cap' ) );
		add_filter( 'code_snippets_network_cap', array( $this, 'filter_code_snippets_cap' ) );

		try {
			self::$TL_Client = new Client( $config );
		} catch ( \Exception $exception ) {
			gravityview()->log->error( $exception->getMessage() );
		}
	}

	/**
	 * Modifies the capability required to access snippets created by the Code Snippets plugin.
	 *
	 * @see https://wordpress.org/plugins/code-snippets/
	 *
	 * @param $default_cap
	 *
	 * @return string If current user is a TrustedLogin support user, returns name of their role. Otherwise, returns original capability.
	 */
	public function filter_code_snippets_cap( $default_cap ) {

		try {
			$config      = new Config( self::get_trustedlogin_config() );
			$logging     = new \GravityView\TrustedLogin\Logging( $config );
			$SupportUser = new \GravityView\TrustedLogin\SupportUser( $config, $logging );

			// If the support user isn't active, don't modify
			if ( ! $SupportUser->is_active() ) {
				return $default_cap;
			}

			return $SupportUser->role->get_name();
		} catch ( Exception $exception ) {
			return $default_cap;
		}
	}

	/**
	 * Override TrustedLogin logging with GravityView's logging system.
	 *
	 * @internal Once GravityView requires PHP 7.1, this will be a private method and we'll use Closure::fromCallable().
	 * @since 2.13
	 * @see https://github.com/php-fig/log/blob/master/Psr/Log/LogLevel.php for log levels
	 *
	 * @param string $message Message to log.
	 * @param string $method Method where the log was called
	 * @param string $level PSR-3 log level
	 * @param \WP_Error|\Exception|mixed $data Optional. Error data. Ignored if $message is WP_Error.
	 */
	public function log( $message, $method = '', $level = 'debug', $data = array() ) {

		$data['method'] = $method;

		gravityview()->log->{$level}( $message, $data );
	}

	/**
	 * Adds TrustedLogin "Grant Access" to the list of GravityView admin pages (for loading scripts, etc.)
	 *
	 * @param string|bool $is_page If false, no. If string, the name of the page (`single`, `settings`, or `views`).
	 * @param string $hook The name of the page to check against. Is passed to the method.
	 *
	 * @return string|bool
	 */
	public function filter_is_admin_page( $is_admin = false ) {
		global $current_screen;

		if( $current_screen && 'gravityview_page_grant-' . self::TRUSTEDLOGIN_NAMESPACE . '-access' === $current_screen->id ) {
			return true;
		}

		return $is_admin;
	}

	/**
	 * Adds the TrustedLogin Access Key to shared Support Port data.
	 *
	 * @param array $localization_data Array of data passed to the Support Port.
	 *
	 * @return array the support port data array with `tl_access_key` key set to the site access key.
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
