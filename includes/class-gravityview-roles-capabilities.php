<?php
/**
 * Roles and Capabilities
 *
 * @package     GravityView
 * @license     GPL2+
 * @since       1.14
 * @author      Katz Web Services, Inc.
 * @link        http://www.gravitykit.com
 * @copyright   Copyright 2015, Katz Web Services, Inc.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * GravityView Roles Class
 *
 * This class handles the role creation and assignment of capabilities for those roles.
 *
 * @since 1.15
 */

use GravityKit\GravityView\Foundation\Helpers\Arr;

class GravityView_Roles_Capabilities {

	/**
	 * @var GravityView_Roles_Capabilities|null
	 */
	static $instance = null;

	/**
	 * @since 1.15
	 * @return GravityView_Roles_Capabilities
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get things going
	 *
	 * @since 1.15
	 */
	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * @since 1.15
	 */
	private function add_hooks() {
		add_filter( 'members_get_capabilities', array( 'GravityView_Roles_Capabilities', 'merge_with_all_caps' ) );
		add_action( 'members_register_cap_groups', array( $this, 'members_register_cap_group' ), 20 );
		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 10, 4 );
		add_action( 'admin_init', array( $this, 'add_caps' ) );
	}


	/**
	 * Add support for `gravityview_full_access` capability, and
	 *
	 * @see map_meta_cap()
	 *
	 * @since 1.15
	 *
	 * @param array        $allcaps An array of all the user's capabilities.
	 * @param array        $caps    Actual capabilities for meta capability.
	 * @param array        $args    Optional parameters passed to has_cap(), typically object ID.
	 * @param WP_User|null $user    The user object, in WordPress 3.7.0 or higher
	 *
	 * @return mixed
	 */
	public function filter_user_has_cap( $usercaps = array(), $caps = array(), $args = array(), $user = null ) {

		// Empty caps_to_check array
		if ( ! $usercaps || ! $caps ) {
			return $usercaps;
		}

		/**
		 * Enable all GravityView caps_to_check if `gravityview_full_access` is enabled
		 */
		if ( ! empty( $usercaps['gravityview_full_access'] ) ) {

			$all_gravityview_caps = self::all_caps();

			foreach ( $all_gravityview_caps as $gv_cap ) {
				$usercaps[ $gv_cap ] = true;
			}

			unset( $all_gravityview_caps );
		}

		$usercaps = $this->add_gravity_forms_usercaps_to_gravityview_caps( $usercaps );

		return $usercaps;
	}

	/**
	 * If a user has been assigned custom capabilities for Gravity Forms, but they haven't been assigned similar abilities
	 * in GravityView yet, we give temporary access to the permissions, until they're set.
	 *
	 * This is for custom roles that GravityView_Roles_Capabilities::add_caps() doesn't modify. If you have a
	 * custom role with the ability to edit any Gravity Forms entries (`gravityforms_edit_entries`), you would
	 * expect GravityView to match that capability, until the role has been updated with GravityView caps.
	 *
	 * @since 1.15
	 *
	 * @param array $usercaps User's allcaps array
	 *
	 * @return array
	 */
	private function add_gravity_forms_usercaps_to_gravityview_caps( $usercaps ) {

		$gf_to_gv_caps = array(
			'gravityforms_edit_entries'     => 'gravityview_edit_others_entries',
			'gravityforms_delete_entries'   => 'gravityview_delete_others_entries',
			'gravityforms_view_entry_notes' => 'gravityview_view_entry_notes',
			'gravityforms_edit_entry_notes' => 'gravityview_delete_entry_notes',
		);

		foreach ( $gf_to_gv_caps as $gf_cap => $gv_cap ) {
			if ( isset( $usercaps[ $gf_cap ] ) && ! isset( $usercaps[ $gv_cap ] ) ) {
				$usercaps[ $gv_cap ] = $usercaps[ $gf_cap ];
			}
		}

		return $usercaps;
	}

	/**
	 * Add GravityView group to Members 1.x plugin management screen
	 *
	 * @see members_register_cap_group()
	 * @since 1.15
	 * @return void
	 */
	function members_register_cap_group() {
		if ( function_exists( 'members_register_cap_group' ) ) {

			$args = array(
				'label'       => __( 'GravityView', 'gk-gravityview' ),
				'icon'        => 'gv-icon-astronaut-head',
				'caps'        => self::all_caps(),
				'merge_added' => true,
				'diff_added'  => false,
			);

			members_register_cap_group( 'gravityview', $args );
		}
	}

	/**
	 * Merge capabilities array with GravityView capabilities
	 *
	 * @since 1.15 Used to add GravityView caps to the Members plugin
	 * @param array $caps Existing capabilities
	 * @return array Modified capabilities array
	 */
	public static function merge_with_all_caps( $caps ) {

		$return_caps = array_merge( $caps, self::all_caps() );

		return array_unique( $return_caps );
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
	private function wp_roles() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		return $wp_roles;
	}

	/**
	 * Add capabilities to their respective roles if they don't already exist
	 * This could be simpler, but the goal is speed.
	 *
	 * @since 1.15
	 * @return void
	 */
	public function add_caps() {

		$has_changes = false;

		$wp_roles = $this->wp_roles();

		if ( is_object( $wp_roles ) ) {

			$_use_db_backup = $wp_roles->use_db;

			/**
			 * When $use_db is true, add_cap() performs update_option() every time.
			 * We disable updating the database here, then re-enable it below.
			 */
			$wp_roles->use_db = false;

			$capabilities = self::all_caps( false, false );

			foreach ( $capabilities as $role_slug => $role_caps ) {
				$capabilities = Arr::get( $wp_roles->roles, "{$role_slug}.capabilities", [] );

				foreach ( $role_caps as $cap ) {
					// Keep the capability if it is already set.
					if ( isset( $capabilities[ $cap ] ) ) {
						continue;
					}

					$has_changes = true;
					$wp_roles->add_cap( $role_slug, $cap );
				}
			}

			if ( $has_changes ) {
				/**
				 * Update the option, as it does in add_cap when $use_db is true
				 *
				 * @see WP_Roles::add_cap() Original code
				 */
				update_option( $wp_roles->role_key, $wp_roles->roles );
			}

			/**
			 * Restore previous $use_db setting
			 */
			$wp_roles->use_db = $_use_db_backup;
		}
	}

	/**
	 * Get an array of GravityView capabilities
	 *
	 * @see get_post_type_capabilities()
	 *
	 * @since 1.15
	 *
	 * @param string  $single_role If set, get the caps_to_check for a specific role. Pass 'all' to get all caps_to_check in a flat array. Default: `all`
	 * @param boolean $flat_array True: return all caps in a one-dimensional array. False: a multi-dimensional array with `$single_role` as keys and the caps as the values
	 *
	 * @return array If $role is set, flat array of caps_to_check. Otherwise, a multi-dimensional array of roles and their caps_to_check with the following keys: 'administrator', 'editor', 'author', 'contributor', 'subscriber'
	 */
	public static function all_caps( $single_role = false, $flat_array = true ) {

		// Change settings
		$administrator_caps = array(
			'gravityview_full_access', // Grant access to all caps_to_check
			'gravityview_view_settings',
			'gravityview_edit_settings',
			'gravityview_uninstall', // Ability to trigger the Uninstall @todo
			'gravityview_contact_support', // Whether able to send a message to support via the Support Port

			'edit_others_gravityviews',
			'edit_private_gravityviews',
			'edit_published_gravityviews',
		);

		// Edit, publish, delete own and others' stuff
		$editor_caps = array(
			'read_private_gravityviews',
			'delete_private_gravityviews',
			'delete_others_gravityviews',
			'publish_gravityviews',
			'delete_published_gravityviews',
			'copy_gravityviews', // For duplicate/clone View functionality

			// GF caps_to_check
			'gravityview_edit_others_entries',
			'gravityview_moderate_entries', // Approve or reject entries from the Admin; show/hide approval column in Entries screen
			'gravityview_delete_others_entries',
			'gravityview_add_entry_notes',
			'gravityview_view_entry_notes',
			'gravityview_delete_entry_notes',
			'gravityview_email_entry_notes',
		);

		// Edit, delete own stuff
		$author_caps = array(
			// GF caps_to_check
			'gravityview_edit_entries',
			'gravityview_edit_entry',
			'gravityview_edit_form_entries', // This is similar to `gravityview_edit_entries`, but checks against a Form ID $object_id
			'gravityview_delete_entries',
			'gravityview_delete_entry',
		);

		// Edit and delete drafts but not publish
		$contributor_caps = array(
			'edit_gravityviews',
			'delete_gravityviews',
			'gravityview_getting_started', // Getting Started page access
			'gravityview_support_port', // Display GravityView Support Port
		);

		// Read only
		$subscriber_caps = array(
			'gravityview_view_entries',
			'gravityview_view_others_entries',
		);

		$subscriber    = $subscriber_caps;
		$contributor   = array_merge( $contributor_caps, $subscriber_caps );
		$author        = array_merge( $author_caps, $contributor_caps, $subscriber_caps );
		$editor        = array_merge( $editor_caps, $author_caps, $contributor_caps, $subscriber_caps );
		$administrator = array_merge( $administrator_caps, $editor_caps, $author_caps, $contributor_caps, $subscriber_caps );
		$all           = $administrator;

		// If role is set, return caps_to_check for just that role.
		if ( $single_role ) {
			$caps = isset( ${$single_role} ) ? ${$single_role} : false;
			return $flat_array ? $caps : array( $single_role => $caps );
		}

		// Otherwise, return multi-dimensional array of all caps_to_check
		return $flat_array ? $all : compact( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
	}

	/**
	 * Check whether the current user has a capability
	 *
	 * @since 1.15
	 *
	 * @see WP_User::user_has_cap()
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/user_has_cap  You can filter permissions based on entry/View/form ID using `user_has_cap` filter
	 *
	 * @see  GFCommon::current_user_can_any
	 * @uses GFCommon::current_user_can_any
	 *
	 * @param string|array $caps_to_check Single capability or array of capabilities
	 * @param int|null     $object_id (optional) Parameter can be used to check for capabilities against a specific object, such as a post or us
	 * @param int|null     $user_id (optional) Check the capabilities for a user who is not necessarily the currently logged-in user
	 *
	 * @return bool True: user has at least one passed capability; False: user does not have any defined capabilities
	 */
	public static function has_cap( $caps_to_check = '', $object_id = null, $user_id = null ) {

		/**
		 * Shall we allow a cap check for non-logged in users? USE WITH CAUTION!
		 *
		 * WARNING: This allows anyone to edit and delete entries, add notes, delete notes, etc!
		 *
		 * If you use this filter, at least check against certain capabilities and $object_ids.
		 *
		 * There are use-cases, albeit strange ones, where we'd like to check and override capabilities for
		 *  for a non-logged in user.
		 *
		 * Examples, you ask? https://github.com/gravityview/GravityView/issues/826
		 *
		 * @param boolean $allow_logged_out Allow the capability check or bail without even checking. Default: false. Do not allow. Do not pass Go. Do not collect $200.
		 * @param string|array $caps_to_check Single capability or array of capabilities to check against
		 * @param int|null $object_id (optional) Parameter can be used to check for capabilities against a specific object, such as a post or us.
		 * @param int|null $user_id (optional) Check the capabilities for a user who is not necessarily the currently logged-in user.
		 */
		$allow_logged_out = apply_filters( 'gravityview/capabilities/allow_logged_out', false, $caps_to_check, $object_id, $user_id );

		if ( true === $allow_logged_out ) {

			$all_caps = self::all_caps( 'editor' );

			if ( array_intersect( $all_caps, (array) $caps_to_check ) ) {
				return true;
			}
		}

		/**
		 * We bail with a negative response without even checking if:
		 *
		 * 1. The current user is not logged in and non-logged in users are considered unprivileged (@see `gravityview/capabilities/allow_logged_out` filter).
		 * 2. If the caps to check are empty.
		 */
		if ( ( ! is_user_logged_in() && ! $allow_logged_out ) || empty( $caps_to_check ) ) {
			return false;
		}

		$has_cap = false;

		// Add full access caps for GV & GF
		$caps_to_check = self::maybe_add_full_access_caps( $caps_to_check );

		$user = $user_id ? get_user_by( 'id', $user_id ) : wp_get_current_user();

		// Sanity check: make sure the user exists.
		if ( ! $user || ! $user->exists() ) {
			return $has_cap;
		}

		foreach ( $caps_to_check as $cap ) {
			if ( ! is_null( $object_id ) ) {
				$has_cap = user_can( $user, $cap, $object_id );
			} else {
				$has_cap = user_can( $user, $cap );
			}

			// At the first successful response, stop checking.
			if ( $has_cap ) {
				return true;
			}
		}

		return $has_cap;
	}

	/**
	 * Add Gravity Forms and GravityView's "full access" caps when any other caps are checked against.
	 *
	 * @since 1.15

	 * @param array $caps_to_check
	 *
	 * @return array
	 */
	public static function maybe_add_full_access_caps( $caps_to_check = array() ) {

		$caps_to_check = (array) $caps_to_check;

		$all_gravityview_caps = self::all_caps();

		// Are there any $caps_to_check that are from GravityView?
		if ( $has_gravityview_caps = array_intersect( $caps_to_check, $all_gravityview_caps ) ) {
			$caps_to_check[] = 'gravityview_full_access';
		}

		$all_gravity_forms_caps = class_exists( 'GFCommon' ) ? GFCommon::all_caps() : array();

		// Are there any $caps_to_check that are from Gravity Forms?
		if ( $all_gravity_forms_caps = array_intersect( $caps_to_check, $all_gravity_forms_caps ) ) {
			$caps_to_check[] = 'gform_full_access';
		}

		return array_unique( $caps_to_check );
	}

	/**
	 * Remove all GravityView caps_to_check from all roles
	 *
	 * @since 1.15
	 * @return void
	 */
	public function remove_caps() {

		$wp_roles = $this->wp_roles();

		if ( is_object( $wp_roles ) ) {

			/** Remove all GravityView caps_to_check from all roles */
			$capabilities = self::all_caps();

			// Loop through each role and remove GV caps_to_check
			foreach ( $wp_roles->get_names() as $role_slug => $role_name ) {
				foreach ( $capabilities as $cap ) {
					$wp_roles->remove_cap( $role_slug, $cap );
				}
			}
		}
	}
}

add_action( 'init', array( 'GravityView_Roles_Capabilities', 'get_instance' ), 1 );
