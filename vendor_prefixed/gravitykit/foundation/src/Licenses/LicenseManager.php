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
use GravityKit\GravityView\Foundation\Helpers\Core as CoreHelpers;
use GravityKit\GravityView\Foundation\Logger\Framework as LoggerFramework;
use GravityKit\GravityView\Foundation\Settings\Framework as SettingsFramework;
use GravityKit\GravityView\Foundation\Encryption\Encryption;
use GravityKit\GravityView\Foundation\Helpers\Arr;
use GFForms;
use GFFormsModel;

class LicenseManager {
	const EDD_LICENSES_API_ENDPOINT = 'https://www.gravitykit.com';

	const EDD_LICENSES_API_VERSION = 2;

	const EDD_ACTION_CHECK_LICENSE = 'check_license';

	const EDD_ACTION_ACTIVATE_LICENSE = 'activate_license';

	const EDD_ACTION_DEACTIVATE_LICENSE = 'deactivate_license';

	const HARDCODED_LICENSE_CONSTANTS = [ 'GRAVITYVIEW_LICENSE_KEY', 'GRAVITYKIT_LICENSES' ];

	/**
	 * @since 1.0.0
	 *
	 * @var LicenseManager Class instance.
	 */
	private static $_instance;

	/**
	 * @since 1.0.0
	 *
	 * @var array Cached licenses data object.
	 */
	public $_licenses_data;

	/**
	 * Returns class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return LicenseManager
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

		if ( ! wp_doing_ajax() ) {
			$this->migrate_legacy_licenses();

			$this->process_hardcoded_licenses();

			$this->recheck_all_licenses();
		}

		add_filter( 'gk/foundation/ajax/' . Framework::AJAX_ROUTER . '/routes', [ $this, 'configure_ajax_routes' ] );

		add_action( 'after_plugin_row', [ $this, 'display_license_info_on_plugins_page' ], 10, 2 );

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
	 * @param array $routes AJAX route to class method map.
	 *
	 * @return array
	 */
	public function configure_ajax_routes( array $routes ) {
		return array_merge( $routes, [
			'get_licenses'       => [ $this, 'ajax_get_licenses_data' ],
			'activate_license'   => [ $this, 'ajax_activate_license' ],
			'deactivate_license' => [ $this, 'ajax_deactivate_license' ],
		] );
	}

	/**
	 * AJAX request wrapper for the get_licenses_data() method.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload
	 *
	 * @throws Exception
	 *
	 * @return array
	 */
	public function ajax_get_licenses_data( array $payload ) {
		if ( ! Framework::get_instance()->current_user_can( 'view_licenses' ) ) {
			throw new Exception( esc_html__( 'You do not have a permission to perform this action.', 'gk-gravityview' ) );
		}

		$payload = wp_parse_args( $payload, [
			'skip_cache' => false,
		] );

		$this->migrate_legacy_licenses( $payload['skip_cache'] );

		$this->process_hardcoded_licenses();

		$this->recheck_all_licenses( $payload['skip_cache'] );

		$licenses_data = [];

		foreach ( $this->get_licenses_data() as $license ) {
			$license                          = $this->modify_license_data_for_frontend_output( $license );
			$licenses_data[ $license['key'] ] = $license;
		}

		return $licenses_data;
	}

	/**
	 * Retrieves license data from the database.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_licenses_data() {
		if ( ! empty( $this->_licenses_data ) ) {
			return $this->_licenses_data;
		}

		$licenses_data = get_site_option( Framework::ID );

		if ( ! empty( $licenses_data ) ) {
			$licenses_data = json_decode( Encryption::get_instance()->decrypt( $licenses_data ) ?: '', true );
		}

		$this->_licenses_data = $licenses_data ?: [];

		return $this->_licenses_data;
	}

	/**
	 * Saves license data in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param array $licenses_data
	 *
	 * @return bool
	 */
	public function save_licenses_data( array $licenses_data ) {
		$expiry_dates = array_column( $licenses_data, 'expiry' );

		array_multisort( $licenses_data, SORT_ASC, $expiry_dates );

		$this->_licenses_data = $licenses_data;

		try {
			$licenses_data = Encryption::get_instance()->encrypt( json_encode( $licenses_data ) );
		} catch ( Exception $e ) {
			LoggerFramework::get_instance()->error( 'Failed to encrypt licenses data: ' . $e->getMessage() );

			return false;
		}

		return update_site_option( Framework::ID, $licenses_data );
	}

