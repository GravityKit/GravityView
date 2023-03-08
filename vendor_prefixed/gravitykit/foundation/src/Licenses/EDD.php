<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Licenses;

use GravityKit\GravityView\Foundation\Logger\Framework as LoggerFramework;
use GravityKit\GravityView\Foundation\Helpers\Arr;
use Exception;

class EDD {
	/**
	 * @since 1.0.0
	 *
	 * @var EDD Class instance.
	 */
	private static $_instance;

	/**
	 * Returns class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return EDD
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
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_product_updates' ] );
		add_filter( 'plugins_api', [ $this, 'display_product_information' ], 10, 3 );
		add_filter( 'admin_init', [ $this, 'disable_legacy_edd_updater' ], 999 );
	}

	/**
	 * Disables EDD updater that's included with GravityKit products.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function disable_legacy_edd_updater() {
		global $wp_filter;

		$filters_to_remove = [ 'pre_set_site_transient_update_plugins', 'plugins_api', 'after_plugin_row', 'admin_init' ];

		$legacy_edd_check = function () {
			return isset( $this->api_url ) && preg_match( '/gravity(view|kit)\.com?/', $this->api_url );
		};

		$remove_filter = function ( $filter ) use ( $wp_filter, $legacy_edd_check ) {
			if ( empty( $wp_filter[ $filter ]->callbacks ) ) {
				return;
			}

			foreach ( $wp_filter[ $filter ]->callbacks as &$callback ) {
				foreach ( $callback as $key => &$hook ) {
					if ( ! is_array( $hook['function'] ) || ! is_object( $hook['function'][0] ) ) {
						continue;
					}

					// EDD_SL_Plugin_Updater->api_url is a private property, so we need a way to access it.
					$is_legacy_edd = $legacy_edd_check->bindTo( $hook['function'][0], get_class( $hook['function'][0] ) );

					if ( ! $is_legacy_edd() ) {
						continue;
					}

					unset( $callback[ $key ] );
				}
			}
		};

		foreach ( array_keys( $wp_filter ) as $filter ) {
			foreach ( $filters_to_remove as $filter_to_remove ) {
				// Older EDD_SL_Plugin_Updater class uses 'after_plugin_row_{plugin_file}' filter, so we can't just check for 'after_plugin_row'.
				if ( strpos( $filter, $filter_to_remove ) !== false ) {
					$remove_filter( $filter );
				}
			}
		}
	}

	/**
	 * Checks for product updates and modifies the 'update_plugins' transient.
	 *
	 * @since 1.0.0
	 *
	 * @param object $transient_data
	 * @param bool   $skip_cache (optional) Whether to skip cache when getting products data. Default: false.
	 *
	 * @return object
	 */
	public function check_for_product_updates( $transient_data, $skip_cache = false ) {
		static $checked;

		if ( ! is_object( $transient_data ) ) {
			$transient_data = new \stdClass();
		}

		if ( ! $checked && ! $skip_cache && Arr::get( $_GET, 'force-check', false ) ) {
			$skip_cache = true;
		}

		try {
			$products_data = ProductManager::get_instance()->get_products_data( [ 'skip_cache' => $skip_cache ] );
		} catch ( Exception $e ) {
			LoggerFramework::get_instance()->error( "Can't get products data when checking for updated versions: " . $e->getMessage() );

			return $transient_data;
		}

		foreach ( $products_data as $path => $product ) {
			if ( ! Arr::get( $product, 'installed' ) ) {
				continue;
			}

			$wp_product_data = $this->format_product_data( $product );

			if ( $product['update_available'] ) {
				$transient_data->response[ $path ] = $wp_product_data;
			} else {
				$transient_data->no_update[ $path ] = $wp_product_data;
			}

			$transient_data->checked[ $path ] = Arr::get( $product, 'installed_version' );
		}

		$transient_data->last_checked = time();

		$checked = true;

		return $transient_data;
	}

	/**
	 * Returns a product object formatted according to what WP expects in order to display changelog/store plugin update data.
	 *
	 * @since 1.0.0
	 *
	 * @see   ProductManager::get_products_data()
	 * @see   plugins_api()
	 *
	 * @param array $product Product data.
	 *
	 * @return object
	 */
	public function format_product_data( $product ) {
		$licenses_data = LicenseManager::get_instance()->get_licenses_data();

		$license = Arr::get( $product, 'licenses.0' );

		$download_link = Arr::get( $licenses_data, "{$license}.products.{$product['id']}.download" );

		$formatted_data = [
			'plugin'       => Arr::get( $product, 'path' ),
			'name'         => Arr::get( $product, 'title' ),
			'id'           => Arr::get( $product, 'id' ),
			'slug'         => Arr::get( $product, 'slug' ),
			'version'      => Arr::get( $product, 'server_version' ),
			'new_version'  => Arr::get( $product, 'server_version' ),
			'url'          => Arr::get( $product, 'link' ),
			'homepage'     => Arr::get( $product, 'link' ),
			'icons'        => [
				'1x' => Arr::get( $product, 'icons.1x' ),
				'2x' => Arr::get( $product, 'icons.2x' ),
			],
			'banners'      => [
				'low'  => Arr::get( $product, 'banners.low' ),
				'high' => Arr::get( $product, 'banners.high' ),
			],
			'sections'     => [
				'description' => Arr::get( $product, 'sections.description' ),
				'changelog'   => Arr::get( $product, 'sections.changelog' ),
			],
			'requires'     => Arr::get( $product, 'system_requirements.wp.version' ),
			'tested'       => Arr::get( $product, 'system_requirements.wp.tested' ),
			'requires_php' => Arr::get( $product, 'system_requirements.php.version' ),
		];

		if ( $download_link ) {
			$formatted_data['package']       = $download_link;
			$formatted_data['download_link'] = $download_link;
		}

		return (object) $formatted_data;
	}


	/**
	 * Returns product information for display on the Plugins page.
	 * This short-circuits the WordPress.org API request by returning product information from the EDD API that we store in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param false|object|array $result Product information.
	 * @param string             $action Plugin Installation API action.
	 * @param object             $args   Request arguments.
	 *
	 * @return false|object|array
	 */
	public function display_product_information( $result, $action, $args ) {
		try {
			$products_data = ProductManager::get_instance()->get_products_data( [ 'key_by' => 'slug' ] );
		} catch ( Exception $e ) {
			LoggerFramework::get_instance()->error( "Can't get products data when displaying the changelog: " . $e->getMessage() );

			return $result;
		}

		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		$product = Arr::get( $products_data, $args->slug );

		if ( ! $product ) {
			return $result;
		}

		return $this->format_product_data( $product );
	}
}
