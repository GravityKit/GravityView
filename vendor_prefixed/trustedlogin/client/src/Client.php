<?php
/**
 * ###                    ###
 * ###   HEY DEVELOPER!   ###
 * ###                    ###
 * ###  (read me first)   ###
 *
 * Thanks for integrating TrustedLogin.
 *
 * 0. If you haven't already, sign up for a TrustedLogin account {@see https://www.trustedlogin.com}
 * 1. Namespace the installation ({@see https://www.trustedlogin.com/configuration/} to learn how)
 * 2. Instantiate this class with a configuration array (really, {@see https://www.trustedlogin.com/configuration/} for more info)
 *
 * Class Client
 *
 * @package GravityKit\GravityView\Foundation\ThirdParty\TrustedLogin\Client
 *
 * @copyright 2021 Katz Web Services, Inc.
 *
 * @license GPL-2.0-or-later
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */
namespace GravityKit\GravityView\Foundation\ThirdParty\TrustedLogin;

// Exit if accessed directly
if ( ! defined('ABSPATH') ) {
	exit;
}

use \Exception;
use \WP_Error;

/**
 * The TrustedLogin all-in-one drop-in class.
 */
final class Client {

	/**
	 * @var string The current drop-in file version
	 * @since 1.0.0
	 */
	const VERSION = '1.3.7';

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @var bool
	 */
	static $valid_config;

	/**
	 * @var null|Logging $logging
	 */
	private $logging;

	/**
	 * @var SupportUser $support_user
	 */
	private $support_user;

	/**
	 * @var Remote $remote
	 */
	private $remote;

	/**
	 * @var Cron $cron
	 */
	private $cron;

	/**
	 * @var Endpoint $endpoint
	 */
	private $endpoint;

	/**
	 * @var Admin $admin
	 */
	private $admin;

	/**
	 * @var Ajax
	 */
	private $ajax;

	/**
	 * @var SiteAccess $site_access
	 */
	private $site_access;

	/**
	 * TrustedLogin constructor.
	 *
	 * @see https://docs.trustedlogin.com/ for more information
	 *
	 * @param Config $config
	 * @param bool $init Whether to initialize everything on instantiation
	 *
	 * @throws Exception If initializing is prevented via constants or the configuration isn't valid, throws exception.
	 *
	 * @returns void If no errors, returns void. Otherwise, throws exceptions.
	 */
	public function __construct( Config $config, $init = true ) {

		$should_initialize = $this->should_init( $config );

		if ( ! $should_initialize ) {
			throw new \Exception( 'TrustedLogin was prevented from loading by constants defined on the site.', 403 );
		}

		try {
			self::$valid_config = $config->validate();
		} catch ( \Exception $exception ) {
			self::$valid_config = false;
			throw $exception;
		}

		$this->config = $config;

		$this->logging = new Logging( $config );

		$this->endpoint = new Endpoint( $this->config, $this->logging );

		$this->cron = new Cron( $this->config, $this->logging );

		$this->support_user = new SupportUser( $this->config, $this->logging );

		$this->admin = new Admin( $this->config, $this->logging );

		$this->ajax = new Ajax( $this->config, $this->logging );

		$this->remote = new Remote( $this->config, $this->logging );

		$this->site_access = new SiteAccess( $this->config, $this->logging );

		if ( $init ) {
			$this->init();
		}
	}

