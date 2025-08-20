<?php
/**
 * The GravityView Edit Entry Extension
 *
 * Easily edit entries in GravityView.
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}


class GravityView_Edit_Entry {
    /**
     * @var string
     */
	static $file;

	static $instance;

    /**
     * Component instances.
     *
     * @var array
     */
    public $instances = array();


	function __construct() {

        self::$file = plugin_dir_path( __FILE__ );

        if ( is_admin() ) {
            $this->load_components( 'admin' );
        }

		$this->load_components( 'locking' );

        $this->load_components( 'render' );

        // If GF User Registration Add-on exists
        $this->load_components( 'user-registration' );

        $this->add_hooks();

		// Process hooks for addons that may or may not be present
		$this->addon_specific_hooks();
	}


    static function getInstance() {

        if ( empty( self::$instance ) ) {
            self::$instance = new GravityView_Edit_Entry();
        }

        return self::$instance;
    }


    private function load_components( $component ) {

        $dir = trailingslashit( self::$file );

        $filename  = $dir . 'class-edit-entry-' . $component . '.php';
        $classname = 'GravityView_Edit_Entry_' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $component ) ) );

        // Loads component and pass extension's instance so that component can
        // talk each other.
        require_once $filename;
        $this->instances[ $component ] = new $classname( $this );
        $this->instances[ $component ]->load();
    }

    private function add_hooks() {

        // Add front-end access to Gravity Forms delete file action
        add_action( 'wp_ajax_nopriv_rg_delete_file', array( $this, 'delete_file' ) );

        // Make sure this hook is run for non-admins
        add_action( 'wp_ajax_rg_delete_file', array( $this, 'delete_file' ) );

        add_filter( 'gravityview_blocklist_field_types', array( $this, 'modify_field_blocklist' ), 10, 2 );

        // add template path to check for field
        add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

		add_filter( 'gravityview/field/is_visible', array( $this, 'maybe_not_visible' ), 10, 3 );

		add_filter( 'gravityview/api/reserved_query_args', array( $this, 'add_reserved_arg' ) );

		add_filter( 'gform_notification_events', array( $this, 'add_edit_notification_events' ), 10, 2 );

		add_action( 'gravityview/edit_entry/after_update', array( $this, 'trigger_notifications' ), 10, 3 );

    }

	/**
	 * Adds "edit" to the list of internal reserved query args
	 *
	 * @since 2.10
	 *
	 * @param array $args Existing reserved args
	 *
	 * @return array
	 */
	public function add_reserved_arg( $args ) {

		$args[] = 'edit';

		return $args;
	}

	/**
	 * Trigger hooks that are normally run in the admin for Addons, but need to be triggered manually because we're not in the admin
     *
	 * @return void
	 */
	private function addon_specific_hooks() {

		if ( class_exists( 'GFSignature' ) && is_callable( array( 'GFSignature', 'get_instance' ) ) ) {
			add_filter( 'gform_admin_pre_render', array( GFSignature::get_instance(), 'edit_lead_script' ) );
		}
	}

	/**
	 * Hide the field or not.
	 *
	 * For non-logged in users.
	 * For users that have no edit rights on any of the current entries.
	 *
	 * @param bool      $visible Visible or not.
	 * @param \GV\Field $field The field.
	 * @param \GV\View  $view The View context.
	 *
	 * @return bool
	 */
	public function maybe_not_visible( $visible, $field, $view ) {

		if ( 'edit_link' !== $field->ID ) {
			return $visible;
		}

		if ( ! $view instanceof \GV\View ) {
			return $visible;
		}

		// We're already on the edit entry page, so we don't need to show the field.
		if ( gravityview()->request->is_edit_entry() ) {
			return false;
		}

		static $visibility_cache_for_view = [];

		$anchor_id = $view->get_anchor_id();

		$result = \GV\Utils::get( $visibility_cache_for_view, $anchor_id, null );
		if ( ! is_null( $result ) ) {
			return $result;
		}

		foreach ( $view->get_entries()->all() as $entry ) {
			if ( self::check_user_cap_edit_entry( $entry->as_entry(), $view ) ) {
				// At least one entry is deletable for this user
				$visibility_cache_for_view[ $anchor_id ] = true;
				return true;
			}
		}

		$visibility_cache_for_view[ $anchor_id ] = false;

		return false;
	}

    /**
     * Include this extension templates path
     *
     * @param array $file_paths List of template paths ordered
     */
    public function add_template_path( $file_paths ) {

        // Index 100 is the default GravityView template path.
        $file_paths[110] = self::$file;

        return $file_paths;
    }

    /**
     *
     * Return a well formatted nonce key according to GravityView Edit Entry protocol
     *
     * @param $view_id int GravityView view id
     * @param $form_id int Gravity Forms form id
     * @param $entry_id int Gravity Forms entry id
     * @return string
     */
    public static function get_nonce_key( $view_id, $form_id, $entry_id ) {
        return sprintf( 'edit_%d_%d_%d', $view_id, $form_id, $entry_id );
    }


    /**
     * The edit entry link creates a secure link with a nonce
     *
     * It also mimics the URL structure Gravity Forms expects to have so that
     * it formats the display of the edit form like it does in the backend, like
     * "You can edit this post from the post page" fields, for example.
     *
     * @param $entry array Gravity Forms entry object
     * @param $view_id int GravityView view id
     * @param $post_id int GravityView Post ID where View may be embedded {@since 1.9.2}
     * @param string|array                                                         $field_values Parameters to pass in to the Edit Entry form to prefill data. Uses the same format as Gravity Forms "Allow field to be populated dynamically" {@since 1.9.2} {@see https://www.gravityhelp.com/documentation/article/allow-field-to-be-populated-dynamically/ }
     * @return string
     */
    public static function get_edit_link( $entry, $view_id, $post_id = null, $field_values = '' ) {

        $nonce_key = self::get_nonce_key( $view_id, $entry['form_id'], $entry['id'] );

        $base = gv_entry_link( $entry, $post_id ? : $view_id );

        $url = add_query_arg(
            array(
				'edit' => wp_create_nonce( $nonce_key ),
            ),
            $base
        );

	    if ( $post_id ) {
		    $url = add_query_arg( array( 'gvid' => $view_id ), $url );
	    }

	    /**
	     * Allow passing params to dynamically populate entry with values.
	     *
	     * @since 1.9.2
	     */
	    if ( ! empty( $field_values ) ) {

		    if ( is_array( $field_values ) ) {
			    // If already an array, no parse_str() needed
			    $params = $field_values;
		    } else {
			    parse_str( $field_values, $params );
		    }

		    $url = add_query_arg( $params, $url );
	    }

	    /**
	     * Filter the edit URL link.
	     *
	     * @since  2.14.6 Added $post param.
	     *
	     * @param string   $url   The url.
	     * @param array    $entry The entry.
	     * @param \GV\View $view  The View.
	     * @param WP_Post|null WP_Post $post WP post.
	     */
	    return apply_filters( 'gravityview/edit/link', $url, $entry, \GV\View::by_id( $view_id ), get_post( $view_id ) );
    }

	/**
	 * @depecated 2.14 Use {@see GravityView_Edit_Entry::modify_field_blocklist()}
	 *
	 * @param  array       $fields  Existing blocklist fields
	 * @param  string|null $context Context
	 *
	 * @return array          If not edit context, original field blocklist. Otherwise, blocklist including post fields.
	 */
	public function modify_field_blacklist( $fields = array(), $context = null ) {
		_deprecated_function( __METHOD__, '2.14', 'GravityView_Edit_Entry::modify_field_blocklist()' );
		return $this->modify_field_blocklist( $fields, $context );
	}

	/**
	 * Edit mode doesn't allow certain field types.
	 *
	 * @since 2.14
	 *
	 * @param  array       $fields  Existing blocklist fields
	 * @param  string|null $context Context
	 *
	 * @return array          If not edit context, original field blocklist. Otherwise, blocklist including post fields.
	 */
	public function modify_field_blocklist( $fields = array(), $context = null ) {

		if ( empty( $context ) || 'edit' !== $context ) {
			return $fields;
		}

		$add_fields = $this->get_field_blocklist();

		return array_merge( $fields, $add_fields );
	}

	/**
	 * @depecated 2.14 Use {@see GravityView_Edit_Entry::get_field_blocklist()}
	 *
	 * @since 1.20
	 *
	 * @param array $entry Gravity Forms entry array
	 *
	 * @return array Blocklist of field types
	 */
	public function get_field_blacklist( $entry = array() ) {
		_deprecated_function( __METHOD__, '2.14', 'GravityView_Edit_Entry::get_field_blocklist()' );
		return $this->get_field_blocklist( $entry );
	}

	/**
	 * Returns array of field types that should not be displayed in Edit Entry
	 *
	 * @since 2.14
	 *
	 * @param array $entry Gravity Forms entry array
	 *
	 * @return array Blocklist of field types
	 */
	function get_field_blocklist( $entry = array() ) {

		$fields = array(
			'page',
			'payment_status',
			'payment_date',
			'payment_amount',
			'is_fulfilled',
			'transaction_id',
			'transaction_type',
			'captcha',
			'honeypot',
			'creditcard',
		);

		/**
		 * @depecated 2.14
		 */
		$fields = apply_filters_deprecated( 'gravityview/edit_entry/field_blacklist', array( $fields, $entry ), '2.14', 'gravityview/edit_entry/field_blocklist' );

		/**
		 * Array of fields that should not be displayed in Edit Entry.
		 *
		 * @since 1.20
		 * @param string[] $fields Array of field type or meta key names (eg: `[ "captcha", "payment_status" ]` ).
		 * @param array $entry Gravity Forms entry array.
		 */
		$fields = apply_filters( 'gravityview/edit_entry/field_blocklist', $fields, $entry );

		return $fields;
	}


    /**
     * checks if user has permissions to edit a specific entry
     *
     * Needs to be used combined with GravityView_Edit_Entry_Render::user_can_edit_entry for maximum security!!
     *
     * @param  array|\WP_Error $entry Gravity Forms entry array or WP_Error if the entry wasn't found.
     * @param \GV\View|int    $view ID of the view you want to check visibility against {@since 1.9.2}. Required since 2.0.
     * @return bool
     */
    public static function check_user_cap_edit_entry( $entry, $view = 0 ) {

        // No permission by default
        $user_can_edit = false;

		// get user_edit setting
		if ( empty( $view ) ) {
			// @deprecated path
			$view_id   = GravityView_View::getInstance()->getViewId();
			$user_edit = GravityView_View::getInstance()->getAtts( 'user_edit' );
		} elseif ( $view instanceof \GV\View ) {
			$view_id   = $view->ID;
			$user_edit = $view->settings->get( 'user_edit' );
		} else {
			$view_id   = $view;
			$user_edit = GVCommon::get_template_setting( $view_id, 'user_edit' );
		}

		// If the entry doesn't exist, they can't edit it, can they?
	    if ( ! $entry || is_wp_error( $entry ) ) {

		    gravityview()->log->error( 'Entry doesn\'t exist.' );

		    $user_can_edit = false;

	    }

	    // If they can edit any entries (as defined in Gravity Forms) or if they can edit other people's entries, then we're good.
		elseif ( GVCommon::has_cap( array( 'gravityforms_edit_entries', 'gravityview_edit_others_entries' ), $entry['id'] ) ) {

		    gravityview()->log->debug( 'User has ability to edit all entries.' );

		    $user_can_edit = true;

	    } elseif ( ! isset( $entry['created_by'] ) ) {

		    gravityview()->log->error( 'Entry `created_by` doesn\'t exist.' );

		    $user_can_edit = false;

	    } else {

            $current_user = wp_get_current_user();

            // User edit is disabled
            if ( $view_id && empty( $user_edit ) ) {

                gravityview()->log->debug( 'User Edit is disabled. Returning false.' );

                $user_can_edit = false;
            }

            // User edit is enabled and the logged-in user is the same as the user who created the entry. We're good.
            elseif ( is_user_logged_in() && intval( $current_user->ID ) === intval( $entry['created_by'] ) ) {

                gravityview()->log->debug( 'User {user_id} created the entry.', array( 'user_id', $current_user->ID ) );

                $user_can_edit = true;

            } elseif ( ! is_user_logged_in() ) {

                gravityview()->log->debug( 'No user defined; edit entry requires logged in user' );

	            /** @noinspection PhpConditionAlreadyCheckedInspection */
	            $user_can_edit = false; // Here just for clarity
            }
		}

		/**
		 * Modify whether user can edit an entry.
		 *
		 * @since 1.15 Added `$entry` and `$view_id` parameters
		 *
		 * @param boolean $user_can_edit Can the current user edit the current entry? (Default: false)
		 * @param array|\WP_Error $entry Gravity Forms entry array {@since 1.15}
		 * @param int $view_id ID of the view you want to check visibility against {@since 1.15}
		 */
		$user_can_edit = apply_filters( 'gravityview/edit_entry/user_can_edit_entry', $user_can_edit, $entry, $view_id );

		return (bool) $user_can_edit;
    }

	/**
	 * Deletes a file.
	 *
	 * @since  2.14.4
	 *
	 * @uses   GFForms::delete_file()
	 */
	public function delete_file() {
		add_filter(
            'user_has_cap',
            function ( $caps ) {
				$caps['gravityforms_delete_entries'] = true;

				return $caps;
			}
        );

		GFForms::delete_file();
	}

	/**
	 * Triggers notifications.
	 *
	 * @since 2.32.0
	 *
	 * @param array                         $form              The form object.
	 * @param int                           $entry_id          The entry ID.
	 * @param GravityView_Edit_Entry_Render $edit_entry_render The edit entry render class instance.
	 */
	public function trigger_notifications( $form, $entry_id, $edit_entry_render ) {
		GravityView_Notifications::send_notifications( (int) $entry_id, 'gravityview/edit_entry/after_update', $edit_entry_render->entry );
	}

	/**
	 * Adds the notification event.
	 *
	 * @since 2.32.0
	 *
	 * @param array $notification_events Existing notification events.
	 * @param array $form                The form object.
	 *
	 * @return array
	 */
	public function add_edit_notification_events( $notification_events = [], $form = [] ) {
		$notification_events['gravityview/edit_entry/after_update'] = 'GravityView - ' . esc_html_x( 'Entry is updated', 'The title for an event in a notifications drop down list.', 'gk-gravityview' );

		return $notification_events;
	}
}

GravityView_Edit_Entry::getInstance();
