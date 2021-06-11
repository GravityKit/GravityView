<?php
/**
 * Class Endpoint
 *
 * @package GravityView\TrustedLogin\Client
 *
 * @copyright 2020 Katz Web Services, Inc.
 *
 * @license GPL-2.0-or-later
 * Modified by gravityview on 11-June-2021 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */
namespace GravityView\TrustedLogin;

use \Exception;
use \WP_Error;
use \WP_User;
use \WP_Admin_Bar;

class Endpoint {

	/**
	 * @var string The query string parameter used to revoke users
	 */
	const REVOKE_SUPPORT_QUERY_PARAM = 'revoke-tl';

	/**
	 * @var Config $config
	 */
	private $config;

	/**
	 * The namespaced setting name for storing part of the auto-login endpoint
	 *
	 * @var string $option_name Example: `tl_{vendor/namespace}_endpoint`
	 */
	private $option_name;

	/**
	 * @var SupportUser
	 * @todo decouple
	 */
	private $support_user;

	/**
	 * @var SiteAccess
	 */
	private $site_access;

	/**
	 * @var Logging $logging
	 */
	private $logging;

	/**
	 * Logger constructor.
	 */
	public function __construct( Config $config, Logging $logging ) {

		$this->config = $config;
		$this->logging = $logging;
		$this->support_user = new SupportUser( $config, $logging );
		$this->site_access = new SiteAccess( $config, $logging );

		/**
		 * Filter: Set endpoint setting name
		 *
		 * @since 0.3.0
		 *
		 * @param string
		 * @param Config $config
		 */
		$this->option_name = apply_filters(
			'trustedlogin/' . $config->ns() . '/options/endpoint',
			'tl_' . $config->ns() . '_endpoint',
			$config
		);

	}

	public function init() {

		if ( did_action( 'init' ) ) {
			$this->add();
		} else {
			add_action( 'init', array( $this, 'add' ) );
		}

		add_action( 'template_redirect', array( $this, 'maybe_login_support' ), 99 );
		add_action( 'admin_init', array( $this, 'admin_maybe_revoke_support' ), 100 );
	}

	/**
	 * Check if the endpoint is hit and has a valid identifier before automatically logging in support agent
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function maybe_login_support() {

		if ( is_user_logged_in() ) {
			return;
		}

		$identifier = $this->get_query_var();

		if ( empty( $identifier ) ) {
			return;
		}

		/**
		 * Runs before the support user is (maybe) logged-in
		 *
		 * @param string $identifier Unique Identifier for support user.
		 */
		do_action( 'trustedlogin/' . $this->config->ns() . '/login/before', $identifier );

		$security_checks = new SecurityChecks( $this->config, $this->logging );

		// Before logging-in support, let's make sure the site isn't locked-down or that this request is flagged
		$is_verified = $security_checks->verify( $identifier );

		if ( ! $is_verified || is_wp_error( $is_verified ) ){

			/**
			 * Runs after the identifier fails security checks
			 *
			 * @param string $identifier Unique Identifier for support user.
			 * @param WP_Error $is_verified The error encountered when verifying the identifier.
			 */
			do_action( 'trustedlogin/' . $this->config->ns() . '/login/refused', $identifier, $is_verified );

			return;
		}

		$is_logged_in = $this->support_user->maybe_login( $identifier );

		if ( is_wp_error( $is_logged_in ) ) {

			/**
			 * Runs after the support user fails to log in
			 *
			 * @param string $identifier Unique Identifier for support user.
			 * @param WP_Error $is_logged_in The error encountered when logging-in.
			 */
			do_action( 'trustedlogin/' . $this->config->ns() . '/login/error', $identifier, $is_logged_in );

			return;
		}

		/**
		 * Runs after the support user is logged-in
		 *
		 * @param string $identifier Unique Identifier for support user.
		 */
		do_action( 'trustedlogin/' . $this->config->ns() . '/login/after', $identifier );

		wp_safe_redirect( admin_url() );