	/**
	 * Should the Client fully initialize?
	 *
	 * @param Config $config
	 *
	 * @return bool
	 */
	private function should_init( Config $config ) {

		// Disables all TL clients.
		if ( defined( 'TRUSTEDLOGIN_DISABLE' ) && TRUSTEDLOGIN_DISABLE ) {
			return false;
		}

		$ns = $config->ns();

		// Namespace isn't set; allow Config
		if( empty( $ns ) ) {
			return true;
		}

		// Disables namespaced client if `TRUSTEDLOGIN_DISABLE_{NS}` is defined and truthy.
		if ( defined( 'TRUSTEDLOGIN_DISABLE_' . strtoupper( $ns ) ) && constant( 'TRUSTEDLOGIN_DISABLE_' . strtoupper( $ns ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Initialize all the things!
	 *
	 */
	public function init() {
		$this->admin->init();
		$this->endpoint->init();
		$this->remote->init();
		$this->cron->init();
		$this->ajax->init();
	}

	/**
	 * Returns the current access key (hashed license key or generated access key
	 *
	 * @see SiteAccess::get_access_key()
	 *
	 * @return string|null|WP_Error
	 */
	public function get_access_key() {

		if ( ! self::$valid_config ) {
			return new \WP_Error( 'invalid_configuration', 'TrustedLogin has not been properly configured or instantiated.', array( 'error_code' => 424 ) );
		}

		return $this->site_access->get_access_key();
	}

	/**
	 * This creates a TrustedLogin user ✨
	 *
	 * @return array|WP_Error
	 */
	public function grant_access() {

		if ( ! self::$valid_config ) {
			return new \WP_Error( 'invalid_configuration', 'TrustedLogin has not been properly configured or instantiated.', array( 'error_code' => 424 ) );
		}

		if ( ! current_user_can( 'create_users' ) ) {
			return new \WP_Error( 'no_cap_create_users', 'Permissions issue: You do not have the ability to create users.', array( 'error_code' => 403 ) );
		}

		// If the user exists already, extend access
		if ( $user_id = $this->support_user->exists() ) {
			return $this->extend_access( $user_id );
		}

		timer_start();

		try {
			$support_user_id = $this->support_user->create();
		} catch ( Exception $exception ) {

			$this->logging->log( 'An exception occurred trying to create a support user.', __METHOD__, 'critical', $exception );

			return new \WP_Error( 'support_user_exception', $exception->getMessage(), array( 'error_code' => 500 ) );
		}

		if ( is_wp_error( $support_user_id ) ) {

			$this->logging->log( sprintf( 'Support user not created: %s (%s)', $support_user_id->get_error_message(), $support_user_id->get_error_code() ), __METHOD__, 'error' );

			$support_user_id->add_data( array( 'error_code' => 409 ) );

			return $support_user_id;
		}

		$site_identifier_hash = Encryption::get_random_hash( $this->logging );

		if ( is_wp_error( $site_identifier_hash ) ) {

			wp_delete_user( $support_user_id );

			$this->logging->log( 'Could not generate a secure secret.', __METHOD__, 'error' );

			return new \WP_Error( 'secure_secret_failed', 'Could not generate a secure secret.', array( 'error_code' => 501 ) );
		}

		$endpoint_hash = $this->endpoint->get_hash( $site_identifier_hash );

		$updated = $this->endpoint->update( $endpoint_hash );

		if ( ! $updated ) {
			$this->logging->log( 'Endpoint hash did not save or didn\'t update.', __METHOD__, 'info' );
		}

		$expiration_timestamp = $this->config->get_expiration_timestamp();

		// Add user meta, configure decay
		$did_setup = $this->support_user->setup( $support_user_id, $site_identifier_hash, $expiration_timestamp, $this->cron );

		if ( is_wp_error( $did_setup ) ) {

			wp_delete_user( $support_user_id );

			$did_setup->add_data( array( 'error_code' => 503 ) );

			return $did_setup;
		}

		if ( empty( $did_setup ) ) {
			return new \WP_Error( 'support_user_setup_failed', 'Error updating user with identifier.', array( 'error_code' => 503 ) );
		}

		$secret_id = $this->endpoint->generate_secret_id( $site_identifier_hash, $endpoint_hash );

		if ( is_wp_error( $secret_id ) ) {

			wp_delete_user( $support_user_id );

			$secret_id->add_data( array( 'error_code' => 500 ) );

			return $secret_id;
		}

		$reference_id = self::get_reference_id();

		$timing_local = timer_stop( 0, 5 );

		$return_data = array(
			'type'       => 'new',
			'site_url'   => get_site_url(),
			'endpoint'   => $endpoint_hash,
			'identifier' => $site_identifier_hash,
			'user_id'    => $support_user_id,
			'expiry'     => $expiration_timestamp,
			'reference_id' => $reference_id,
			'timing'     => array(
				'local'  => $timing_local,
				'remote' => null, // Updated later
			),
		);

		if ( ! $this->config->meets_ssl_requirement() ) {
			return new \WP_Error( 'fails_ssl_requirement', esc_html__( 'TrustedLogin requires a secure connection using HTTPS.', 'gk-gravityview' ) );
		}

		timer_start();

		try {

			add_filter( 'trustedlogin/' . $this->config->ns() . '/envelope/meta', array( $this, 'add_meta_to_envelope' ) );

			$created = $this->site_access->sync_secret( $secret_id, $site_identifier_hash, 'create' );

			remove_filter( 'trustedlogin/' . $this->config->ns() . '/envelope/meta', array( $this, 'add_meta_to_envelope' ) );

		} catch ( Exception $e ) {

			$exception_error = new \WP_Error( $e->getCode(), $e->getMessage(), array( 'status_code' => 500 ) );

			$this->logging->log( 'There was an error creating a secret.', __METHOD__, 'error', $e );

			wp_delete_user( $support_user_id );

			return $exception_error;
		}

		if ( is_wp_error( $created ) ) {

			$this->logging->log( sprintf( 'There was an issue creating access (%s): %s', $created->get_error_code(), $created->get_error_message() ), __METHOD__, 'error' );

			$created->add_data( array( 'status_code' => 503 ) );

			wp_delete_user( $support_user_id );

			return $created;
		}

		$return_data['timing']['remote'] = timer_stop( 0, 5 );

		do_action( 'trustedlogin/' . $this->config->ns() . '/access/created', array(
			'url'    => get_site_url(),
			'ns' => $this->config->ns(),
			'action' => 'created',
			'ref' => $reference_id,
		) );

		return $return_data;
	}

	/**
	 * Extends the access duration for an existing Support User
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id The existing Support User ID
	 *
	 * @return array|WP_Error
	 */
	private function extend_access( $user_id ) {

		timer_start();

		$expiration_timestamp = $this->config->get_expiration_timestamp();

		$site_identifier_hash = $this->support_user->get_site_hash( $user_id );

		if ( is_wp_error( $site_identifier_hash ) ) {

			$this->logging->log( sprintf( 'Could not get identifier hash for existing support user account. %s (%s)', $site_identifier_hash->get_error_message(), $site_identifier_hash->get_error_code() ), __METHOD__, 'critical' );

			return $site_identifier_hash;
		}

		$extended = $this->support_user->extend( $user_id, $site_identifier_hash, $expiration_timestamp, $this->cron );

		if ( is_wp_error( $extended ) ) {
			return $extended;
		}

		$secret_id = $this->endpoint->generate_secret_id( $site_identifier_hash );

		if ( is_wp_error( $secret_id ) ) {

			wp_delete_user( $user_id );

			$secret_id->add_data( array( 'error_code' => 500 ) );

			return $secret_id;
		}

		$timing_local = timer_stop( 0, 5 );

		$return_data = array(
			'type'       => 'extend',
			'site_url'   => get_site_url(),
			'identifier' => $site_identifier_hash,
			'user_id'    => $user_id,
			'expiry'     => $expiration_timestamp,
			'timing'     => array(
				'local'  => $timing_local,
				'remote' => null, // Updated later
			),
		);

		if ( ! $this->config->meets_ssl_requirement() ) {
			return new \WP_Error( 'fails_ssl_requirement', esc_html__( 'TrustedLogin requires a secure connection using HTTPS.', 'gk-gravityview' ) );
		}

		timer_start();

		try {

			add_filter( 'trustedlogin/' . $this->config->ns() . '/envelope/meta', array( $this, 'add_meta_to_envelope' ) );

			$updated = $this->site_access->sync_secret( $secret_id, $site_identifier_hash, 'extend' );

			remove_filter( 'trustedlogin/' . $this->config->ns() . '/envelope/meta', array( $this, 'add_meta_to_envelope' ) );

		} catch ( Exception $e ) {

			$exception_error = new \WP_Error( $e->getCode(), $e->getMessage(), array( 'status_code' => 500 ) );

			$this->logging->log( 'There was an error updating TrustedLogin servers.', __METHOD__, 'error', $e );

			wp_delete_user( $user_id );

			return $exception_error;
		}

		if ( is_wp_error( $updated ) ) {

			$this->logging->log( sprintf( 'There was an issue creating access (%s): %s', $updated->get_error_code(), $updated->get_error_message() ), __METHOD__, 'error' );

			$updated->add_data( array( 'status_code' => 503 ) );

			wp_delete_user( $user_id );

			return $updated;
		}

		$return_data['timing']['remote'] = timer_stop( 0, 5 );

		do_action( 'trustedlogin/' . $this->config->ns() . '/access/extended', array(
			'url'    => get_site_url(),
			'ns' => $this->config->ns(),
			'action' => 'extended',
			'ref' => self::get_reference_id(),
		) );

		return $return_data;
	}

	/**
	 * Revoke access to a site
	 *
	 * @param string $identifier Unique ID or "all"
	 *
	 * @return bool|WP_Error True: Synced to SaaS and user(s) deleted. False: empty identifier. WP_Error: failed to revoke site in SaaS or failed to delete user.
	 */
	public function revoke_access( $identifier = '' ) {

		if ( empty( $identifier ) ) {

			$this->logging->log( 'Missing the revoke access identifier.', __METHOD__, 'error' );

			return false;
		}

		if ( 'all' === $identifier ) {
			$users = $this->support_user->get_all();

			foreach ( $users as $user ) {
				$this->revoke_access( $this->support_user->get_user_identifier( $user ) );
			}
		}

		$user = $this->support_user->get( $identifier );

		if ( null === $user ) {
			$this->logging->log( 'User does not exist; access may have already been revoked.', __METHOD__, 'error' );

			return false;
		}

		$site_identifier_hash = $this->support_user->get_site_hash( $user );
		$endpoint_hash = $this->endpoint->get_hash( $site_identifier_hash );
		$secret_id = $this->endpoint->generate_secret_id( $site_identifier_hash, $endpoint_hash );

		// Revoke site in SaaS
		$site_revoked = $this->site_access->revoke( $secret_id, $this->remote );

		if ( is_wp_error( $site_revoked ) ) {

			// Couldn't sync to SaaS, this should/could be extended to add a cron-task to delayed update of SaaS DB
			// TODO: extend to add a cron-task to delayed update of SaaS DB
			$this->logging->log( 'There was an issue syncing to SaaS. Failing silently.', __METHOD__, 'error' );
		}

		$deleted_user = $this->support_user->delete( $identifier, true, true );

		if ( is_wp_error( $deleted_user ) ) {
			$this->logging->log( 'Removing user failed: ' . $deleted_user->get_error_message(), __METHOD__, 'error' );

			return $deleted_user;
		}

		$should_be_deleted = $this->support_user->get( $identifier );

		if ( ! empty( $should_be_deleted ) ) {
			$this->logging->log( 'User #' . $should_be_deleted->ID . ' was not removed', __METHOD__, 'error' );
			return new \WP_Error( 'support_user_not_deleted', esc_html__( 'The support user was not deleted.', 'gk-gravityview' ) );
		}

		/**
		 * Site was removed in SaaS, user was deleted.
		 */
		do_action( 'trustedlogin/' . $this->config->ns() . '/access/revoked', array(
			'url'    => get_site_url(),
			'ns' => $this->config->ns(),
			'action' => 'revoked',
		) );

		return $site_revoked;
	}

	/**
	 * Adds PLAINTEXT metadata to the envelope, including reference ID.
	 *
	 * @since 1.0.0
	 *
	 * @param array $metadata
	 *
	 * @return array Array of metadata that will be sent with the Envelope.
	 */
	public function add_meta_to_envelope( $metadata = array() ) {

		$reference_id = self::get_reference_id();

		if ( $reference_id ) {
			$metadata['reference_id'] = $reference_id;
		}

		return $metadata;
	}

	/**
	 * Gets the reference ID passed to the $_REQUEST using `reference_id` or `ref` keys.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Sanitized reference ID (escaped with esc_html) if exists. NULL if not.
	 */
	public static function get_reference_id() {

		if ( isset( $_REQUEST['reference_id'] ) ) {
			return esc_html( $_REQUEST['reference_id'] );
		}

		if ( isset( $_REQUEST['ref'] ) ) {
			return esc_html( $_REQUEST['ref'] );
		}

		return null;
	}

}
