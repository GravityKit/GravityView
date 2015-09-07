<?php
/**
 * Roles and Capabilities
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
 * @since 1.14
 */
class GravityView_Roles {

	/**
	 * @var GravityView_Roles|null
	 */
	static $instance = null;

	/**
	 * @since 1.14
	 * @return GravityView_Roles
	 */
	public static function get_instance() {

		if( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Get things going
	 *
	 * @since 1.14
	 */
	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * Call hooks
	 * @since 1.14
	 */
	private function add_hooks() {
		add_filter( 'members_get_capabilities', array( $this, 'members_get_capabilities' ) );
	}

	/**
	 * Add GravityView capabilities to the Members plugin
	 *
	 * @since 1.14
	 * @param array $caps Existing capabilities registered with Members
	 * @return array Modified capabilities array
	 */
	public function members_get_capabilities( $caps ) {
		return array_merge( $caps, $this->all_caps('all') );
	}

	/**
	 * Retrieves the global WP_Roles instance and instantiates it if necessary.
	 *
	 * @see wp_roles() This method uses the exact same code as wp_roles(), here for backward compatibility
	 *
	 * @global WP_Roles $wp_roles WP_Roles global instance.
	 *
	 * @return WP_Roles WP_Roles global instance if not already instantiated.
	 */
	function wp_roles() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		return $wp_roles;
	}

	/**
	 * Add capabilities to their respective roles
	 *
	 * @since 1.14
	 * @return void
	 */
	public function add_caps() {

		$wp_roles = $this->wp_roles();

		if ( is_object( $wp_roles ) ) {

			foreach( $wp_roles->get_names() as $role_slug => $role_label ) {

				$capabilities = $this->all_caps( $role_slug );

				foreach( $capabilities as $cap ) {
					$wp_roles->add_cap( $role_slug, $cap );
				}
			}
		}
	}

	/**
	 * Get an array of GravityView capabilities
	 *
	 * @see get_post_type_capabilities()
	 *
	 * @since 1.14
	 *
	 * @param string $role If set, get the caps for a specific role. Pass 'all' to get all caps in a flat array. Default: ''
	 *
	 * @return array If $role is set, flat array of caps. Otherwise, a multi-dimensional array of roles and their caps with the following keys: 'administrator', 'editor', 'author', 'contributor', 'subscriber'
	 */
	public function all_caps( $role = '' ) {

		$administrator = array(
			// Settings
			'gravityview_view_settings',
			'gravityview_edit_settings',
		);

		// Edit, publish, delete own and others' stuff
		$editor = array(
			'edit_others_gravityviews',
			'read_private_gravityviews',
			'delete_private_gravityviews',
			'delete_others_gravityviews',
			'edit_private_gravityviews',
			'publish_gravityviews',
			'delete_published_gravityviews',
			'edit_published_gravityviews',

			// GF caps
			'gravityview_edit_others_entries',

			// GF caps
			'gravityview_view_others_entry_notes',
			'gravityview_moderate_entries',
			'gravityview_delete_others_entries',
		);

		// Edit, publish and delete own stuff
		$author = array(

			// GF caps
			'gravityview_edit_entries',
			'gravityview_view_entry_notes',
			'gravityview_delete_entries',

		);

		// Edit and delete drafts but not publish
		$contributor = array(
			'edit_gravityview',
			'edit_gravityviews',
			'delete_gravityview',
			'delete_gravityviews',
		);

		// Read only
		$subscriber = array(
			'read_gravityview',
		);

		$capabilities = array();

		switch( $role ) {
			case 'subscriber':
				$capabilities = $subscriber;
				break;
			case 'contributor':
				$capabilities = array_merge( $contributor, $subscriber );
				break;
			case 'author':
				$capabilities = array_merge( $author, $contributor, $subscriber );
				break;
			case 'editor':
				$capabilities = array_merge( $editor, $author, $contributor, $subscriber );
				break;
			case 'administrator':
			case 'all':
				$capabilities = array_merge( $administrator, $editor, $author, $contributor, $subscriber );
				break;
		}

		// If role is set, return empty array if not exists
		if( $role ) {
			return isset( $capabilities[ $role ] ) ? $capabilities[ $role ] : array();
		}

		// By default, return multi-dimensional array of all caps
		return compact( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
	}


	/**
	 * Remove all GravityView caps from all roles
	 *
	 * @since 1.14
	 * @return void
	 */
	public function remove_caps() {

		$wp_roles = $this->wp_roles();

		if ( is_object( $wp_roles ) ) {

			/** Remove all GravityView caps from all roles */
			$capabilities = $this->all_caps('all');

			// Loop through each role and remove GV caps
			foreach( $wp_roles->get_names() as $role_slug => $role_name ) {
				foreach ( $capabilities as $cap ) {
					$wp_roles->remove_cap( $role_slug, $cap );
				}
			}
		}
	}
}
