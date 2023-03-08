<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Licenses;

use Exception;
use GravityKit\GravityView\Foundation\Core as FoundationCore;
use GravityKit\GravityView\Foundation\WP\AdminMenu;
use GravityKit\GravityView\Foundation\Translations\Framework as TranslationsFramework;
use GravityKit\GravityView\Foundation\Logger\Framework as LoggerFramework;
use GravityKit\GravityView\Foundation\Helpers\Core as CoreHelpers;

class Framework {
	const ID = 'gk_licenses';

	const AJAX_ROUTER = 'licenses';

	/**
	 * @since 1.0.0
	 *
	 * @var Framework Class instance.
	 */
	private static $_instance;

	/**
	 * @since 1.0.3
	 *
	 * @var LicenseManager Class instance.
	 */
	private $_license_manager;

	/**
	 * @since 1.0.3
	 *
	 * @var ProductManager Class instance.
	 */
	private $_product_manager;

	/**
	 * @since 1.0.0
	 *
	 * @var array User permissions to manage licenses/products.
	 */
	private $_permissions;

	private function __construct() {
		$permissions = [
			// Licenses
			'view_licenses'       =>
				( ! is_super_admin() && current_user_can( 'gk_foundation_view_licenses' ) ) ||
				( ! is_multisite() && current_user_can( 'manage_options' ) ) ||
				( is_multisite() && current_user_can( 'manage_network_options' ) && CoreHelpers::is_network_admin() ),
			'manage_licenses'     =>
				( ! is_super_admin() && current_user_can( 'gk_foundation_manage_licenses' ) ) ||
				( ! is_multisite() && current_user_can( 'manage_options' ) ) ||
				( is_multisite() && current_user_can( 'manage_network_options' ) && CoreHelpers::is_network_admin() ),
			// Products
			'view_products'       =>
				( ! is_super_admin() && current_user_can( 'gk_foundation_view_products' ) ) ||
				( ! is_multisite() && current_user_can( 'install_plugins' ) ) ||
				( is_multisite() && ( current_user_can( 'activate_plugins' ) || current_user_can( 'manage_network_plugins' ) ) ),
			'install_products'    =>
				( ! is_super_admin() && current_user_can( 'gk_foundation_install_products' ) ) ||
				( ! is_multisite() && current_user_can( 'install_plugins' ) ) ||
				( is_multisite() && current_user_can( 'manage_network_plugins' ) && CoreHelpers::is_network_admin() ),
			'activate_products'   =>
				( ! is_super_admin() && current_user_can( 'gk_foundation_activate_products' ) ) ||
				( ! is_multisite() && current_user_can( 'activate_plugins' ) ) ||
				( is_multisite() && ( current_user_can( 'activate_plugins' ) || current_user_can( 'manage_network_plugins' ) ) ),
			'deactivate_products' =>
				( ! is_super_admin() && current_user_can( 'gk_foundation_deactivate_products' ) ) ||
				( ! is_multisite() && current_user_can( 'install_plugins' ) ) ||
				( is_multisite() && ( current_user_can( 'activate_plugins' ) || current_user_can( 'manage_network_plugins' ) ) ),
		];

		/**
		 * @filter `gk/foundation/licenses/permissions` Modifies permissions to access Licenses functionality.
		 *
		 * @since  1.0.0
		 *
		 * @param array $permissions Permissions.
		 */
		$this->_permissions = apply_filters( 'gk/foundation/licenses/permissions', $permissions );
	}

