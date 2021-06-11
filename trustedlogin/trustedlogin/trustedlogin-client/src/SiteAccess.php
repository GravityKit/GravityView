<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 11-June-2021 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityView\TrustedLogin;

use \WP_Error;

class SiteAccess {

	/**
	 * @var Config $config
	 */
	private $config;

	/**
	 * @var Logging $logging
	 */
	private $logging;

	/**
	 * @var string The unique identifier of the site in TrustedLogin
	 */
	private $identifier;

	/**
	 *
	 */
	public function __construct( Config $config, Logging $logging ) {
		$this->config  = $config;
		$this->logging = $logging;
	}

	/**
	 * @param string $identifier The unique identifier of the site in TrustedLogin
	 */
	public function set_identifier( $identifier ) {
		$this->identifier = (string) $identifier;
	}

	/**
	 * Revoke access to a site
	 *
	 * @param string $identifier Unique ID or "all"
	 *
	 * @return bool|WP_Error True: Synced to SaaS. False: empty identifier. WP_Error: failed to revoke site in SaaS.
	 */
	public function revoke_access( $identifier = '' ) {

		if ( empty( $identifier ) ) {

			$this->logging->log( 'Missing the revoke access identifier.', __METHOD__, 'error' );

			return false;
		}

		$Endpoint = new Endpoint( $this->config, $this->logging );

		$endpoint_hash = $Endpoint->get_hash( $identifier );

		$this->set_identifier( $endpoint_hash );

		$Remote = new Remote( $this->config, $this->logging );

		// Revoke site in SaaS
		$site_revoked = $this->revoke( $Remote );

		if ( is_wp_error( $site_revoked ) ) {

			// Couldn't sync to SaaS, this should/could be extended to add a cron-task to delayed update of SaaS DB
			// TODO: extend to add a cron-task to delayed update of SaaS DB
			$this->logging->log( 'There was an issue syncing to SaaS. Failing silently.', __METHOD__, 'error' );

			return $site_revoked;
		}

		do_action( 'trustedlogin/' . $this->config->ns() . '/access/revoked', array(
			'url'    => get_site_url(),
			'action' => 'revoked',
		) );

		return $site_revoked;
	}

	/**
	 * Handles the syncing of newly generated support access to the TrustedLogin servers.
	 *
	 * @param string $secret_id The unique identifier for this TrustedLogin authorization. {@see Endpoint::generate_secret_id}
	 * @param string $identifier The unique identifier for the WP_User created {@see SiteAccess::create_hash}
	 * @param string $action The type of sync this is. Options can be 'create', 'extend'.
	 *
	 * @return true|WP_Error True if successfully created secret on TrustedLogin servers; WP_Error if failed.
	 */
	public function sync_secret( $secret_id, $identifier, $action = 'create' ) {


		$logging    = new Logging( $this->config );
		$remote     = new Remote( $this->config, $logging );
		$encryption = new Encryption( $this->config, $remote, $logging );

		if ( ! in_array( $action, array( 'create', 'extend' ) ) ) {
			return new WP_Error( 'param_error', __( 'Unexpected action value', 'trustedlogin' ) );
		}

		// Ping SaaS and get back tokens.
		$envelope = new Envelope( $this->config, $encryption );

		$access_key = $this->get_access_key();

		if ( is_wp_error( $access_key ) ) {
			return $access_key;
		}

		$sealed_envelope = $envelope->get( $secret_id, $identifier, $access_key );

		if ( is_wp_error( $sealed_envelope ) ) {
			return $sealed_envelope;
		}

		$api_response = $remote->send( 'sites', $sealed_envelope, 'POST' );

		if ( is_wp_error( $api_response ) ) {
			return $api_response;
		}

		$response_json = $remote->handle_response( $api_response, array( 'success' ) );

		if ( is_wp_error( $response_json ) ) {
			return $response_json;
		}

		if ( empty( $response_json['success'] ) ) {
			return new WP_Error( 'sync_error', __( 'Could not sync to TrustedLogin server', 'trustedlogin' ) );
		}

		do_action( 'trustedlogin/' . $this->config->ns() . '/secret/synced', array(
			'url'    => get_site_url(),
			'action' => $action,
		) );

		return true;
	}