		exit();
	}


	/**
	 * Hooked Action to maybe revoke support if $_REQUEST[ SupportUser::ID_QUERY_PARAM ] == {namespace}
	 * Can optionally check for $_REQUEST[ SupportUser::ID_QUERY_PARAM ] for revoking a specific user by their identifier
	 *
	 * @since 0.2.1
	 */
	public function admin_maybe_revoke_support() {

		if ( ! isset( $_REQUEST[ self::REVOKE_SUPPORT_QUERY_PARAM ] ) ) {
			return;
		}

		if ( $this->config->ns() !== $_REQUEST[ self::REVOKE_SUPPORT_QUERY_PARAM ] ) {
			return;
		}

		if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		$verify_nonce = wp_verify_nonce( $_REQUEST['_wpnonce' ], self::REVOKE_SUPPORT_QUERY_PARAM );

		if ( ! $verify_nonce ) {
			$this->logging->log( 'Removing user failed: Nonce expired (Nonce value: ' . $verify_nonce . ')', __METHOD__, 'error' );

			return;
		}

		// Allow namespaced support team to revoke their own users
		$support_team = current_user_can( $this->support_user->role->get_name() );

		// As well as existing users who can delete other users
		$can_delete_users = current_user_can( 'delete_users' );

		if ( ! $support_team && ! $can_delete_users ) {
			wp_safe_redirect( home_url() );

			return;
		}

		$identifier = isset( $_REQUEST[ SupportUser::ID_QUERY_PARAM ] ) ? esc_attr( $_REQUEST[ SupportUser::ID_QUERY_PARAM ] ) : 'all';

		$deleted_user = $this->support_user->delete( $identifier );

		if ( is_wp_error( $deleted_user ) ) {
			$this->logging->log( 'Removing user failed: ' . $deleted_user->get_error_message(), __METHOD__, 'error' );
		}

		$revoked_site_in_saas = $this->site_access->revoke_access( $identifier );

		if ( is_wp_error( $revoked_site_in_saas ) ) {
			return; // Don't trigger `access_revoked` if anything fails.
		}

		$should_be_deleted = $this->support_user->get( $identifier );

		if ( ! empty( $should_be_deleted ) ) {
			$this->logging->log( 'User #' . $should_be_deleted->ID . ' was not removed', __METHOD__, 'error' );
			return; // Don't trigger `access_revoked` if anything fails.
		}

		/**
		 * Only triggered when all access has been successfully revoked and no users exist with identifier $identifer.
		 * @param string $identifier Unique TrustedLogin ID for the Support User
		 */
		do_action( 'trustedlogin/' . $this->config->ns() . '/admin/access_revoked', $identifier );
	}

	/**
	 * Hooked Action: Add a unique endpoint to WP if a support agent exists
	 *
	 * @see Endpoint::init() Called via `init` hook
	 *
	 * @since 0.3.0
	 */
	public function add() {

		$endpoint = $this->get();

		if ( ! $endpoint ) {
			return;
		}

		add_rewrite_endpoint( $endpoint, EP_ROOT );

		$this->logging->log( "Endpoint {$endpoint} added.", __METHOD__, 'debug' );

		if ( ! get_site_option( 'tl_permalinks_flushed' ) ) {

			flush_rewrite_rules( false );

			update_option( 'tl_permalinks_flushed', 1 );

			$this->logging->log( "Rewrite rules flushed.", __METHOD__, 'info' );
		}
	}

	/**
	 * Get the site option value at {@see option_name}
	 * @return string
	 */
	public function get() {
		return (string) get_site_option( $this->option_name );
	}

	private function get_query_var() {

		$endpoint = $this->get();

		$query_var = get_query_var( $endpoint, false );

		$identifier = sanitize_text_field( $query_var );

		return empty( $identifier ) ? false : $identifier;
	}

	/**
	 * Generate the secret_id parameter as a hash of the endpoint with the identifier
	 *
	 * @param string $identifier_hash
	 * @param string $endpoint_hash
	 *
	 * @return string|WP_Error This hash will be used as an identifier in TrustedLogin SaaS. Or something went wrong.
	 */
	public function generate_secret_id( $identifier_hash, $endpoint_hash = '' ) {

		if ( empty( $endpoint_hash ) ) {
			$endpoint_hash = $this->get_hash( $identifier_hash );
		}

		return Encryption::hash( $endpoint_hash . $identifier_hash );
	}

	/**
	 * Generate the endpoint parameter as a hash of the site URL with the identifier
	 *
	 * @param $identifier_hash
	 *
	 * @return string This hash will be used as the first part of the URL and also a part of $secret_id
	 */
	public function get_hash( $identifier_hash ) {
		return Encryption::hash( get_site_url() . $identifier_hash );
	}

	/**
	 * Updates the site's endpoint to listen for logins. Flushes rewrite rules after updating.
	 *
	 * @param string $endpoint
	 *
	 * @return bool True: updated; False: didn't change, or didn't update
	 */
	public function update( $endpoint ) {

		$updated = update_option( $this->option_name, $endpoint, true );

		update_option( 'tl_permalinks_flushed', 0 );

		return $updated;
	}

	/**
	 *
	 * @return void
	 */
	public function delete() {

		if ( ! get_site_option( $this->option_name ) ) {
			$this->logging->log( "Endpoint not deleted because it does not exist.", __METHOD__, 'info' );

			return;
		}

		delete_site_option( $this->option_name );

		flush_rewrite_rules( false );

		update_option( 'tl_permalinks_flushed', 0 );

		$this->logging->log( "Endpoint removed & rewrites flushed", __METHOD__, 'info' );
	}
}
