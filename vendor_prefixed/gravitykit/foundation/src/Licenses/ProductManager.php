<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Licenses;

use Exception;
use GravityKit\GravityView\Foundation\Core;
use GravityKit\GravityView\Foundation\Helpers\Arr;
use GravityKit\GravityView\Foundation\Logger\Framework as LoggerFramework;
use GravityKit\GravityView\Foundation\Encryption\Encryption;
use GravityKit\GravityView\Foundation\Helpers\Core as CoreHelpers;
use GravityKit\GravityView\Foundation\Licenses\WP\WPUpgraderSkin;
use Plugin_Upgrader;

class ProductManager {
	const EDD_PRODUCTS_API_ENDPOINT = 'https://www.gravitykit.com/edd-api/products/';

	const EDD_PRODUCTS_API_KEY = 'e4c7321c4dcf342c9cb078e27bf4ba97'; // Public key.

	const EDD_PRODUCTS_API_TOKEN = 'e031fd350b03bc223b10f04d8b5dde42'; // Public token.

	/**
	 * @since 1.0.0
	 *
	 * @var ProductManager Class instance.
	 */
	private static $_instance;

	/**
	 * Returns class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return ProductManager
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Initializes the class.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		static $initialized;

		if ( $initialized ) {
			return;
		}

		add_filter( 'gk/foundation/ajax/' . Framework::AJAX_ROUTER . '/routes', [ $this, 'configure_ajax_routes' ] );

		add_filter( 'plugin_action_links', [ $this, 'display_product_action_links' ], 10, 2 );

		$this->update_submenu_badge_count();

		$initialized = true;
	}

	/**
	 * Configures AJAX routes handled by this class.
	 *
	 * @since 1.0.0
	 *
	 * @see   Core::process_ajax_request()
	 *
	 * @param array $routes AJAX action to class method map.
	 *
	 * @return array
	 */
	public function configure_ajax_routes( array $routes ) {
		return array_merge( $routes, [
			'activate_product'   => [ $this, 'ajax_activate_product' ],
			'deactivate_product' => [ $this, 'ajax_deactivate_product' ],
			'install_product'    => [ $this, 'ajax_install_product' ],
			'update_product'     => [ $this, 'ajax_update_product' ],
			'get_products'       => [ $this, 'ajax_get_products_data' ],
		] );
	}

	/**
	 * AJAX request wrapper for the install_product() method.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload
	 *
	 * @throws Exception
	 *
	 * @return array{path: string, active: bool, network_activated: bool, activation_error: null|string}
	 */
	public function ajax_install_product( array $payload ) {
		$payload = wp_parse_args( $payload, [
			'id'       => null,
			'download' => null,
			'activate' => false,
		] );

		if ( ! Framework::get_instance()->current_user_can( 'install_products' ) ) {
			throw new Exception( esc_html__( 'You do not have a permission to perform this action.', 'gk-gravityview' ) );
		}

		return $this->install_product( $payload );
	}