	/**
	 * Returns an object keyed by product ID and associated licenses.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key_by (optional) Key (product ID or text domain) to use for the returned array.
	 *                       Choices: 'id' or 'text_domain'. Default: 'id'.
	 *
	 * @return array
	 */
	public function get_product_license_map( $key_by = 'id' ) {
		$licenses_data = $this->get_licenses_data();

		$product_license_map = [];

		foreach ( $licenses_data as $license_key => $license_data ) {
			if ( empty( $license_data['products'] ) ) {
				continue;
			}

			foreach ( $license_data['products'] as $product_id => $product_data ) {
				switch ( $key_by ) {
					case 'id':
						$key = $product_id;
						break;
					default:
						$key = $product_data['text_domain'];
						break;
				}


				if ( empty( $product_license_map[ $key ] ) ) {
					$product_license_map[ $key ] = [];
				}

				$product_license_map[ $key ][] = $license_key;
			}
		}

		return $product_license_map;
	}

	/**
	 * Returns licenses for a product.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $id Product ID or text domain.
	 *
	 * @return array
	 */
	public function get_product_licenses( $id ) {
		$key_by = ! ctype_alpha( $id ) ? 'id' : 'text_domain';

		$product_license_map = $this->get_product_license_map( $key_by );

		return ! empty( $product_license_map[ $id ] ) ? $product_license_map[ $id ] : [];
	}

	/**
	 * Returns license status message based on the EDD status code.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status EDD status code.
	 *
	 * @return mixed
	 */
	public function get_license_key_status_message( $status ) {
		$statuses = [
			'site_inactive'       => esc_html__( 'The license key is valid, but it has not been activated for this site.', 'gk-gravityview' ),
			'inactive'            => esc_html__( 'The license key is valid, but it has not been activated for this site.', 'gk-gravityview' ),
			'no_activations_left' => esc_html__( 'This license has reached its activation limit.', 'gk-gravityview' ),
			'deactivated'         => esc_html__( 'This license has been deactivated.', 'gk-gravityview' ),
			'valid'               => esc_html__( 'This license key is valid and active.', 'gk-gravityview' ),
			'invalid'             => esc_html__( 'This license key is invalid.', 'gk-gravityview' ),
			'missing'             => esc_html__( 'This license key is invalid.', 'gk-gravityview' ),
			'revoked'             => esc_html__( 'This license key has been revoked.', 'gk-gravityview' ),
			'expired'             => esc_html__( 'This license key has expired.', 'gk-gravityview' ),
		];

		if ( empty( $statuses[ $status ] ) ) {
			LoggerFramework::get_instance()->warning( 'Unknown license status: ' . $status );

			return esc_html__( 'License status could not be determined.', 'gk-gravityview' );
		}

		return $statuses[ $status ];
	}

