<?php
/**
 * Roles and Capabilities
 *
 * @package     GravityView
 * @license     GPL2+
 * @since       1.14
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
 * @since 1.15
 */
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

		if( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Get things going
	 *
	 * @since 1.15
	 */
	public function __construct() {
		self::has_cap( 'edit_gravityview', 1892 );
		$this->add_hooks();
	}

	/**
	 * Add Members plugin hook
	 * @since 1.15
	 */
	private function add_hooks() {
		add_filter( 'members_get_capabilities', array( $this, 'members_get_capabilities' ) );
		add_action( 'members_register_cap_groups', array( $this, 'members_register_cap_group' ), 20 );
		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 10, 3 );
	}

	/**
	 * Add support for `gravityview_full_access` capability, and
	 *
	 * @see map_meta_cap()
	 *
	 * @since 1.15
	 *
	 * @param $allcaps
	 * @param $caps
	 * @param $args
	 *
	 * @return mixed
	 */
	public function filter_user_has_cap( $allcaps = array(), $caps = array(), $args = array() ) {

		// Empty caps array
		if( ! $allcaps ) {
			return $allcaps;
		}

		/**
		 * Enable all GravityView caps if `gravityview_full_access` is enabled
		 */
		if( ! empty( $allcaps['gravityview_full_access'] ) ) {

			$all_gravityview_caps = self::all_caps('all');

			foreach( $all_gravityview_caps as $gv_cap ) {
				$allcaps[ $gv_cap ] = true;
			}

			unset( $all_gravityview_caps );
		}

		if( ! empty( $caps[0] ) ) {

			/**
			 * WordPress doesn't add "read_{post_type}" capabilities by default, so we check our permissions here.
			 * So: are we asking whether a user can read (see) a View?
			 */
			if ( 'read_gravityview' === $args[0] ) {
				/**
				 * @var string $meta_cap The specific capability requested, in this case "read_gravityview"
				 * @var int $user_id User ID to check cap for
				 * @var int $view_id Post ID to check capability against, if provided
				 */
				list( $meta_cap, $user_id, $view_id ) = array_pad( $args, 3, null );

				$allcaps[ $cap ] = ! empty( $allcaps[ $meta_cap ] );
			}

		}

		return $allcaps;
	}

	/**
	 * Add GravityView group to Members 1.x plugin management screen
	 * @see members_register_cap_group()
	 * @since 1.15
	 * @return void
	 */
	function members_register_cap_group() {
		if ( function_exists( 'members_register_cap_group' ) ) {
			$args = array(
				'label'       => __( 'GravityView' ),
				'icon'        => 'gv-icon-astronaut-head',
				'caps'        => self::all_caps( 'all' ),
				'merge_added' => true,
				'diff_added'  => false,
			);
			members_register_cap_group( 'gravityview', $args );
		}
	}

	/**
	 * Add GravityView capabilities to the Members plugin
	 *
	 * @since 1.15
	 * @param array $caps Existing capabilities registered with Members
	 * @return array Modified capabilities array
	 */
	public function members_get_capabilities( $caps ) {
		return array_merge( $caps, self::all_caps('all') );
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
	 * Add capabilities to their respective roles
	 *
	 * @since 1.15
	 * @return void
	 */
	public function add_caps() {

		$wp_roles = $this->wp_roles();

		if ( is_object( $wp_roles ) ) {

			foreach( $wp_roles->get_names() as $role_slug => $role_label ) {

				$capabilities = self::all_caps( $role_slug );

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
	 * @since 1.15
	 *
	 * @param string $role If set, get the caps for a specific role. Pass 'all' to get all caps in a flat array. Default: ''
	 *
	 * @return array If $role is set, flat array of caps. Otherwise, a multi-dimensional array of roles and their caps with the following keys: 'administrator', 'editor', 'author', 'contributor', 'subscriber'
	 */
	public static function all_caps( $role = '' ) {

		// Change settings
		$administrator = array(
			'gravityview_full_access', // Grant access to all caps
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
			'gravityview_contact_support',

			'copy_gravityviews', // For duplicate/clone View functionality

			// GF caps
			'gravityview_edit_others_entries',
			'gravityview_moderate_entries',
			'gravityview_delete_others_entries',
			'gravityview_view_others_entry_notes',
		);

		// Edit, publish and delete own stuff
		$author = array(
			// GF caps
			'gravityview_edit_entries',
			'gravityview_edit_form_entries', // This is similar to `gravityview_edit_entries`, but checks against a Form ID $object_id
			'gravityview_view_entry_notes',
			'gravityview_delete_entries',
			'gravityview_delete_entry',
		);

		// Edit and delete drafts but not publish
		$contributor = array(
			'edit_gravityview',
			'edit_gravityviews', // Affects if you're able to see the Views menu in the Admin
			'delete_gravityview',
			'delete_gravityviews',
			
			'gravityview_support_port', // Display GravityView Help beacon
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

		// If role is set, return caps for just that role.
		if( $role ) {
			return $capabilities;
		}

		// By default, return multi-dimensional array of all caps
		return compact( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
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
	 * @param string|array $caps Single capability or array of capabilities
	 * @param int $object_id (optional) Parameter can be used to check for capabilities against a specific object, such as a post or user
	 *
	 * @return bool True: user has at least one passed capability; False: user does not have any defined capabilities
	 */
	public static function has_cap( $caps = '', $object_id = NULL ) {

		if( empty( $caps ) ) {
			return false;
		}

		// Convert string to array
		$caps = (array)$caps;

		$all_gravityview_caps = self::all_caps( 'all' );

		// Are there any $caps that are from GravityView?
		if( $has_gravityview_caps = array_intersect( $caps, $all_gravityview_caps ) ) {
			$caps[] = 'gravityview_full_access';
		}

		$all_gravity_forms_caps = class_exists( 'GFCommon' ) ? GFCommon::all_caps() : array();

		// Are there any $caps that are from Gravity Forms?
		if( $all_gravity_forms_caps = array_intersect( $caps, $all_gravity_forms_caps ) ) {
			$caps[] = 'gform_full_access';
		}

		foreach ( $caps as $cap ) {
			if( $has_cap = current_user_can( $cap, $object_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove all GravityView caps from all roles
	 *
	 * @since 1.15
	 * @return void
	 */
	public function remove_caps() {

		$wp_roles = $this->wp_roles();

		if ( is_object( $wp_roles ) ) {

			/** Remove all GravityView caps from all roles */
			$capabilities = self::all_caps('all');

			// Loop through each role and remove GV caps
			foreach( $wp_roles->get_names() as $role_slug => $role_name ) {
				foreach ( $capabilities as $cap ) {
					$wp_roles->remove_cap( $role_slug, $cap );
				}
			}
		}
	}
}

add_action( 'init', array( 'GravityView_Roles_Capabilities', 'get_instance' ), 1 );