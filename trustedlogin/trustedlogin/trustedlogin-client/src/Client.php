<?php
/**
 * The TrustedLogin drop-in class. Include this file and instantiate the class and you have secure support.
 *
 * @version 0.9.2
 * @copyright 2020 Katz Web Services, Inc.
 *
 * ###                    ###
 * ###   HEY DEVELOPER!   ###
 * ###                    ###
 * ###  (read me first)   ###
 *
 * Thanks for integrating TrustedLogin.
 *
 * 0. If you haven't already, sign up for a TrustedLogin account {@see https://www.trustedlogin.com}
 * 1. Prefix the namespace below with your own namespace (`namespace \ReplaceThisExample\TrustedLogin;`)
 * 2. Instantiate this class with a configuration array ({@see https://www.trustedlogin.com/configuration/} for more info)
 *
 * @license GPL-2.0-or-later
 * Modified by gravityview on 04-June-2021 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */
namespace GravityView\TrustedLogin;

// Exit if accessed directly
if ( ! defined('ABSPATH') ) {
	exit;
}

use \Exception;
use \WP_Error;
use \WP_User;
use \WP_Admin_Bar;

/**
 * The TrustedLogin all-in-one drop-in class.
 */
final class Client {

	/**
	 * @var string The current drop-in file version
	 * @since 0.1.0
	 */
	const VERSION = '0.9.6';

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
	 * @var Encryption
	 */
	private $encryption;

	/**
	 * TrustedLogin constructor.
	 *
	 * @see https://docs.trustedlogin.com/ for more information
	 *
	 * @param Config $config
	 * @param bool $init Whether to initialize everything on instantiation
	 *
	 * @returns Client|\Exception
	 */
	public function __construct( Config $config, $init = true ) {

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

		$this->encryption = new Encryption( $this->config, $this->remote, $this->logging );

		if ( $init ) {
			$this->init();
		}
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
	 * This creates a TrustedLogin user ✨
	 *
	 * @return array|WP_Error
	 */
	public function grant_access() {

		if ( ! self::$valid_config ) {
			return new WP_Error( 'invalid_configuration', 'TrustedLogin has not been properly configured or instantiated.', array( 'error_code' => 424 ) );
		}

		if ( ! current_user_can( 'create_users' ) ) {
			return new WP_Error( 'no_cap_create_users', 'Permissions issue: You do not have the ability to create users.', array( 'error_code' => 403 ) );
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

			return new WP_Error( 'support_user_exception', $exception->getMessage(), array( 'error_code' => 500 ) );
		}

		if ( is_wp_error( $support_user_id ) ) {

			$this->logging->log( sprintf( 'Support user not created: %s (%s)', $support_user_id->get_error_message(), $support_user_id->get_error_code() ), __METHOD__, 'error' );

			$support_user_id->add_data( array( 'error_code' => 409 ) );

			return $support_user_id;
		}

		$identifier_hash = $this->site_access->create_hash();

		if ( is_wp_error( $identifier_hash ) ) {

			wp_delete_user( $support_user_id );

			$this->logging->log( 'Could not generate a secure secret.', __METHOD__, 'error' );

			return new WP_Error( 'secure_secret_failed', 'Could not generate a secure secret.', array( 'error_code' => 501 ) );
		}

		$endpoint_hash = $this->endpoint->get_hash( $identifier_hash );

		$updated = $this->endpoint->update( $endpoint_hash );

		if ( ! $updated ) {
			$this->logging->log( 'Endpoint hash did not save or didn\'t update.', __METHOD__, 'info' );
		}

		$expiration_timestamp = $this->config->get_expiration_timestamp();

		// Add user meta, configure decay
		$did_setup = $this->support_user->setup( $support_user_id, $identifier_hash, $expiration_timestamp, $this->cron );

		if ( is_wp_error( $did_setup ) ) {

			wp_delete_user( $support_user_id );

			$did_setup->add_data( array( 'error_code' => 503 ) );

			return $did_setup;
		}

		if ( empty( $did_setup ) ) {
			return new WP_Error( 'support_user_setup_failed', 'Error updating user with identifier.', array( 'error_code' => 503 ) );
		}

		$secret_id = $this->endpoint->generate_secret_id( $identifier_hash, $endpoint_hash );

		if ( is_wp_error( $secret_id ) ) {

			wp_delete_user( $support_user_id );

			$secret_id->add_data( array( 'error_code' => 500 ) );

			return $secret_id;
		}

		$timing_local = timer_stop( 0, 5 );

		$return_data = array(
			'type'       => 'new',
			'site_url'   => get_site_url(),
			'endpoint'   => $endpoint_hash,
			'identifier' => $identifier_hash,
			'user_id'    => $support_user_id,
			'expiry'     => $expiration_timestamp,
			'access_key' => $secret_id,
			'is_ssl'     => is_ssl(),
			'timing'     => array(
				'local'  => $timing_local,
				'remote' => null,
			),
		);

		if ( ! $this->config->meets_ssl_requirement() ) {
			// TODO: If fails test, return WP_Error instead
			// TODO: Write test for this
			return new WP_Error( 'fails_ssl_requirement', __( 'TODO', 'trustedlogin' ) );
		}

		timer_start();

		try {

			$created = $this->site_access->sync_secret( $secret_id, $identifier_hash, 'create' );

		} catch ( Exception $e ) {

			$exception_error = new WP_Error( $e->getCode(), $e->getMessage(), array( 'status_code' => 500 ) );

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
			'action' => 'created'
		) );

		return $return_data;
	}

	/**
	 * @return string|null
	 */
	public function get_access_key() {
		return $this->site_access->get_access_key();
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
		$identifier_hash      = $this->support_user->get_user_identifier( $user_id );

		if ( is_wp_error( $identifier_hash ) ) {

			$this->logging->log( sprintf( 'Could not get identifier hash for existing support user account. %s (%s)', $identifier_hash->get_error_message(), $identifier_hash->get_error_code() ), __METHOD__, 'critical' );

			return $identifier_hash;
		}

		$extended = $this->support_user->extend( $user_id, $identifier_hash, $expiration_timestamp, $this->cron );

		if ( is_wp_error( $extended ) ) {
			return $extended;
		}

		$secret_id = $this->endpoint->generate_secret_id( $identifier_hash );

		if ( is_wp_error( $secret_id ) ) {

			wp_delete_user( $user_id );

			$secret_id->add_data( array( 'error_code' => 500 ) );

			return $secret_id;
		}

		$timing_local = timer_stop( 0, 5 );

		$return_data = array(
			'type'       => 'extend',
			'site_url'   => get_site_url(),
			'identifier' => $identifier_hash,
			'user_id'    => $user_id,
			'expiry'     => $expiration_timestamp,
			'is_ssl'     => is_ssl(),
			'timing'     => array(
				'local'  => $timing_local,
				'remote' => null,
			),
		);

		if ( ! $this->config->meets_ssl_requirement() ) {
			// TODO: If fails test, return WP_Error instead
			// TODO: Write test for this
			return new WP_Error( 'fails_ssl_requirement', __( 'TODO', 'trustedlogin' ) );
		}

		timer_start();

		try {

			$updated = $this->site_access->sync_secret( $secret_id, $identifier_hash, 'extend' );

		} catch ( Exception $e ) {

			$exception_error = new WP_Error( $e->getCode(), $e->getMessage(), array( 'status_code' => 500 ) );

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
			'action' => 'extended',
		) );

		return $return_data;
	}

}