	/**
	 * Returns class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Framework
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Initializes the License framework.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		if ( ! $this->current_user_can( 'view_licenses' ) && ! $this->current_user_can( 'view_products' ) ) {
			return;
		}

		if ( ! is_admin() || did_action( 'gk/foundation/licenses/initialized' ) ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		add_filter( 'gk/foundation/ajax/' . self::AJAX_ROUTER . '/routes', [ $this, 'configure_ajax_routes' ] );

		$this->_product_manager = ProductManager::get_instance();
		$this->_license_manager = LicenseManager::get_instance();

		$this->_product_manager->init();
		$this->_license_manager->init();

		EDD::get_instance()->init();

		$this->add_gk_submenu_item();

		/**
		 * @action `gk/foundation/licenses/initialized` Fires when the class has finished initializing.
		 *
		 * @since  1.0.0
		 *
		 * @param $this
		 */
		do_action( 'gk/foundation/licenses/initialized', $this );
	}

	/**
	 * Configures AJAX routes handled by this class.
	 *
	 * @since 1.0.0
	 *
	 * @see   FoundationCore::process_ajax_request()
	 *
	 * @param array $routes AJAX route to class method map.
	 *
	 * @return array
	 */
	public function configure_ajax_routes( array $routes ) {
		return array_merge( $routes, [
			'get_app_data' => [ $this, 'ajax_get_app_data' ],
		] );
	}

	/**
	 * AJAX request to get products and/or licenses data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload
	 *
	 * @throws Exception
	 *
	 * @return array
	 */
	public function ajax_get_app_data( array $payload ) {
		$payload = wp_parse_args( $payload, [
			'skip_cache' => false,
		] );

		$response = [];

		if ( ! $this->current_user_can( 'view_products' ) && ! $this->current_user_can( 'view_licenses' ) ) {
			throw new Exception( esc_html__( 'You do not have permission to view this page.', 'gk-gravityview' ) );
		}

		// When skipping cache, we need to first refresh licenses and then products since the products data depends on the licenses data.
		if ( $this->current_user_can( 'view_licenses' ) ) {
			$response['licenses'] = LicenseManager::get_instance()->ajax_get_licenses_data( $payload );
		}

		if ( $this->current_user_can( 'view_products' ) ) {
			try {
				$response['products'] = ProductManager::get_instance()->ajax_get_products_data( $payload );
			} catch ( Exception $e ) {
				throw new Exception( $e->getMessage() );
			}
		}

		return $response;
	}

	/**
	 * Returns framework title used in admin menu and the UI.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_framework_title() {
		if ( ! $this->current_user_can( 'view_licenses' ) && ! $this->current_user_can( 'view_products' ) ) {
			return '';
		}

		if ( ! $this->current_user_can( 'view_licenses' ) ) {
			return esc_html__( 'Products', 'gk-gravityview' );
		} else if ( ! $this->current_user_can( 'view_products' ) ) {
			return esc_html__( 'Licenses', 'gk-gravityview' );
		} else {
			return esc_html__( 'Products & Licenses', 'gk-gravityview' );
		}
	}

	/**
	 * Adds Licenses submenu to the GravityKit top-level admin menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_gk_submenu_item() {
		AdminMenu::add_submenu_item( [
			'page_title' => $this->get_framework_title(),
			'menu_title' => $this->get_framework_title(),
			'capability' => 'manage_options',
			'id'         => self::ID,
			'callback'   => '__return_false', // Content will be injected into #wpbody by gk-licenses.js (see /UI/Licenses/src/main-prod.js)
			'order'      => 1,
		], 'top' );
	}

	/**
	 * Enqueues UI assets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page Current page.
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function enqueue_assets( $page ) {
		if ( strpos( $page, self::ID ) === false ) {
			return;
		}

		$script = 'licenses.js';
		$style  = 'licenses.css';

		if ( ! file_exists( CoreHelpers::get_assets_path( $script ) ) ||
		     ! file_exists( CoreHelpers::get_assets_path( $style ) )
		) {
			LoggerFramework::get_instance()->warning( 'UI assets not found.' );

			return;
		}

		wp_enqueue_script(
			self::ID,
			CoreHelpers::get_assets_url( $script ),
			[ 'wp-i18n' ],
			filemtime( CoreHelpers::get_assets_path( $script ) )
		);

		$script_data = array_merge(
			[
				'appTitle'       => $this->get_framework_title(),
				'isNetworkAdmin' => CoreHelpers::is_network_admin(),
				'permissions'    => $this->_permissions,
			],
			FoundationCore::get_ajax_params( self::AJAX_ROUTER )
		);

		if ( $this->_permissions['view_licenses'] ) {
			$script_data['licensesData'] = LicenseManager::get_instance()->ajax_get_licenses_data( [] );
		}

		wp_localize_script(
			self::ID,
			'gkLicenses',
			[ 'data' => $script_data ]
		);

		wp_enqueue_style(
			self::ID,
			CoreHelpers::get_assets_url( $style ),
			[],
			filemtime( CoreHelpers::get_assets_path( $style ) )
		);

		// WP's forms.css interferes with our styles.
		wp_deregister_style( 'forms' );
		wp_register_style( 'forms', false );

		// Load UI translations using the text domain of the plugin that instantiated Foundation.
		$registered_plugins            = FoundationCore::get_instance()->get_registered_plugins();
		$foundation_source_plugin_data = CoreHelpers::get_plugin_data( $registered_plugins['foundation_source'] );
		TranslationsFramework::get_instance()->load_frontend_translations( $foundation_source_plugin_data['TextDomain'], '', 'gk-foundation' );
	}

	/**
	 * Checks if the current user has a certain permission.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	function current_user_can( $permission ) {
		return isset( $this->_permissions[ $permission ] ) ? $this->_permissions[ $permission ] : false;
	}

	/**
	 * Returns Product Manager class instance.
	 *
	 * @since 1.0.3
	 *
	 * @return ProductManager
	 */
	function product_manager() {
		return $this->_product_manager;
	}

	/**
	 * Returns License Manager class instance.
	 *
	 * @since 1.0.3
	 *
	 * @return LicenseManager
	 */
	function license_manager() {
		return $this->_license_manager;
	}

	/**
	 * Returns link to product search in the licensing page.
	 *
	 * @since 1.0.5
	 *
	 * @param string $product_id Product ID (EDD download ID).
	 *
	 * @return string
	 */
	function get_link_to_product_search( $product_id ) {
		$admin_page = 'admin.php?page=' . self::ID;

		$admin_url = CoreHelpers::is_network_admin() ? network_admin_url( $admin_page ) : admin_url( $admin_page );

		return add_query_arg(
			[
				'filter' => 'custom',
				'search' => 'id:' . $product_id,
			],
			$admin_url
		);
	}
}
