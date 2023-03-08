<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Integrations;

use GravityKit\GravityView\Foundation\Helpers\Arr;
use GravityKit\GravityView\Foundation\Helpers\Core as CoreHelpers;
use GravityKit\GravityView\Foundation\Licenses\LicenseManager;
use GravityKit\GravityView\Foundation\ThirdParty\TrustedLogin\Admin as TrustedLoginAdmin;
use GravityKit\GravityView\Foundation\ThirdParty\TrustedLogin\Logging as TrustedLoginLogging;
use GravityKit\GravityView\Foundation\ThirdParty\TrustedLogin\Config as TrustedLoginConfig;
use GravityKit\GravityView\Foundation\ThirdParty\TrustedLogin\Client as TrustedLoginClient;
use GravityKit\GravityView\Foundation\Logger\Framework as LoggerFramework;
use GravityKit\GravityView\Foundation\WP\AdminMenu;
use Exception;

class TrustedLogin {
	const ID = 'gk_foundation_trustedlogin';

	const TL_API_KEY = '3b3dc46c0714cc8e';

	/**
	 * @since 1.0.0
	 *
	 * @var string Access capabilities.
	 */
	private $_capability = 'manage_options';

	/**
	 * @since 1.0.0
	 *
	 * @var TrustedLoginClient TL Client class instance.
	 */
	private $_trustedlogin_client;

	/**
	 * @since 1.0.0
	 *
	 * @var TrustedLogin Class instance.
	 */
	private static $_instance;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function __construct() {
		try {
			$this->_trustedlogin_client = new TrustedLoginClient(
				new TrustedLoginConfig( $this->get_config() )
			);
		} catch ( Exception $e ) {
			LoggerFramework::get_instance()->error( 'Unable to initialize TrustedLogin client: ' . $e->getMessage() );

			return;
		}

		try {
			$this->add_gk_submenu_item();
		} catch ( Exception $e ) {
			LoggerFramework::get_instance()->error( 'Unable to add TrustedLogin to the Foundation menu: ' . $e->getMessage() );

			return;
		}

		add_action( 'trustedlogin/' . self::ID . '/logging/log', [ $this, 'log' ], 10, 4 );
		add_filter( 'gk/foundation/integrations/helpscout/configuration', [ $this, 'add_tl_key_to_helpscout_beacon' ] );
	}

	/**
	 * Returns class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return TrustedLogin
	 */
	public static function get_instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Adds Settings submenu to the GravityKit top-level admin menu.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception TrustedLoginConfig throws an exception when the config object is empty (do not apply to us),
	 *
	 * @return void
	 */
	public function add_gk_submenu_item() {
		$tl_config = new TrustedLoginConfig( $this->get_config() );
		$tl_admin  = new TrustedLoginAdmin( $tl_config, new TrustedLoginLogging( $tl_config ) );

		$page_title = $menu_title = esc_html__( 'Grant Support Access', 'gk-gravityview' );

		AdminMenu::add_submenu_item( [
			'page_title' => $page_title,
			'menu_title' => $menu_title,
			'capability' => $this->_capability,
			'id'         => self::ID,
			'callback'   => [ $tl_admin, 'print_auth_screen' ],
			'order'      => 1,
		], 'bottom' );
	}

	/**
	 * Returns TrustedLogin configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_config() {
		/**
		 * @filter `gk/foundation/integrations/trustedlogin/capabilities` Modifies the capabilities added/removed by TL.
		 *
		 * @since  1.0.0
		 *
		 * @param array $capabilities
		 */
		$capabilities = apply_filters( 'gk/foundation/integrations/trustedlogin/capabilities', [
			'add'    => [
				'gravityview_full_access' => esc_html__( 'We need access to Views to provide great support.', 'gk-gravityview' ),
				'gform_full_access'       => esc_html__( 'We will need to see and edit the forms, entries, and Gravity Forms settings to debug issues.', 'gk-gravityview' ),
				'install_plugins'         => esc_html__( 'We may need to manage plugins in order to debug conflicts on your site and add related GravityView functionality.', 'gk-gravityview' ),
				'update_plugins'          => '',
				'deactivate_plugins'      => '',
				'activate_plugins'        => '',
			],
			'remove' => [
				'manage_woocommerce' => strtr(
					esc_html_x( "We don't need to see your [plugin] details to provide support (if [plugin] is enabled).", 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
					[ 'plugin' => 'WooCommerce' ]
				),
				'view_shop_reports'  => strtr(
					esc_html_x( "We don't need to see your [plugin] details to provide support (if [plugin] is enabled).", 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
					[ 'plugin' => 'Easy Digital Downloads' ]
				),
			],
		] );

		$config = [
			'auth'            => [
				'api_key' => self::TL_API_KEY,
			],
			'menu'            => [
				'slug' => false, // Prevent TL from adding a menu item; we'll do it manually in the add_gk_submenu_item() method.
			],
			'role'            => 'administrator',
			'caps'            => $capabilities,
			'logging'         => [
				'enabled'   => true,
				'threshold' => 'warning',
			],
			'vendor'          => [
				'namespace'    => self::ID,
				'title'        => 'GravityKit',
				'email'        => 'support+{hash}@gravitykit.com',
				'website'      => 'https://www.gravitykit.com',
				'support_url'  => 'https://www.gravitykit.com/support/',
				'display_name' => 'GravityKit Support',
				'logo_url'     => CoreHelpers::get_assets_url( 'gravitykit-logo.svg' ),
			],
			'register_assets' => true,
			'paths'           => [
				'css' => CoreHelpers::get_assets_url( 'trustedlogin/trustedlogin.css' ),
			],
			'webhook_url'     => 'https://hooks.zapier.com/hooks/catch/28670/bbyi3l4',
		];

		$license_manager = LicenseManager::get_instance();

		foreach ( $license_manager->get_licenses_data() as $license_data ) {
			if ( Arr::get( $license_data, 'products' ) && ! $license_manager->is_expired_license( Arr::get( $license_data, 'expiry' ) ) ) {
				Arr::set( $config, 'auth.license_key', Arr::get( $license_data, 'key' ));

				break;
			}
		}

		return $config;
	}

	/**
	 * Overrides TL's internal logging with Foundation's logging.
	 *
	 * @internal  Once we require PHP 7.1, this will be a private method, and we'll use Closure::fromCallable().
	 *
	 * @since     1.0.0
	 *
	 * @param string                     $message Message to log.
	 * @param string                     $method  Method where the log was called.
	 * @param string                     $level   PSR-3 log level {@see https://www.php-fig.org/psr/psr-3/#5-psrlogloglevel}.
	 * @param \WP_Error|\Exception|mixed $data    (optional) Error data. Ignored if $message is WP_Error.
	 *
	 * @return void
	 */
	public function log( $message, $method = '', $level = 'debug', $data = [] ) {
		LoggerFramework::get_instance()->{$level}( $message );
	}

	/**
	 * Updates Help Scout beacon with TL access key.
	 *
	 * @since 1.0.0
	 *
	 * @param array $configuration
	 *
	 * @return array
	 */
	public function add_tl_key_to_helpscout_beacon( $configuration ) {
		Arr::set( $configuration, 'identify.tl_access_key', $this->_trustedlogin_client->get_access_key() );

		return $configuration;
	}
}