	/**
	 * Generate a hash that is used to add two levels of security to the login URL:
	 * The hash is stored as usermeta, and is used when generating $secret_id.
	 * Both parts are required to access the site.
	 *
	 * TODO: Move to Encryption?
	 *
	 * @return string|WP_Error
	 */
	public function create_hash() {

		$hash = false;

		if ( function_exists( 'random_bytes' ) ) {
			try {
				$bytes = random_bytes( 64 );
				$hash  = bin2hex( $bytes );
			} catch ( \TypeError $e ) {
				$this->logging->log( $e->getMessage(), __METHOD__, 'error' );
			} catch ( \Error $e ) {
				$this->logging->log( $e->getMessage(), __METHOD__, 'error' );
			} catch ( \Exception $e ) {
				$this->logging->log( $e->getMessage(), __METHOD__, 'error' );
			}
		} else {
			$this->logging->log( 'This site does not have the random_bytes() function.', __METHOD__, 'debug' );
		}

		if ( $hash ) {
			return $hash;
		}

		if ( ! function_exists( 'openssl_random_pseudo_bytes' ) ) {
			return new WP_Error( 'generate_hash_failed', 'Could not generate a secure hash with random_bytes or openssl.' );
		}

		$crypto_strong = false;
		$hash          = openssl_random_pseudo_bytes( 64, $crypto_strong );

		if ( ! $crypto_strong ) {
			return new WP_Error( 'openssl_not_strong_crypto', 'Site could not generate a secure hash with OpenSSL.' );
		}

		return $hash;
	}

	/**
	 * Gets the shareable accessKey
	 *
	 * - For licensed plugins or themes, a hashed customer's license key is the access key.
	 * - For plugins or themes without license keys, the accessKey is generated for the site.
	 *
	 * @uses SiteAccess::get_license_key()
	 * @uses SiteAccess::generate_access_key()
	 *
	 * @since 0.9.2
	 *
	 * @return string|null $access_key, if exists. Either a hashed license key or a generated hash. If error occurs, returns null.
	 */
	public function get_access_key() {

		// If there's a license, return a hash of the license.
		$license_key = $this->get_license_key( true );

		if ( $license_key && ! is_wp_error( $license_key ) ) {
			return $license_key;
		}

		$generated_access_key = $this->generate_access_key();

		if ( ! $generated_access_key || is_wp_error( $generated_access_key ) ) {
			return null;
		}

		return $generated_access_key;
	}

	/**
	 * Get the license key for the current user.
	 *
	 * @since 0.7.0
	 *
	 * @param bool $hashed Should the value be hashed using SHA256?
	 *
	 * @return string|WP_Error
	 */
	public function get_license_key( $hashed = false ) {

		// If no license key is provided
		$license_key_config = $this->config->get_setting( 'auth/license_key', null );

		/**
		 * Filter: Allow for over-riding the 'accessKey' sent to SaaS platform
		 *
		 * @since 0.4.0
		 *
		 * @param string|null $license_key
		 */
		$license_key = apply_filters( 'trustedlogin/' . $this->config->ns() . '/licence_key', $license_key_config );

		if ( ! is_string( $license_key ) ) {

			$this->logging->log( '', '', 'error', array(
				'$license from Config'    => $license_key_config,
				'$license after filter: ' => $license_key,
			) );

			return new WP_Error( 'invalid_license_key', 'License key was not a string.' );
		}

		if ( $hashed ) {
			return hash( 'sha256', $license_key );
		}

		return $license_key;
	}

	/**
	 * Generates an accessKey that can be copy-pasted to support to give them access via TrustedLogin
	 *
	 * Access Keys can only be used by authenticated support agents to request logged access to a site via their TrustedLogin plugin.
	 *
	 * @since 0.9.2
	 *
	 * @return  string|WP_Error  Access Key prepended with TL, or something went wrong.
	 */
	private function generate_access_key() {

		$hash = Encryption::hash( get_site_url() . $this->config->get_setting( 'auth/public_key' ), 32 );

		if ( is_wp_error( $hash ) ) {
			return $hash;
		}

		return $hash;
	}

	public function revoke_by_identifier( $identifier ) {

	}

	/**
	 * Revoke a site in TrustedLogin
	 *
	 * @param Remote $remote
	 *
	 * @return true|\WP_Error Was the sync to TrustedLogin successful
	 */
	public function revoke( Remote $remote ) {

		if ( ! $this->config->meets_ssl_requirement() ) {
			$this->logging->log( 'Not notifying TrustedLogin about revoked site due to SSL requirements.', __METHOD__, 'info' );

			return true;
		}

		$body = array(
			'publicKey' => $this->config->get_setting( 'auth/public_key' ),
		);

		$api_response = $remote->send( 'sites/' . $this->identifier, $body, 'DELETE' );

		if ( is_wp_error( $api_response ) ) {
			return $api_response;
		}

		$response = $remote->handle_response( $api_response );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

}
