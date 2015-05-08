<?php
/**
 * Roles and Capabilities
 *
 * TODO: https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/master/uninstall.php#L67-L73
 * TODO: https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/master/includes/install.php#L135-L138
 * TODO: https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/master/includes/install.php#L205-L231
 *
 * @see         https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/master/includes/class-edd-roles.php Easy Digital Downloads FTW
 * @package     GravityView
 * @license     GPL2+
 * @since       // TODO
 * @author      Katz Web Services, Inc.
 * @link        http://gravityview.co
 * @copyright   Copyright 2015, Katz Web Services, Inc.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * GravityView Roles Class
 *
 * This class handles the role creation and assignment of capabilities for those roles.
 *
 * These roles let us have Shop Accountants, Shop Workers, etc, each of whom can do
 * certain things within the EDD store
 *
 * @since 1.4.4
 */
class GravityView_Roles {

	/**
	 * Get things going
	 *
	 * @since 1.4.4
	 */
	public function __construct() {}

	/**
	 * Add new shop roles with default WP caps
	 *
	 * @access public
	 * @since 1.4.4
	 * @return void
	 */
	public function add_roles() {
	}

	/**
	 * Add new shop-specific capabilities
	 *
	 * @access public
	 * @since  1.4.4
	 * @global WP_Roles $wp_roles
	 * @return void
	 */
	public function add_caps() {
		global $wp_roles;

		if ( class_exists('WP_Roles') ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {

			// Add the main post type capabilities
			$capabilities = $this->get_core_caps();
			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'administrator', $cap );
				}
			}

		}
	}

	/**
	 * Gets the core post type capabilities
	 *
	 * @access public
	 * @since  1.4.4
	 * @return array $capabilities Core post type capabilities
	 */
	public function get_core_caps() {
		$capabilities = array();

		$capability_types = array( 'gravityview', 'gravityview_comment' );

		foreach ( $capability_types as $capability_type ) {
			$capabilities[ $capability_type ] = array(
				// Post type
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				// Terms
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms",
			);
		}

		return $capabilities;
	}


	/**
	 * Remove core post type capabilities (called on uninstall)
	 *
	 * @access public
	 * @since 1.5.2
	 * @return void
	 */
	public function remove_caps() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {

			/** Remove the Main Post Type Capabilities */
			$capabilities = $this->get_core_caps();

			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->remove_cap( 'administrator', $cap );
				}
			}

		}
	}
}
