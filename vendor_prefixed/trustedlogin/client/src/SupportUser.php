<?php
/**
 * Class SupportUser
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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Exception;
use \WP_Error;
use \WP_User;
use \WP_Admin_Bar;

/**
 * The TrustedLogin all-in-one drop-in class.
 */
final class SupportUser {

	/**
	 * @var string The query parameter used to pass the unique user ID
	 */
	const ID_QUERY_PARAM = 'tlid';

	/**
	 * @var Config $config
	 */
	private $config;

	/**
	 * @var Logging $logging
	 */
	private $logging;

	/**
	 * @var SupportRole $role
	 */
	public $role;

	/**
	 * @var string $user_identifier_meta_key The namespaced setting name for storing the unique identifier hash in user meta
	 * @since 1.0.0
	 * @example tl_{vendor/namespace}_id
	 */
	private $user_identifier_meta_key;

	/**
	 * @var string $site_hash_meta_key The namespaced setting name for storing the site identifier hash in user meta
	 * @since 1.0.0
	 * @example tl_{vendor/namespace}_site_hash
	 */
	private $site_hash_meta_key;

	/**
	 * @var int $expires_meta_key The namespaced setting name for storing the timestamp the user expires
	 * @since 1.0.0
	 * @example tl_{vendor/namespace}_expires
	 */
	private $expires_meta_key;

	/**
	 * @var int $created_by_meta_key The ID of the user who created the TrustedLogin access
	 * @since 1.0.0
	 */
	private $created_by_meta_key;

	/**
	 * SupportUser constructor.
	 */
	public function __construct( Config $config, Logging $logging ) {
		$this->config  = $config;
		$this->logging = $logging;
		$this->role    = new SupportRole( $config, $logging );

		$this->user_identifier_meta_key = 'tl_' . $config->ns() . '_id';
		$this->site_hash_meta_key       = 'tl_' . $config->ns() . '_site_hash';
		$this->expires_meta_key         = 'tl_' . $config->ns() . '_expires';
		$this->created_by_meta_key      = 'tl_' . $config->ns() . '_created_by';
	}

	/**
	 * Allow accessing limited private properties with a magic method.
	 *
	 * @param string $name Name of property
	 *
	 * @return string|null Value of property, if defined. Otherwise, null.
	 */
	public function __get( $name ) {

		// Allow accessing limited private variables
		switch ( $name ) {
			case 'identifier_meta_key':
			case 'expires_meta_key':
			case 'created_by_meta_key':
				return $this->{$name};
				break;
		}

		return null;
	}

	/**
	 * Checks if a Support User for this vendor has already been created.
	 *
	 * @since 1.0.0
	 *
	 * @return int|false - WP User ID if support user exists, otherwise false.
	 */
	public function exists() {

		$args = array(
			'role'         => $this->role->get_name(),
			'number'       => 1,
			'meta_key'     => $this->user_identifier_meta_key,
			'meta_value'   => '',
			'meta_compare' => 'EXISTS',
			'fields'       => 'ID',
		);

		$user_ids = get_users( $args );

		return empty( $user_ids ) ? false : (int) $user_ids[0];
	}

	/**
	 * Returns whether the support user exists and has an expiration time in the future.
	 *
	 * @since 1.0.2
	 *
	 * @return bool True: Support user exists and has an expiration time in the future. False: Any of those things aren't true.
	 */
	public function is_active( $passed_user = null ) {

		$current_user = is_a( $passed_user, '\WP_User' ) ? $passed_user : wp_get_current_user();

		if ( ! $current_user || ! $current_user->exists() ) {
			return false;
		}

		$expiration = $this->get_expiration( $current_user, false, true );

		if ( ! $expiration ) {
			return false;
		}

		if ( time() > (int) $expiration ) {
			return false;
		}

		return true;
	}

