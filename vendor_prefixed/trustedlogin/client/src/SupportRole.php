<?php
/**
 * Class SupportRole
 *
 * @package GravityKit\GravityView\Foundation\ThirdParty\TrustedLogin\SupportRole
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

use WP_Error;

final class SupportRole {

	/**
	 * @var Config $config
	 */
	private $config;

	/**
	 * @var Logging $logging
	 */
	private $logging;

	/**
	 * @var string $role_name The namespaced name of the new Role to be created for Support Agents
	 * @example '{vendor/namespace}-support'
	 */
	private $role_name;

	/**
	 * @var array These capabilities will never be allowed for users created by TrustedLogin
	 * @since 1.0.0
	 */
	static $prevented_caps = array(
		'create_users',
		'delete_users',
		'edit_users',
		'list_users',
		'promote_users',
		'delete_site',
		'remove_users',
	);

	/**
	 * SupportUser constructor.
	 */
	public function __construct( Config $config, Logging $logging ) {
		$this->config = $config;
		$this->logging = $logging;
		$this->role_name = $this->set_name();
	}

	/**
	 * Get the name (slug) of the role that should be cloned for the TL support role
	 *
	 * @return string
	 */
	public function get_cloned_name() {

		$roles = $this->config->get_setting( 'role', 'editor' );

		// TODO: Support multiple roles
		$role = is_array( $roles ) ? array_key_first( $roles ) : $roles;

		return (string) $role;
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return (string) $this->role_name;
	}

	/**
	 * @return string Sanitized with {@uses sanitize_title_with_dashes}
	 */
	private function set_name( ) {

		$default = $this->config->ns() . '-support';

		$role_name = apply_filters(
			'trustedlogin/' . $this->config->ns() . '/support_role',
			$default,
			$this
		);

		if ( ! is_string( $role_name ) ) {
			$role_name = $default;
		}

		return sanitize_title_with_dashes( $role_name );
	}

	/**
	 * Creates the custom Support Role if it doesn't already exist
	 *
	 * @since 1.0.0
	 * @since 1.0.0 removed excluded_caps from generated role
	 *
	 * @param string $new_role_slug    The slug for the new role (optional). Default: {@see SupportRole::get_name()}
	 * @param string $clone_role_slug  The slug for the role to clone (optional). Default: {@see SupportRole::get_cloned_name()}.
	 *
	 * @return \WP_Role|\WP_Error Created/pre-existing role, if successful. WP_Error if failure.
	 */
	public function create( $new_role_slug = '', $clone_role_slug = '' ) {

		if ( empty( $new_role_slug ) ) {
			$new_role_slug = $this->get_name();
		}

		if ( ! is_string( $new_role_slug ) ) {
			return new \WP_Error( 'new_role_slug_not_string', 'The slug for the new support role must be a string.' );
		}

		if ( empty( $clone_role_slug ) ) {
			$clone_role_slug = $this->get_cloned_name();
		}

		if ( ! is_string( $clone_role_slug ) ) {
			return new \WP_Error( 'cloned_role_slug_not_string', 'The slug for the cloned support role must be a string.' );
		}

		$role_exists = get_role( $new_role_slug );

		if ( $role_exists ) {
			$this->logging->log( 'Not creating user role; it already exists', __METHOD__, 'notice' );
			return $role_exists;
		}

		$this->logging->log( 'New role slug: ' . $new_role_slug . ', Clone role slug: ' . $clone_role_slug, __METHOD__, 'debug' );

		$old_role = get_role( $clone_role_slug );

		if ( empty( $old_role ) ) {
			return new \WP_Error( 'role_does_not_exist', 'Error: the role to clone does not exist: ' . $clone_role_slug );
		}

		$capabilities = $old_role->capabilities;

		$add_caps = $this->config->get_setting( 'caps/add' );

		foreach ( (array) $add_caps as $add_cap => $reason ) {
			$capabilities[ $add_cap ] = true;
		}

		// These roles should never be assigned to TrustedLogin roles.
		foreach ( self::$prevented_caps as $prevented_cap ) {
			unset( $capabilities[ $prevented_cap ] );
		}

		/**
		 * @filter trustedlogin/{namespace}/support_role/display_name Modify the display name of the created support role
		 */
		$role_display_name = apply_filters( 'trustedlogin/' . $this->config->ns() . '/support_role/display_name',
			// translators: %s is replaced with the name of the software developer (e.g. "Acme Widgets")
			sprintf( esc_html__( '%s Support', 'gk-gravityview' ), $this->config->get_setting( 'vendor/title' ) ),
			$this
		);

		$new_role = add_role( $new_role_slug, $role_display_name, $capabilities );

		if ( ! $new_role ){

			return new \WP_Error(
				'add_role_failed',
				'Error: the role was not created using add_role()', compact(
					"new_role_slug",
					"capabilities",
					"role_display_name"
				)
			);

		}

		$remove_caps = $this->config->get_setting( 'caps/remove' );

		if ( ! empty( $remove_caps ) ){

			foreach ( $remove_caps as $remove_cap => $description ){
				$new_role->remove_cap( $remove_cap );
				$this->logging->log( 'Capability '. $remove_cap .' removed from role.', __METHOD__, 'info' );
			}
		}

		return $new_role;
	}

	/**
	 * @return bool|null Null: Role wasn't found; True: Removing role succeeded; False: Role wasn't deleted successfully.
	 */
	public function delete() {

		if ( ! get_role( $this->get_name() ) ) {
			return null;
		}

		// Returns void; no way to tell if successful
		remove_role( $this->get_name() );

		if( get_role( $this->get_name() ) ) {

			$this->logging->log( "Role " . $this->get_name() . " was not removed successfully.", __METHOD__, 'error' );

			return false;
		}

		$this->logging->log( "Role " . $this->get_name() . " removed.", __METHOD__, 'info' );

		return true;
	}
}
