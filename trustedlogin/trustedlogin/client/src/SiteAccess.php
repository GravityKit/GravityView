<?php
/**
 * Class SiteAccess
 *
 * @package GravityView\TrustedLogin\Client
 *
 * @copyright 2021 Katz Web Services, Inc.
 *
 * @license GPL-2.0-or-later
 * Modified by gravityview on 07-October-2021 using Strauss.
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
	 * @var string[] Valid action types to use when syncing to TrustedLogin.
	 */
	private static $sync_actions = array(
		'create',
		'extend'
	);

	/**
	 *
	 */
	public function __construct( Config $config, Logging $logging ) {
		$this->config  = $config;
		$this->logging = $logging;
	}

	/**
	 * Handles the syncing of newly generated support access to the TrustedLogin servers.
	 *
	 * @param string $secret_id The unique identifier for this TrustedLogin authorization. {@see Endpoint::generate_secret_id}
	 * @param string $site_identifier_hash The unique identifier for the WP_User created {@see Encryption::get_random_hash()}
	 * @param string $action The type of sync this is. Options can be 'create', 'extend'.
	 *
	 * @return true|WP_Error True if successfully created secret on TrustedLogin servers; WP_Error if failed.
	 */
	public function sync_secret( $secret_id, $site_identifier_hash, $action = 'create' ) {

		$logging    = new Logging( $this->config );
		$remote     = new Remote( $this->config, $logging );
		$encryption = new Encryption( $this->config, $remote, $logging );

		if ( ! in_array( $action, self::$sync_actions, true ) ) {
			return new WP_Error( 'param_error', __( 'Unexpected action value', 'gravityview' ) );
		}

		// Ping SaaS and get back tokens.
		$envelope = new Envelope( $this->config, $encryption );

		$access_key = $this->get_access_key();

		if ( is_wp_error( $access_key ) ) {
			return $access_key;
		}

		$sealed_envelope = $envelope->get( $secret_id, $site_identifier_hash, $access_key );

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
			return new WP_Error( 'sync_error', __( 'Could not sync to TrustedLogin server', 'gravityview' ) );
		}

		do_action( 'trustedlogin/' . $this->config->ns() . '/secret/synced', array(
			'url'    => get_site_url(),
			'action' => $action,
		) );

		return true;
	}

	/**
	 * Gets the shareable access key
	 *
	 * - For licensed plugins or themes, a hashed customer's license key is the access key.
	 * - For plugins or themes without license keys, the accessKey is generated for the site.
	 *
	 * @uses SiteAccess::get_license_key()
	 * @uses SiteAccess::generate_access_key()
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
		 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @return  string|WP_Error  Access Key prepended with TL, or something went wrong.
	 */
	private function generate_access_key() {

		$hash = Encryption::hash( get_site_url() . $this->config->get_setting( 'auth/api_key' ), 32 );

		if ( is_wp_error( $hash ) ) {
			return $hash;
		}

		return $hash;
	}

	/**
	 * Revoke a site in TrustedLogin
	 *
	 * @param string $secret_id ID of site secret identifier to be removed from TrustedLogin
	 * @param Remote $remote
	 *
	 * @return true|\WP_Error Was the sync to TrustedLogin successful
	 */
	public function revoke( $secret_id, Remote $remote ) {

		if ( ! $this->config->meets_ssl_requirement() ) {
			$this->logging->log( 'Not notifying TrustedLogin about revoked site due to SSL requirements.', __METHOD__, 'info' );

			return true;
		}

		$body = array(
			'publicKey' => $this->config->get_setting( 'auth/api_key' ),
		);

		$api_response = $remote->send( 'sites/' . $secret_id, $body, 'DELETE' );

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