	/**
	 * Installs a product.
	 *
	 * @since 1.0.0
	 *
	 * @param array $product
	 *
	 * @throws Exception
	 *
	 * @return array{path: string, active: bool, network_activated: bool, activation_error: null|string}
	 */
	public function install_product( array $product ) {
		if ( ! file_exists( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' ) ) {
			throw new Exception( esc_html__( 'Unable to load core WordPress files required to install the product.', 'gk-gravityview' ) );
		}

		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

		$product_id = $product['id'];

		$product_download_link = null;

		$license_manager = LicenseManager::get_instance();

		if ( empty( $product['download'] ) ) {
			$licenses_data = $license_manager->get_licenses_data();

			foreach ( $licenses_data as $key => $license_data ) {
				if ( $license_manager->is_expired_license( $license_data['expiry'] ) || empty( $license_data['products'] ) || ! isset( $license_data['products'][ $product_id ] ) ) {
					continue;
				}

				try {
					$license = $license_manager->check_license( $key );
				} catch ( Exception $e ) {
					LoggerFramework::get_instance()->warning( "Unable to verify license key ${key} when installing product ID ${product_id}: " . $e->getMessage() );

					continue;
				}

				if ( empty( $license['products'][ $product_id ]['download'] ) ) {
					continue;
				}

				$product_download_link = $license['products'][ $product_id ]['download'];

				break;
			}
		}

		if ( ! $product_download_link ) {
			throw new Exception( esc_html__( 'Unable to locate product download link.', 'gk-gravityview' ) );
		}

		$installer = new Plugin_Upgrader( new WPUpgraderSkin() );

		try {
			$installer->install( $product_download_link );
		} catch ( Exception $e ) {
			$error = join( ' ', [
				esc_html__( 'Installation failed.', 'gk-gravityview' ),
				$e->getMessage()
			] );

			throw new Exception( $error );
		}

		if ( ! $installer->result ) {
			throw new Exception( esc_html__( 'Installation failed.', 'gk-gravityview' ) );
		}

		$product_path = $installer->plugin_info();

		$activation_error = null;

		if ( ! is_plugin_active( $product_path ) && ! empty( $product['activate'] ) ) {
			try {
				$this->activate_product( $product_path );
			} catch ( Exception $e ) {
				$activation_error = $e->getMessage();
			}
		}

		return [
			'path'              => $product_path,
			'active'            => is_plugin_active( $product_path ),
			'network_activated' => is_plugin_active_for_network( $product_path ),
			'activation_error'  => $activation_error,
		];
	}

	/**
	 * AJAX request wrapper for the activate_product() method.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload
	 *
	 * @throws Exception
	 *
	 * @return array{active: bool, network_activated: bool}
	 */
	public function ajax_activate_product( array $payload ) {
		$payload = wp_parse_args( $payload, [
			'path' => null,
		] );

		if ( ! Framework::get_instance()->current_user_can( 'activate_products' ) ) {
			throw new Exception( esc_html__( 'You do not have a permission to perform this action.', 'gk-gravityview' ) );
		}

		return $this->activate_product( $payload['path'] );
	}

	/**
	 * Activates a product.
	 *
	 * @since 1.0.0
	 *
	 * @param string $product
	 *
	 * @throws Exception
	 *
	 * @return array{active: bool, network_activated: bool}
	 */
	public function activate_product( $product ) {
		if ( $this->is_product_active_in_current_context( $product ) ) {
			throw new Exception( esc_html__( 'Product is already active.', 'gk-gravityview' ) );
		}

		$result = activate_plugin( $product, false, CoreHelpers::is_network_admin() );

		if ( is_wp_error( $result ) || ! $this->is_product_active_in_current_context( $product ) ) {
			throw new Exception( esc_html__( 'Could not activate the product.', 'gk-gravityview' ) );
		}

		return [
			'active'            => is_plugin_active( $product ),
			'network_activated' => is_plugin_active_for_network( $product ),
		];
	}

	/**
	 * AJAX request wrapper for the deactivate_product() method.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload
	 *
	 * @throws Exception
	 *
	 * @return array
	 */
	public function ajax_deactivate_product( array $payload ) {
		$payload = wp_parse_args( $payload, [
			'path' => null,
		] );

		if ( ! Framework::get_instance()->current_user_can( 'deactivate_products' ) ) {
			throw new Exception( esc_html__( 'You do not have a permission to perform this action.', 'gk-gravityview' ) );
		}

		return $this->deactivate_product( $payload['path'] );
	}

	/**
	 * Deactivates a product.
	 *
	 * @since 1.0.0
	 *
	 * @param string $product
	 *
	 * @throws Exception
	 *
	 * @return array{active: bool, network_activated: bool}
	 */
	public function deactivate_product( $product ) {
		if ( ! $this->is_product_active_in_current_context( $product ) ) {
			throw new Exception( esc_html__( 'Product in not active.', 'gk-gravityview' ) );
		}

		deactivate_plugins( $product, false, CoreHelpers::is_network_admin() );

		if ( $this->is_product_active_in_current_context( $product ) ) {
			throw new Exception( esc_html__( 'Could not deactivate the product.', 'gk-gravityview' ) );
		}

		return [
			'active'            => is_plugin_active( $product ),
			'network_activated' => is_plugin_active_for_network( $product ),
		];
	}

	/**
	 * AJAX request wrapper for the update_product() method.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload
	 *
	 * @throws Exception
	 *
	 * @return array{active: bool, network_activated: bool, installed_version: string, update_available: bool}
	 */
	public function ajax_update_product( array $payload ) {
		$payload = wp_parse_args( $payload, [
			'path' => null,
		] );

		if ( ! Framework::get_instance()->current_user_can( 'activate_products' ) ) {
			throw new Exception( esc_html__( 'You do not have a permission to perform this action.', 'gk-gravityview' ) );
		}

		return $this->update_product( $payload['path'] );
	}

	/**
	 * Updates a product.
	 *
	 * @since 1.0.0
	 *
	 * @param string $product_path
	 *
	 * @throws Exception
	 *
	 * @return array{active: bool, network_activated: bool, installed_version: string, update_available: bool}
	 */
	public function update_product( $product_path ) {
		if ( ! file_exists( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' ) ) {
			throw new Exception( esc_html__( 'Unable to load core WordPress files required to install the product.', 'gk-gravityview' ) );
		}

		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

		// This is an edge case when for some reason the update_plugins transient is not set or the product is not marked as needing an update.
		$update_plugins_transient_filter = function () {
			return EDD::get_instance()->check_for_product_updates( new \stdClass() );
		};

		// Tampering with the user-agent header (e.g., done by the WordPress Classifieds Plugin) breaks the update process.
		$lock_user_agent_header = function ( $args, $url ) {
			if ( strpos( $url, 'gravitykit.com' ) !== false ) {
				$args['user-agent'] = 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url();
			}

			return $args;
		};

		$updater = new Plugin_Upgrader( new WPUpgraderSkin() );

		try {
			add_filter( 'pre_site_transient_update_plugins', $update_plugins_transient_filter );
			add_filter( 'http_request_args', $lock_user_agent_header, 100, 2 );

			$updater->upgrade( $product_path );

			remove_filter( 'pre_site_transient_update_plugins', $update_plugins_transient_filter );
			remove_filter( 'http_request_args', $lock_user_agent_header, 100 );
		} catch ( Exception $e ) {
			$error = join( ' ', [
				esc_html__( 'Update failed.', 'gk-gravityview' ),
				isset( $updater->strings[ $e->getMessage() ] ) ? $updater->strings[ $e->getMessage() ] : $e->getMessage(),
			] );

			throw new Exception( $error );
		}

		if ( ! $updater->result ) {
			throw new Exception( esc_html__( 'Installation failed.', 'gk-gravityview' ) );
		}

		$activation_error = null;

		try {
			$this->activate_product( $product_path );
		} catch ( Exception $e ) {
			$activation_error = $e->getMessage();
		}

		$products_data = $this->get_products_data();

		return [
			'active'            => $products_data[ $product_path ]['active'],
			'network_activated' => $products_data[ $product_path ]['network_activated'],
			'installed_version' => $products_data[ $product_path ]['installed_version'],
			'update_available'  => $products_data[ $product_path ]['update_available'],
			'activation_error'  => $activation_error,
		];
	}

	/**
	 * Returns a list of all GravityKit products from the API grouped by category (e.g., plugins, extensions, etc.).
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception
	 *
	 * @return array
	 */
	public function get_remote_products() {
		try {
			$response = Helpers::query_api(
				self::EDD_PRODUCTS_API_ENDPOINT,
				[
					'key'   => self::EDD_PRODUCTS_API_KEY,
					'token' => self::EDD_PRODUCTS_API_TOKEN
				]
			);
		} catch ( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		$remote_products    = Arr::get( $response, 'products', [] );
		$formatted_products = [];

		if ( empty( $response ) ) {
			throw new Exception( esc_html__( 'Invalid product information received from the API.', 'gk-gravityview' ) );
		}

		foreach ( $remote_products as $remote_product ) {
			$categories = Arr::get( $remote_product, 'info.category', [] );

			if ( empty( $categories ) ) {
				continue;
			}

			foreach ( $categories as $category ) {
				$category_slug = Arr::get( $category, 'slug' );
				$category_name = Arr::get( $category, 'name' );

				if ( 'bundles' === $category_slug ) {
					continue;
				}

				if ( ! Arr::get( $formatted_products, $category_slug ) ) {
					$formatted_products[ $category_slug ] = [
						'category' => $category_name,
						'products' => [],
					];
				}

				$icons = unserialize( Arr::get( $remote_product, 'readme.icons', '' ) );

				$banners = unserialize( Arr::get( $remote_product, 'readme.banners', '' ) );

				$sections = unserialize( Arr::get( $remote_product, 'readme.sections', '' ) );

				$formatted_products[ $category_slug ]['products'][] = [
					'id'                  => Arr::get( $remote_product, 'info.id' ),
					'slug'                => Arr::get( $remote_product, 'info.slug' ),
					'text_domain'         => Arr::get( $remote_product, 'info.textdomain' ),
					'coming_soon'         => Arr::get( $remote_product, 'info.coming_soon' ),
					'title'               => Arr::get( $remote_product, 'info.title' ),
					'excerpt'             => Arr::get( $remote_product, 'info.excerpt' ),
					'buy_link'            => Arr::get( $remote_product, 'info.buy_url' ),
					'link'                => esc_url( Arr::get( $remote_product, 'info.link', '' ) ),
					'icon'                => esc_url( Arr::get( $remote_product, 'info.icon', '' ) ),
					'icons'               => [
						'1x' => esc_url( Arr::get( $icons, '1x', '' ) ),
						'2x' => esc_url( Arr::get( $icons, '2x', '' ) ),
					],
					'banners'             => [
						'low'  => esc_url( Arr::get( $banners, 'low', '' ) ),
						'high' => esc_url( Arr::get( $banners, 'high', '' ) ),
					],
					'sections'            => [
						'description' => Arr::get( $sections, 'description' ),
						'changelog'   => Arr::get( $sections, 'changelog' ),
					],
					'server_version'      => Arr::get( $remote_product, 'licensing.version' ),
					'modified_date'       => Arr::get( $remote_product, 'info.modified_date' ),
					'docs'                => esc_url( Arr::get( $remote_product, 'info.docs_url', '' ) ),
					'system_requirements' => Arr::get( $remote_product, 'system_requirements', [] ),
					'plugin_dependencies' => Arr::get( $remote_product, 'plugin_dependencies', [] ),
				];
			}
		}

		return $formatted_products;
	}

	/**
	 * AJAX request wrapper for the {@see get_products_data()} method.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload
	 *
	 * @throws Exception
	 *
	 * @return array
	 */
	public function ajax_get_products_data( array $payload ) {
		if ( ! Framework::get_instance()->current_user_can( 'view_products' ) ) {
			throw new Exception( esc_html__( 'You do not have a permission to perform this action.', 'gk-gravityview' ) );
		}

		$payload = wp_parse_args( $payload, [
			'group_by_category' => true,
			'skip_cache'        => false,
		] );

		$products_data = $this->get_products_data( $payload );

		foreach ( $products_data as &$category ) {
			foreach ( $category['products'] as &$product ) {
				// Unset properties that are not needed in the UI.
				foreach ( [ 'icons', 'banners', 'sections' ] as $property ) {
					if ( isset( $product[ $property ] ) ) {
						unset( $product[ $property ] );
					}
				}

				// Encrypt license keys.
				$product['licenses'] = array_map( function ( $key ) {
					return Encryption::get_instance()->encrypt( $key, false, Core::get_request_unique_string() );;
				}, $product['licenses'] );
			}
		}

		return array_values( $products_data );
	}

	/**
	 * Returns a list of all GravityKit products with associated installation/activation/licensing data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args (optional) Additional arguments. Default: ['skip_cache' => false, 'group_by_category' => false, 'key_by' => 'path'].
	 *
	 * @throws Exception
	 *
	 * @return array
	 */
	public function get_products_data( array $args = [] ) {
		$args = wp_parse_args( $args, [
			'skip_cache'        => false,
			'group_by_category' => false,
			'key_by'            => 'path',
		] );

		$cache_id = Framework::ID . '/products';

		$products = ! $args['skip_cache'] ? get_site_transient( $cache_id ) : null;

		if ( ! $products ) {
			try {
				$products = $this->get_remote_products();
			} catch ( Exception $e ) {
				throw new Exception( $e->getMessage() );
			}

			// json_encode() the object as serialize() may break due to unsupported characters.
			set_site_transient( $cache_id, json_encode( $products ), DAY_IN_SECONDS );
		} else if ( ! is_array( $products ) ) { // Backward compatibility for serialized data (used in earlier Foundation versions).
			$products = json_decode( $products, true );
		}

		if ( empty( $products ) ) {
			LoggerFramework::get_instance()->warning( 'Products data is empty.' );

			return [];
		}

		$product_license_map = LicenseManager::get_instance()->get_product_license_map();

		$flattened_products = [];

		// If a product is installed, add additional information to the products object.
		foreach ( $products as &$data ) {
			foreach ( $data['products'] as &$product_data ) {
				$installed_product = CoreHelpers::get_installed_plugin_by_text_domain( $product_data['text_domain'] );

				/**
				 * @filter `gk/foundation/settings/{$product_slug}/settings-url` Sets link to the product settings page.
				 *
				 * @since  1.0.3
				 *
				 * @param string $settings_url URL to the product settings page.
				 */
				$product_settings_url = apply_filters( "gk/foundation/settings/{$product_data['slug']}/settings-url", '' );

				$product_data = array_merge( $product_data, [
					'id'                         => $product_data['id'],
					'text_domain'                => $installed_product ? $installed_product['text_domain'] : $product_data['text_domain'],
					'installed'                  => ! is_null( $installed_product ),
					'path'                       => $installed_product ? $installed_product['path'] : null,
					'plugin_file'                => $installed_product ? $installed_product['plugin_file'] : null,
					'installed_version'          => $installed_product ? $installed_product['version'] : null,
					'update_available'           => $installed_product && version_compare( $installed_product['version'], $product_data['server_version'], '<' ),
					'active'                     => $installed_product ? $installed_product['active'] : false,
					'network_activated'          => $installed_product ? $installed_product['network_activated'] : false,
					'licenses'                   => isset( $product_license_map[ $product_data['id'] ] ) ? $product_license_map[ $product_data['id'] ] : [],
					'failed_system_requirements' => $this->validate_product_system_requirements( $product_data['system_requirements'] ),
					'failed_plugin_dependencies' => $this->validate_product_plugin_dependencies( $product_data['plugin_dependencies'] ),
					'settings'                   => esc_url_raw( $product_settings_url ),
					'has_git_folder'             => $installed_product && file_exists( dirname( $installed_product['plugin_file'] ) . '/.git' ),
				] );

				if ( ! $args['group_by_category'] ) {
					$key = isset( $product_data[ $args['key_by'] ] ) ? $product_data[ $args['key_by'] ] : $product_data['id'];

					$flattened_products[ $key ] = $product_data;
				}
			}
		}

		$products = $args['group_by_category'] ? $products : $flattened_products;

		/**
		 * @filter `gk/foundation/products/data` Modifies products data object.
		 *
		 * @since  1.0.3
		 *
		 * @param array $products Products data.
		 * @param array $args     Additional arguments passed to the get_products_data() method.
		 */
		$products = apply_filters( 'gk/foundation/products/data', $products, $args );

		return $products;
	}

	/**
	 * Checks if plugin is activated in the current context (network or site).
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_product_active_in_current_context( $plugin ) {
		return CoreHelpers::is_network_admin() ? is_plugin_active_for_network( $plugin ) : is_plugin_active( $plugin );
	}

	/**
	 * Optionally updates the Licenses submenu badge count if any of the products have newer versions available.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function update_submenu_badge_count() {
		if ( ! Framework::get_instance()->current_user_can( 'install_products' ) ) {
			return;
		}
		try {
			$products_data = $this->get_products_data();
		} catch ( Exception $e ) {
			LoggerFramework::get_instance()->warning( 'Unable to get products when adding a badge count for products with updates.' );

			return;
		}

		$update_count = 0;

		foreach ( $products_data as $product ) {
			if ( $product['update_available'] && $product['active'] && ! empty( $product['licenses'] ) ) {
				$update_count++;
			}
		}

		if ( ! $update_count ) {
			return;
		}

		add_filter( 'gk/foundation/admin-menu/submenu/' . Framework::ID . '/counter', function ( $count ) use ( $update_count ) {
			return (int) $count + $update_count;
		} );
	}

	/**
	 * Validates product system requirements.
	 *
	 * @since 1.0.0
	 *
	 * @param array $requirements
	 *
	 * @return array|null An array of failed requirements or null if all requirements are met.
	 */
	public function validate_product_system_requirements( $requirements ) {
		$current_system_versions = [
			'php' => PHP_VERSION,
			'wp'  => get_bloginfo( 'version' ),
		];

		$failed_requirements = [];

		if ( empty( $requirements ) ) {
			return null;
		}

		foreach ( $requirements as $key => $requirement ) {
			if ( empty( $requirement['version'] ) ) {
				continue;
			}

			if ( ! isset( $current_system_versions[ $key ] ) ||
			     version_compare( $current_system_versions[ $key ], $requirement['version'], '<' )
			) {
				$failed_requirements[ $key ] = array_merge( $requirement, [ 'version' => $current_system_versions[ $key ] ] );
			}
		}

		return $failed_requirements ?: null;
	}

	/**
	 * Validates product plugin dependencies.
	 *
	 * @since 1.0.0
	 *
	 * @param array $dependencies
	 *
	 * @return array|null An array of failed dependencies or null if all dependencies are met.
	 */
	public function validate_product_plugin_dependencies( $dependencies ) {
		$failed_dependencies = [];

		if ( empty( $dependencies ) ) {
			return null;
		}

		foreach ( $dependencies as $text_domain => $dependency ) {
			$installed_dependency = CoreHelpers::get_installed_plugin_by_text_domain( $text_domain );

			if ( ! $installed_dependency || ! $installed_dependency['active'] ) {
				$failed_dependencies[ $text_domain ] = array_merge( $dependency, [ 'version' => null ] );
			} else if ( ! empty( $dependency['version'] ) && version_compare( $installed_dependency['version'], $dependency['version'], '<' ) ) {
				$failed_dependencies[ $text_domain ] = array_merge( $dependency, [ 'version' => $installed_dependency['version'] ] );
			}
		}

		return $failed_dependencies ?: null;
	}

	/**
	 * Displays action links (e.g., Settings, Support, etc.) for each product in the Plugins page.
	 *
	 * @since 1.0.3
	 *
	 * @param array  $links        Links associated with the product.
	 * @param string $product_path Product path.
	 *
	 * @return array
	 */
	public function display_product_action_links( $links, $product_path ) {
		static $products_data;

		if ( ! $products_data ) {
			try {
				$products_data = $this->get_products_data();
			} catch ( Exception $e ) {
				LoggerFramework::get_instance()->error( 'Unable to get products when linking to the settings page in the Plugins area.' );

				return $links;
			}
		}

		$product = isset( $products_data[ $product_path ] ) ? $products_data[ $product_path ] : null;

		if ( ! $product ) {
			return $links;
		}

		$gk_links = [];

		if ( $product['settings'] ) {
			$gk_links = [
				'settings' => sprintf(
					'<a href="%s">%s</a>',
					$product['settings'],
					esc_html__( 'Settings', 'gk-gravityview' )
				)
			];
		}

		$gk_links['support'] = sprintf(
			'<a href="%s">%s</a>',
			'https://docs.gravitykit.com',
			esc_html__( 'Support', 'gk-gravityview' )
		);

		/**
		 * @filter `gk/foundation/products/{$product_slug}/action-links` Sets product action links in the Plugins page.
		 *
		 * @since  1.0.3
		 *
		 * @param array $links    Combined GravityKit and original action links.
		 * @param array $gk_links GravityKit-added action links.
		 * @param array $link     Original action links.
		 */
		return apply_filters( "gk/foundation/products/{$product['slug']}/action-links", array_merge( $gk_links, $links ), $gk_links, $links );
	}
}