	/**
	 * Create the Support User with custom role.
	 *
	 * @since 1.0.0
	 *
	 * @uses wp_insert_user()
	 *
	 * @return int|WP_Error - Array with login response information if created, or WP_Error object if there was an issue.
	 */
	public function create() {

		$user_id = $this->exists();

		// Double-check that a user doesn't exist before trying to create a new one.
		if ( $user_id ) {
			$this->logging->log( 'Support User not created; already exists: User #' . $user_id, __METHOD__, 'notice' );

			return new \WP_Error( 'user_exists', sprintf( 'A user with the User ID %d already exists', $user_id ) );
		}

		$role_exists = $this->role->create();

		if ( is_wp_error( $role_exists ) ) {

			$error_output = $role_exists->get_error_message();

			if ( $error_data = $role_exists->get_error_data() ) {
				$error_output .= ' ' . print_r( $error_data, true );
			}

			$this->logging->log( $error_output, __METHOD__, 'error' );

			return $role_exists;
		}

		$user_email = $this->config->get_setting( 'vendor/email' );

		if ( defined( 'LOGGED_IN_KEY' ) && defined( 'NONCE_KEY' ) ) {
			// The hash doesn't need to be secure, just persistent.
			$user_email = str_replace( '{hash}', sha1( LOGGED_IN_KEY . NONCE_KEY . get_current_blog_id() ), $user_email );
		}

		if ( email_exists( $user_email ) ) {
			$this->logging->log( 'Support User not created; a user with that email already exists: ' . $user_email, __METHOD__, 'warning' );

			return new \WP_Error( 'email_exists', esc_html__( 'User not created; User with that email already exists', 'gk-gravityview' ) );
		}

		$user_data = array(
			'user_url'        => $this->config->get_setting( 'vendor/website' ),
			'user_login'      => $this->generate_unique_username(),
			'user_email'      => $user_email,
			'user_pass'       => Encryption::get_random_hash( $this->logging ),
			'role'            => $this->role->get_name(),
			'display_name'    => $this->config->get_setting( 'vendor/display_name', '' ),
			'user_registered' => date( 'Y-m-d H:i:s', time() ),
		);

		$new_user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $new_user_id ) ) {
			$this->logging->log( 'Error: User not created because: ' . $new_user_id->get_error_message(), __METHOD__, 'error' );

			return $new_user_id;
		}

		$this->logging->log( 'Support User #' . $new_user_id, __METHOD__, 'info' );

		return $new_user_id;
	}

	/**
	 * Always return a unique username
	 *
	 * @return string Username, with possible number trailing, if clashes exist.
	 */
	private function generate_unique_username() {

		// translators: %s is replaced with the name of the software developer (e.g. "Acme Widgets")
		$username = sprintf( esc_html__( '%s Support', 'gk-gravityview' ), $this->config->get_setting( 'vendor/title' ) );

		if ( ! username_exists( $username ) ) {
			return $username;
		}

		$i            = 1;
		$new_username = $username;
		while ( username_exists( $new_username ) ) {
			$new_username = sprintf( '%s %d', $username, $i + 1 );
		}

		return $new_username;
	}

	/**
	 * Returns the site secret ID connected to the support user.
	 *
	 * @param string $user_identifier
	 *
	 * @return string|WP_Error|null Returns the secret ID. WP_Error if there was a problem generating any hashes. Null: No users were found using that user identifier.
	 */
	public function get_secret_id( $user_identifier ) {

		$user = $this->get( $user_identifier );

		if ( is_null( $user ) ) {
			return null;
		}

		$site_identifier_hash = $this->get_site_hash( $user );

		if ( is_wp_error( $site_identifier_hash ) ) {
			return $site_identifier_hash;
		}

		$Endpoint = new Endpoint( $this->config, $this->logging );

		return $Endpoint->generate_secret_id( $site_identifier_hash );
	}

	/**
	 * Logs in a support user, if any exist at $user_identifier and haven't expired yet
	 *
	 * If the user access has expired, deletes the user with {@see SupportUser::delete()}
	 *
	 * @param string $user_identifier Unique identifier for support user before being hashed.
	 *
	 * @return true|WP_Error
	 */
	public function maybe_login( $user_identifier ) {

		$support_user = $this->get( $user_identifier );

		if ( empty( $support_user ) ) {

			$this->logging->log( 'Support user not found at identifier ' . esc_attr( $user_identifier ), __METHOD__, 'notice' );

			return new \WP_Error( 'user_not_found', sprintf( 'Support user not found at identifier %s.', esc_attr( $user_identifier ) ) );
		}

		$is_active = $this->is_active( $support_user );

		// This user has expired, but the cron didn't run...
		if ( ! $is_active ) {

			$expires = $this->get_expiration( $support_user, false, true );

			$this->logging->log( 'The user was supposed to expire on ' . $expires . '; revoking now.', __METHOD__, 'warning' );

			$this->delete( $user_identifier, true, true );

			return new \WP_Error( 'access_expired', 'The user was supposed to expire on ' . $expires . '; revoking now.' );
		}

		$this->login( $support_user );

		return true;
	}

	/**
	 * Processes login (with extra logging) and triggers the 'trustedlogin/{ns}/login' hook
	 *
	 * @param \WP_User $support_user
	 */
	private function login( \WP_User $support_user ) {

		if ( ! $support_user->exists() ) {

			$this->logging->log( sprintf( 'Login failed: Support User #%d does not exist.', $support_user->ID ), __METHOD__, 'error' );

			return;
		}

		wp_set_current_user( $support_user->ID, $support_user->user_login );
		wp_set_auth_cookie( $support_user->ID );

		do_action( 'wp_login', $support_user->user_login, $support_user );

		$this->logging->log( sprintf( 'Support User #%d logged in', $support_user->ID ), __METHOD__, 'notice' );

		/**
		 * Action run when TrustedLogin has logged-in
		 */
		do_action( 'trustedlogin/' . $this->config->ns() . '/logged_in', array(
			'url'    => get_site_url(),
			'action' => 'logged_in',
		) );
	}

	/**
	 * Helper Function: Get the generated support user(s).
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_identifier_or_hash
	 *
	 * @return \WP_User|null WP_User if found; null if not
	 */
	public function get( $user_identifier_or_hash = '' ) {

		if ( empty( $user_identifier_or_hash ) ) {
			return null;
		}

		$user_identifier_hash = $user_identifier_or_hash;

		// When passed in the endpoint URL, the unique ID will be the raw value, not the hash.
		if ( strlen( $user_identifier_or_hash ) > 32 ) {
			$user_identifier_hash = Encryption::hash( $user_identifier_or_hash );
		}

		$args = array(
			'role'       => $this->role->get_name(),
			'number'     => 1,
			'meta_key'   => $this->user_identifier_meta_key,
			'meta_value' => $user_identifier_hash,
		);

		$user = get_users( $args );

		return empty( $user ) ? null : $user[0];
	}

	/**
	 * Returns the expiration for user access as either a human-readable string or timestamp.
	 *
	 * @param \WP_User $user
	 * @param bool $human_readable Whether to show expiration as a human_time_diff()-formatted string. Default: false.
	 * @param bool $gmt Whether to use GMT timestamp in the human-readable result. Not used if $human_readable is false. Default: false.
	 *
	 * @return int|string|false False if no expiration is set. Expiration timestamp if $human_readable is false. Time diff if $human_readable is true.
	 */
	public function get_expiration( \WP_User $user, $human_readable = false, $gmt = false ) {

		$expiration = get_user_option( $this->expires_meta_key, $user->ID );

		if ( ! $expiration ) {
			return false;
		}

		return $human_readable ? human_time_diff( current_time( 'timestamp', $gmt ), $expiration ) : $expiration;
	}

	/**
	 * Get all users with the support role
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_User[]
	 */
	public function get_all() {

		static $support_users = null;

		// Only fetch once per process
		if ( ! is_null( $support_users ) ) {
			return $support_users;
		}

		$args = array(
			'role' => $this->role->get_name(),
		);

		return get_users( $args );
	}


	/**
	 * Returns the first support user active on the site, if any.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_User|null
	 */
	public function get_first() {
		$support_users = $this->get_all();

		if ( $support_users ) {
			return $support_users[0];
		}

		return null;
	}

	/**
	 * Deletes support user(s) with options to delete the TrustedLogin-created user role and endpoint as well
	 *
	 * @used-by SupportUser::maybe_login() Called when user access has expired, but the cron didn't run...
	 * @used-by Client::revoke_access()
	 *
	 * @param string $user_identifier Unique identifier of the user to delete.
	 * @param bool $delete_role Should the TrustedLogin-created user role be deleted also? Default: `true`.
	 * @param bool $delete_endpoint Should the TrustedLogin endpoint for the site be deleted also? Default: `true`.
	 *
	 * @return bool|WP_Error True: Successfully removed user and role; false: There are no support users matching $user_identifier; WP_Error: something went wrong.
	 */
	public function delete( $user_identifier = '', $delete_role = true, $delete_endpoint = true ) {

		require_once ABSPATH . 'wp-admin/includes/user.php'; // Needed for wp_delete_user()

		$user = $this->get( $user_identifier );

		if ( empty( $user ) ) {
			return false;
		}

		$reassign_id_or_null = $this->get_reassign_user_id();

		$this->logging->log( 'Processing user ID ' . $user->ID, __METHOD__, 'debug' );

		// Remove auto-cleanup hook
		wp_clear_scheduled_hook( 'trustedlogin/' . $this->config->ns() . '/access/revoke', array( $user_identifier ) );

		// Delete first using wp_delete_user() to allow for reassignment of posts
		$deleted = wp_delete_user( $user->ID, $reassign_id_or_null );

		// Also delete the user from the all sites on the WP Multisite network
		$wpmu_deleted = \function_exists( 'wpmu_delete_user' ) ? wpmu_delete_user( $user->ID ) : false;

		if ( $deleted ) {
			$message = 'User: ' . $user->ID . ' deleted.';

			if ( $wpmu_deleted ) {
				$message .= ' Also deleted from the Multisite network.';
			}

			$this->logging->log( $message, __METHOD__, 'info' );
		} else {
			$this->logging->log( 'User: ' . $user->ID . ' was NOT deleted.', __METHOD__, 'error' );
		}

		if ( $delete_role ) {
			$this->role->delete();
		}

		if ( $delete_endpoint ) {
			$Endpoint = new Endpoint( $this->config, $this->logging );
			$Endpoint->delete();
		}

		// Re-run to make sure there were no race conditions
		return $this->delete( $user_identifier );
	}

	/**
	 * Get the ID of the best-guess appropriate admin user
	 *
	 * @since 1.0.0
	 *
	 * @return int|null User ID if there are admins, null if not
	 */
	private function get_reassign_user_id() {

		if ( ! $this->config->get_setting( 'reassign_posts' ) ) {
			return null;
		}

		// TODO: Add a filter to modify who gets auto-reassigned
		$admins = get_users( array(
			'role'    => 'administrator',
			'orderby' => 'registered',
			'order'   => 'DESC',
			'number'  => 1,
		) );

		$reassign_id = empty( $admins ) ? null : $admins[0]->ID;

		$this->logging->log( 'Reassign user ID: ' . var_export( $reassign_id, true ), __METHOD__, 'info' );

		return $reassign_id;
	}

	/**
	 * Schedules cron job to auto-revoke, adds user meta with unique ids
	 *
	 * @param int $user_id ID of generated support user
	 * @param string $site_identifier_hash
	 * @param int $decay_timestamp Timestamp when user will be removed
	 *
	 * @return string|WP_Error Value of $identifier_meta_key if worked; empty string or WP_Error if not.
	 */
	public function setup( $user_id, $site_identifier_hash, $expiration_timestamp = null, Cron $cron = null ) {

		if ( $expiration_timestamp ) {

			$scheduled = $cron->schedule( $expiration_timestamp, $site_identifier_hash );

			if ( $scheduled ) {
				update_user_option( $user_id, $this->expires_meta_key, $expiration_timestamp );
			}
		}

		$user_identifier = Encryption::hash( $site_identifier_hash );

		if ( is_wp_error( $user_identifier ) ) {
			return $user_identifier;
		}

		update_user_option( $user_id, $this->site_hash_meta_key, $site_identifier_hash, true );
		update_user_option( $user_id, $this->user_identifier_meta_key, $user_identifier, true );
		update_user_option( $user_id, $this->created_by_meta_key, get_current_user_id() );

		// Make extra sure that the identifier was saved. Otherwise, things won't work!
		return get_user_option( $this->user_identifier_meta_key, $user_id );
	}

	/**
	 * Updates the scheduled cron job to auto-revoke and updates the Support User's meta.
	 *
	 * @param int $user_id ID of generated support user.
	 * @param string $site_identifier_hash The unique identifier for the WP_User created {@see Encryption::get_random_hash()}
	 * @param int $expiration_timestamp Timestamp when user will be removed. Throws error if null/empty.
	 * @param Cron|null $cron Optional. The Cron object for handling scheduling. Defaults to null.
	 *
	 * @return string|WP_Error Value of $identifier_meta_key if worked; empty string or WP_Error if not.
	 */
	public function extend( $user_id, $site_identifier_hash, $expiration_timestamp = null, $cron = null ) {

		if ( ! $user_id || ! $site_identifier_hash || ! $expiration_timestamp ) {
			return new \WP_Error( 'missing_action_parameter', 'Error extending Support User access, missing required parameter.' );
		}

		if ( ! $cron instanceof Cron ) {
			// Avoid a Fatal error if `$cron` parameter is not provided.
			$cron = new Cron( $this->config, $this->logging );
		}

		$rescheduled = $cron->reschedule( $expiration_timestamp, $site_identifier_hash );

		if ( $rescheduled ) {
			update_user_option( $user_id, $this->expires_meta_key, $expiration_timestamp );

			return true;
		}

		return new \WP_Error( 'extend_failed', 'Error rescheduling cron task' );

	}

	/**
	 * @param \WP_User|int $user_id_or_object User ID or User object
	 *
	 * @return string|WP_Error User unique identifier if success; WP_Error if $user is not int or WP_User.
	 */
	public function get_user_identifier( $user_id_or_object ) {

		if ( empty( $this->user_identifier_meta_key ) ) {
			$this->logging->log( 'The meta key to identify users is not set.', __METHOD__, 'error' );

			return new \WP_Error( 'missing_meta_key', 'The SupportUser object has not been properly instantiated.' );
		}

		if ( $user_id_or_object instanceof \WP_User ) {
			$user_id = $user_id_or_object->ID;
		} elseif ( is_int( $user_id_or_object ) ) {
			$user_id = $user_id_or_object;
		} else {

			$this->logging->log( 'The $user_id_or_object value must be int or WP_User: ' . var_export( $user_id_or_object, true ), __METHOD__, 'error' );

			return new \WP_Error( 'invalid_type', '$user must be int or WP_User' );
		}

		return get_user_option( $this->user_identifier_meta_key, $user_id );
	}

	/**
	 * @param WP_User|int $user_id_or_object User ID or User object
	 *
	 * @return string|WP_Error User unique identifier if success; WP_Error if $user is not int or WP_User.
	 */
	public function get_site_hash( $user_id_or_object ) {

		if ( empty( $this->site_hash_meta_key ) ) {
			$this->logging->log( 'The constructor has not been properly instantiated; the site_hash_meta_key property is not set.', __METHOD__, 'error' );

			return new \WP_Error( 'missing_meta_key', 'The SupportUser object has not been properly instantiated.' );
		}

		if ( $user_id_or_object instanceof \WP_User ) {
			$user_id = $user_id_or_object->ID;
		} elseif ( is_int( $user_id_or_object ) ) {
			$user_id = $user_id_or_object;
		} else {

			$this->logging->log( 'The $user_id_or_object value must be int or WP_User: ' . var_export( $user_id_or_object, true ), __METHOD__, 'error' );

			return new \WP_Error( 'invalid_type', '$user must be int or WP_User' );
		}

		return get_user_option( $this->site_hash_meta_key, $user_id );
	}

	/**
	 * Returns admin URL to revoke support user
	 *
	 * @uses SupportUser::get_user_identifier()
	 *
	 * @since 1.1 Removed second parameter $current_url.
	 *
	 * @param \WP_User|int|string $user User object, user ID, or "all". If "all", will revoke all users.
	 *
	 * @return string|false Unsanitized nonce URL to revoke support user. If not able to retrieve user identifier, returns false.
	 */
	public function get_revoke_url( $user ) {

		// If "all", will revoke all support users.
		if ( 'all' === $user ) {
			$user_identifier = 'all';
		} else {
			$user_identifier = $this->get_user_identifier( $user );
		}

		if ( ! $user_identifier || is_wp_error( $user_identifier ) ) {
			return false;
		}

		$revoke_url = add_query_arg( array(
			Endpoint::REVOKE_SUPPORT_QUERY_PARAM => $this->config->ns(),
			self::ID_QUERY_PARAM                 => $user_identifier,
			'_wpnonce'                           => wp_create_nonce( Endpoint::REVOKE_SUPPORT_QUERY_PARAM ),
		), admin_url() );

		$this->logging->log( "revoke_url: $revoke_url", __METHOD__, 'debug' );

		return $revoke_url;
	}
}