	/**
	 * Performs remote call to the EDD API.
	 *
	 * @sice 1.0
	 *
	 * @param string|array $license
	 * @param string       $edd_action
	 *
	 * @throws Exception
	 *
	 * @return array Response body.
	 */
	public function perform_remote_license_call( $license, $edd_action ) {
		$multiple_licenses = is_array( $license );

		$payload = [
			'edd_action'  => $edd_action,
			'url'         => is_multisite() ? network_home_url() : home_url(),
			'api_version' => self::EDD_LICENSES_API_VERSION,
			'license'     => $license
		];

		if ( 'check_license' === $edd_action ) {
			$payload['site_data'] = $this->get_site_data();
		}

		try {
			$response = Helpers::query_api(
				self::EDD_LICENSES_API_ENDPOINT,
				$payload
			);
		} catch ( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		// Response can be a multidimensional array when checking multiple licenses.
		$response = $multiple_licenses ? $response : [ $response ];

		// When checking multiple licenses (i.e., an array of keys) but there is only 1 key in the array, the response is an associative array that needs to be converted to a multidimensional array keyed by the license key.
		if ( $multiple_licenses && 1 === count( $license ) ) {
			$response = [ $license[0] => $response ];
		}

		$normalized_response_data = [];

		$license_keys = $multiple_licenses ? $license : [ $license ];

		foreach ( (array) $response as $key => $data ) {
			if ( ! isset( $data['success'] ) || ! isset( $data['license'] ) || ! isset( $data['checksum'] ) ) {
				throw new Exception( esc_html__( 'License data received from the API is incomplete.', 'gk-gravityview' ) );
			}

			$license_key = $multiple_licenses ? $key : $license;

			if ( ! in_array( $license_key, $license_keys, true ) ) {
				LoggerFramework::get_instance()->warning( "EDD API returned unknown license key in response: {$license_key}" );

				continue;
			}

			if ( ! $data['success'] && empty( $data['expires'] ) ) {
				$expiry = null;
			} else {
				$expiry = ! empty( $data['expires'] ) ? strtotime( $data['expires'], current_time( 'timestamp' ) ) : null;
				$expiry = $expiry ?: $data['expires'];
			}

			$normalized_license_data = [
				'name'         => ! empty( $data['customer_name'] ) ? $data['customer_name'] : null,
				'email'        => ! empty( $data['customer_email'] ) ? $data['customer_email'] : null,
				'license_name' => ! empty( $data['license_name'] ) ? $data['license_name'] : null,
				'expiry'       => $expiry,
				'key'          => $license_key,
				'products'     => [],
				'_raw'         => $data,
			];

			if ( ! empty( $data['products'] ) ) {
				foreach ( $data['products'] as $product ) {
					if ( empty( $product['files'][0]['file'] ) || empty( $product['id'] ) || empty( $product['textdomain'] ) ) {
						continue;
					}

					$normalized_license_data['products'][ $product['id'] ] = [
						'id'          => $product['id'],
						'text_domain' => $product['textdomain'],
						'download'    => $product['files'][0]['file']
					];
				}
			}

			if ( $multiple_licenses ) {
				$normalized_response_data[ $license_key ] = $normalized_license_data;
			} else {
				$normalized_response_data = $normalized_license_data;
			}
		}

		return $normalized_response_data;
	}

	/**
	 * Checks license key for validity.
	 *
	 * @since 1.0.0
	 *
	 * @param string $license_key
	 *
	 * @throws Exception
	 *
	 * @return array License data.
	 */
	public function check_license( $license_key ) {
		try {
			return $this->perform_remote_license_call( $license_key, self::EDD_ACTION_CHECK_LICENSE );
		} catch ( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 * Checks multiples license keys for validity.
	 *
	 * @since 1.0.0
	 *
	 * @param array $license_keys
	 *
	 * @throws Exception
	 *
	 * @return array Licenses data.
	 */
	public function check_licenses( array $license_keys ) {
		try {
			return $this->perform_remote_license_call( $license_keys, self::EDD_ACTION_CHECK_LICENSE );
		} catch ( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 * AJAX request wrapper for the activate_license() method.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload
	 *
	 * @throws Exception
	 *
	 * @return array License information.
	 */
	public function ajax_activate_license( array $payload ) {
		if ( ! Framework::get_instance()->current_user_can( 'manage_licenses' ) ) {
			throw new Exception( esc_html__( 'You do not have a permission to perform this action.', 'gk-gravityview' ) );
		}

		if ( empty( $payload['key'] ) ) {
			throw new Exception( esc_html__( 'Missing license key.', 'gk-gravityview' ) );
		}

		return $this->modify_license_data_for_frontend_output( $this->activate_license( $payload['key'] ) );
	}

	/**
	 * Activates license.
	 *
	 * @since 1.0.0
	 *
	 * @param string $license_key license_key
	 *
	 * @throws Exception
	 *
	 * @return array License information.
	 */
	public function activate_license( $license_key ) {
		if ( ! Framework::get_instance()->current_user_can( 'manage_licenses' ) ) {
			throw new Exception( esc_html__( 'You do not have a permission to perform this action.', 'gk-gravityview' ) );
		}

		$licenses_data = $this->get_licenses_data();

		if ( isset( $licenses_data[ $license_key ] ) ) {
			throw new Exception( esc_html__( 'This license is already activated.', 'gk-gravityview' ) );
		}

		try {
			$response = $this->perform_remote_license_call( $license_key, self::EDD_ACTION_ACTIVATE_LICENSE );

			if ( ! $response['_raw']['success'] ) {
				throw new Exception( $this->get_license_key_status_message( $response['_raw']['error'] ) );
			}

			if ( ! $response['_raw']['success'] ) {
				throw new Exception( esc_html__( 'Could not get information on products associated with this license.', 'gk-gravityview' ) );
			}
		} catch ( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		unset( $response['_raw'] );

		$licenses_data[ $license_key ] = $response;

		$this->save_licenses_data( $licenses_data );

		if ( CoreHelpers::is_network_admin() ) {
			delete_site_transient( 'update_plugins ' );
		} else {
			delete_transient( 'update_plugins' );
		}

		return $response;
	}

	/**
	 * AJAX request wrapper for the deactivate_license() method.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payload
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function ajax_deactivate_license( array $payload ) {
		$payload = wp_parse_args( $payload, [
			'key'           => false,
			'force_removal' => false,
		] );

		if ( ! $payload['key'] ) {
			throw new Exception( esc_html__( 'Missing license key.', 'gk-gravityview' ) );
		}

		$licenses_data = $this->get_licenses_data();

		$license_key = Encryption::get_instance()->decrypt( $payload['key'] );

		if ( empty( $licenses_data[ $license_key ] ) ) {
			throw new Exception( esc_html__( 'The license key is invalid.', 'gk-gravityview' ) );
		}

		$this->deactivate_license( $license_key, (bool) $payload['force_removal'] );
	}

	/**
	 * Deactivates license.
	 *
	 * @since 1.0.0
	 * @since 1.0.7 Added $force_removal parameter.
	 *
	 * @param string $license_key
	 * @param bool   $force_removal (optional) Forces removal of license from the local licenses object even if deactivation request fails. Default: false.
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function deactivate_license( $license_key, $force_removal = false ) {
		$licenses_data = $this->get_licenses_data();

		try {
			$response = $this->perform_remote_license_call( $license_key, self::EDD_ACTION_DEACTIVATE_LICENSE );

			if ( ! $force_removal && ! Arr::get( $response, '_raw.success' ) ) {
				// Unsuccessful deactivation can happen when the license has expired, in which case we should treat it as a "success" and remove from our list.
				// If the license hasn't expired, then there is a problem deactivating it, and we should throw an exception.
				if ( ! Arr::get( $response, 'expiry' ) || ! $this->is_expired_license( Arr::get( $response, 'expiry' ) ) ) {
					throw new Exception( esc_html__( 'Failed to deactivate license.', 'gk-gravityview' ) );
				}
			}
		} catch ( Exception $e ) {
			if ( ! $force_removal ) {
				throw new Exception( $e->getMessage() );
			}
		}

		unset( $licenses_data[ $license_key ] );

		if ( CoreHelpers::is_network_admin() ) {
			delete_site_transient( 'update_plugins ' );
		} else {
			delete_transient( 'update_plugins' );
		}

		$this->save_licenses_data( $licenses_data );
	}

	/**
	 * Adds additional data to the license object for use in the frontend.
	 * - Encrypts license key
	 * - Formats expiration date or message if license is expired
	 *
	 * @since 1.0.0
	 *
	 * @param $license
	 *
	 * @return array
	 */
	public function modify_license_data_for_frontend_output( $license ) {
		$expiry  = ! empty( $license['expiry'] ) ? $license['expiry'] : 'invalid';
		$expired = false;

		if ( ! ctype_alpha( $expiry ) ) {
			$expired = $this->is_expired_license( $expiry );

			$expiry = $expired
				? human_time_diff( $expiry, current_time( 'timestamp' ) ) . ' ' . esc_html_x( 'ago', 'Indicates "time ago"', 'gk-gravityview' )
				: date_i18n( get_option( 'date_format' ), $expiry );

		}

		try {
			$encrypted_key = Encryption::get_instance()->encrypt( $license['key'], false, Core::get_request_unique_string() );
		} catch ( Exception $e ) {
			LoggerFramework::get_instance()->error( 'Failed to encrypt license key: ' . $e->getMessage() );

			$encrypted_key = 'key_encryption_failed';
		}

		return array_merge( $license, [
			'expiry'     => $expiry,
			'expired'    => $expired,
			'key'        => $encrypted_key,
			'masked_key' => $this->mask_license_key( $license['key'] )
		] );
	}

	/**
	 * Masks part of the license key
	 *
	 * @since 1.0.0
	 *
	 * @param string $license_key
	 *
	 * @return string
	 */
	public function mask_license_key( $license_key ) {
		$length        = strlen( $license_key );
		$visible_count = (int) round( $length / 8 );
		$hidden_count  = $length - ( $visible_count * 4 );

		return sprintf( '%s%s%s',
			substr( $license_key, 0, $visible_count ),
			str_repeat( '✽', $hidden_count ),
			substr( $license_key, ( $visible_count * -1 ), $visible_count )
		);
	}

	/**
	 * Saves new or removes existing hardcoded licenses from the license data.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function process_hardcoded_licenses() {
		$hardcoded_license_keys = [];

		foreach ( self::HARDCODED_LICENSE_CONSTANTS as $constant ) {
			if ( ! defined( $constant ) ) {
				continue;
			}

			if ( is_array( constant( $constant ) ) ) {
				$hardcoded_license_keys = array_merge( $hardcoded_license_keys, constant( $constant ) );
			} else {
				$hardcoded_license_keys[] = constant( $constant );
			}
		}

		$licenses_data = $this->get_licenses_data();

		// Remove any licenses that are no longer hardcoded.
		$removed_hardcoded_licenses = 0;

		foreach ( $licenses_data as $key => $license ) {
			if ( ! empty( $license['hardcoded'] ) && ! in_array( $key, $hardcoded_license_keys, true ) ) {
				$removed_hardcoded_licenses++;

				unset( $licenses_data[ $key ] );
			}
		}

		if ( $removed_hardcoded_licenses ) {
			$this->save_licenses_data( $licenses_data );
		}

		if ( empty( $hardcoded_license_keys ) ) {
			return;
		}

		// Add any new hardcoded licenses.
		$license_keys_to_check = array_values( array_diff( $hardcoded_license_keys, array_keys( $licenses_data ) ) );

		if ( empty( $license_keys_to_check ) ) {
			return;
		}

		try {
			$checked_licenses = $this->check_licenses( $license_keys_to_check );
		} catch ( Exception $e ) {
			LoggerFramework::get_instance()->error( "Failed to check hardcoded licenses. {$e->getMessage()}." );

			return;
		}

		foreach ( $checked_licenses as $key => $license ) {
			if ( ! $license['_raw']['success'] ) {
				LoggerFramework::get_instance()->warning( "Hardcoded license {$key} is invalid." );

				continue;
			}

			unset( $license['_raw'] );

			$license['hardcoded'] = true;

			$licenses_data[ $key ] = $license;
		}

		$this->save_licenses_data( $licenses_data );
	}

	/**
	 * Migrates licenses for products that do not have Foundation integrated.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $force_migration Whether to force migration even if it was done before.
	 *
	 * @return void
	 */
	public function migrate_legacy_licenses( $force_migration = false ) {
		$logger = LoggerFramework::get_instance();

		$migration_status_id = Framework::ID . '/legacy-licenses-migrated';

		$save_migration_status_in_db = function () use ( $migration_status_id ) {
			update_site_option( $migration_status_id, current_time( 'timestamp' ) );
		};

		if ( get_site_option( $migration_status_id ) && ! $force_migration ) {
			return;
		}

		$licenses_data = $this->get_licenses_data();

		$license_keys_to_migrate = [];

		$db_options = [
			'gravityformsaddon_gravityview-importer_settings',
			'gravityformsaddon_gravityview_app_settings',
			'gravityformsaddon_gravityview-inline-edit_settings',
			'gravityformsaddon_gravitycharts_settings',
			'gravityformsaddon_gk-gravityactions_settings',
			'gravityformsaddon_gravityview-calendar_settings',
			'gravityformsaddon_gravityexport_settings',
			'gravityformsaddon_gravityview-entry-revisions_settings',
		];

		foreach ( $db_options as $option ) {
			$license = Arr::get( get_option( $option, [] ), 'license_key' );

			$option = str_replace( [ 'gravityformsaddon_', '_settings' ], '', $option );

			if ( $license ) {
				$license_keys_to_migrate[ $license ] = $option;
			} else {
				$logger->warning( "Legacy license not found for {$option}." );
			}
		}

		if ( empty( $license_keys_to_migrate ) ) {
			$save_migration_status_in_db();

			$logger->info( 'Did not find any legacy licenses to migrate.' );

			return;
		}

		try {
			$checked_licenses = $this->check_licenses( array_keys( $license_keys_to_migrate ) );
		} catch ( Exception $e ) {
			$logger->error( "Failed to check legacy licenses. {$e->getMessage()}." );

			return;
		}

		foreach ( $checked_licenses as $key => $license ) {
			if ( ! $license['_raw']['success'] ) {
				$logger->warning( "Legacy license {$key} is invalid." );

				continue;
			}

			try {
				$license = $this->activate_license( $key );
			} catch ( Exception $e ) {
				$logger->error( "Failed to activate legacy license {$key}. {$e->getMessage()}." );

				continue;
			}

			$logger->info( "Migrated legacy license for {$license_keys_to_migrate[$key]}." );

			$licenses_data[ $key ] = $license;
		}

		$save_migration_status_in_db();

		$this->save_licenses_data( $licenses_data );
	}

	/**
	 * Rechecks all licenses and updates the database.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $skip_cache Whether to skip returning products from cache.
	 *
	 * @return void
	 */
	public function recheck_all_licenses( $skip_cache = false ) {
		$cache_id = Framework::ID . '/licenses';

		$last_validation = get_site_transient( $cache_id );

		if ( $last_validation && ! $skip_cache ) {
			return;
		}

		$licenses_data = $this->get_licenses_data();

		$revalidated_licenses = [];

		if ( empty( $licenses_data ) ) {
			return;
		}

		try {
			$license_check_result = $this->check_licenses( array_keys( $licenses_data ) );

			foreach ( $license_check_result as $key => $license ) {
				if ( ! $license['_raw']['success'] ) {
					LoggerFramework::get_instance()->warning( "License {$key} is invalid." );

					continue;
				}

				unset( $license['_raw'] );

				if ( ! empty( $licenses_data[ $key ]['hardcoded'] ) ) {
					$license['hardcoded'] = true;
				}

				$revalidated_licenses[ $key ] = $license;
			}
		} catch ( Exception $e ) {
			LoggerFramework::get_instance()->error( "Failed to revalidate all licenses. {$e->getMessage()}." );
		}

		set_site_transient( $cache_id, current_time( 'timestamp' ), DAY_IN_SECONDS );

		if ( ! empty( $revalidated_licenses ) ) {
			$this->save_licenses_data( $revalidated_licenses );
		}
	}

	/**
	 * Optionally adds notices to installed plugins when license is invalid or expired.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_name
	 * @param array  $plugin_data
	 *
	 * @return void
	 */
	public function display_license_info_on_plugins_page( $plugin_name, $plugin_data ) {
		static $products_data;

		if ( is_multisite() && ! CoreHelpers::is_network_admin() ) {
			return;
		}

		$is_active = CoreHelpers::is_network_admin() ? is_plugin_active_for_network( $plugin_name ) : is_plugin_active( $plugin_name );

		if ( ! $is_active ) {
			return;
		}

		$licenses_data = $this->get_licenses_data();

		if ( ! $products_data ) {
			try {
				$products_data = ProductManager::get_instance()->get_products_data( [ 'key_by' => 'text_domain' ] );
			} catch ( Exception $e ) {
				LoggerFramework::get_instance()->error( "Failed to get products on the plugins page. {$e->getMessage()}." );

				return;
			}
		}

		if ( ! isset( $products_data[ $plugin_data['TextDomain'] ] ) ) {
			return;
		}

		$this_plugin = $products_data[ $plugin_data['TextDomain'] ];

		$valid_licenses = [];

		foreach ( $this_plugin['licenses'] as $license_key ) {
			if ( ! isset( $licenses_data[ $license_key ] ) ) {
				continue;
			}

			$valid_licenses[] = $license_key;
		}

		if ( ! empty( $valid_licenses ) ) {
			return;
		}

		add_filter( "after_plugin_row_{$plugin_name}", function ( $plugin_name, $plugin_data ) use ( $this_plugin ) {
			$url = Framework::get_instance()->get_link_to_product_search( $this_plugin['id'] );

			$message = strtr(
				esc_html_x( 'This is an unlicensed product. Please [link]visit the licensing page[/link] to enter a valid license or to purchase a new one.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
				[
					'[link]'  => '<a href="' . $url . '">',
					'[/link]' => '</a>'
				]
			);

			$screen      = get_current_screen();
			$columns     = get_column_headers( $screen );
			$colspan     = ! is_countable( $columns ) ? 3 : count( $columns );
			$plugin_slug = isset( $plugin_data['slug'] ) ? $plugin_data['slug'] : sanitize_title( $plugin_data['Name'] );
			$plugin_name = $plugin_data['Name'];

			echo <<<HTML
<tr class="plugin-update-tr active gk-custom-plugin-update-message" data-slug="{$plugin_slug}11" data-plugin="{$plugin_name}">
	<td colspan="{$colspan}" class="plugin-update colspanchange">
		<div class="update-message notice inline notice-error notice-alt">
			<p>{$message}</p>
		</div>
	</td>
</tr>
<style>tr[data-slug="{$plugin_slug}"]:not(.gk-custom-plugin-update-message) td, tr[data-slug="{$plugin_slug}"]:not(.gk-custom-plugin-update-message) th { box-shadow: none !important; }</style>
HTML;
		}, 11, 2 );
	}

	/**
	 * Retrieves site data (plugin versions, integrations, etc.) to be sent along with the license check.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_site_data() {
		global $wpdb;

		$data = [];

		$theme_data = wp_get_theme();
		$theme      = $theme_data->Name . ' ' . $theme_data->Version;

		$data['php_version']   = PHP_VERSION;
		$data['wp_version']    = get_bloginfo( 'version' );
		$data['mysql_version'] = $wpdb->db_version();

		if ( defined( 'GV_PLUGIN_VERSION' ) ) {
			$data['gv_version'] = GV_PLUGIN_VERSION;
		}

		if ( class_exists( 'GFForms' ) ) {
			$data['gf_version'] = GFForms::$version;
		}

		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$data['server'] = $_SERVER['SERVER_SOFTWARE'];
		}

		$data['multisite'] = is_multisite();
		$data['theme']     = $theme;
		$data['url']       = is_multisite() ? network_home_url() : home_url();
		$data['beta']      = SettingsFramework::get_instance()->get_plugin_setting( Core::ID, 'beta' );

		// GravityView view data.
		$gravityview_posts = wp_count_posts( 'gravityview', 'readable' );

		$data['view_count']  = null;
		$data['view_first']  = null;
		$data['view_latest'] = null;

		if ( ! empty( $gravityview_posts->publish ) ) {
			$data['view_count'] = $gravityview_posts->publish;

			$first  = get_posts( 'numberposts=1&post_type=gravityview&post_status=publish&order=ASC' );
			$latest = get_posts( 'numberposts=1&post_type=gravityview&post_status=publish&order=DESC' );

			if ( $first = array_shift( $first ) ) {
				$data['view_first'] = $first->post_date;
			}
			if ( $latest = array_pop( $latest ) ) {
				$data['view_latest'] = $latest->post_date;
			}
		}

		// Gravity Forms form data.
		if ( class_exists( 'GFFormsModel' ) ) {
			$form_data = GFFormsModel::get_form_count();

			$data['forms_total']    = $form_data['total'];
			$data['forms_active']   = $form_data['active'];
			$data['forms_inactive'] = $form_data['inactive'];
			$data['forms_trash']    = $form_data['trash'];
		}

		$plugins = CoreHelpers::get_installed_plugins();
		foreach ( $plugins as &$plugin ) {
			$plugin = Arr::only( $plugin, [ 'name', 'version', 'active', 'network_activated' ] );
			$plugin = array_filter( $plugin ); // Don't include active/network activated if false
		}

		$data['plugins'] = $plugins;
		$data['locale']  = get_locale();

		return $data;
	}

	/**
	 * Optionally updates the Licenses submenu badge count if any of the products are unlicensed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function update_submenu_badge_count() {
		if ( ! Framework::get_instance()->current_user_can( 'manage_licenses' ) ) {
			return;
		}

		try {
			$products_data = ProductManager::get_instance()->get_products_data();
		} catch ( Exception $e ) {
			LoggerFramework::get_instance()->warning( 'Unable to get products when adding a badge count for unlicensed products.' );

			return;
		}

		$update_count = 0;

		foreach ( $products_data as $product ) {
			if ( $product['active'] && empty( $product['licenses'] ) ) {
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
	 * Determines if the license has expired.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $expiry Unix time or 'lifetime'.
	 *
	 * @return bool
	 */
	public function is_expired_license( $expiry ) {
		if ( 'lifetime' === $expiry ) {
			return false;
		}

		return $expiry < current_time( 'timestamp' );
	}
}
