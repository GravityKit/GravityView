<?php
/**
 * The GravityView Edit Entry Extension
 *
 * Easily edit entries in GravityView.
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
 * @link      http://gravityview.co
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
     * @var array
     */
    public $instances = array();


	function __construct() {

        self::$file = plugin_dir_path( __FILE__ );

        if( is_admin() ) {
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

        if( empty( self::$instance ) ) {
            self::$instance = new GravityView_Edit_Entry;
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
        add_action( 'wp_ajax_nopriv_rg_delete_file', array( 'GFForms', 'delete_file') );

        // Make sure this hook is run for non-admins
        add_action( 'wp_ajax_rg_delete_file', array( 'GFForms', 'delete_file') );

        add_filter( 'gravityview_blacklist_field_types', array( $this, 'modify_field_blacklist' ), 10, 2 );

        // add template path to check for field
        add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

		add_filter( 'gravityview/field/is_visible', array( $this, 'maybe_not_visible' ), 10, 3 );

		add_filter( 'gravityview/api/reserved_query_args', array( $this, 'add_reserved_arg' ) );
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
	 * @return void
	 */
	private function addon_specific_hooks() {

		if( class_exists( 'GFSignature' ) && is_callable( array( 'GFSignature', 'get_instance' ) ) ) {
			add_filter('gform_admin_pre_render', array( GFSignature::get_instance(), 'edit_lead_script'));
		}

	}

	/**
	 * Hide the field or not.
	 *
	 * For non-logged in users.
	 * For users that have no edit rights on any of the current entries.
	 *
	 * @param bool $visible Visible or not.
	 * @param \GV\Field $field The field.
	 * @param \GV\View $view The View context.
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

		static $visibility_cache_for_view = array();

		if ( ! is_null( $result = \GV\Utils::get( $visibility_cache_for_view, $view->ID, null ) ) ) {
			return $result;
		}

		foreach ( $view->get_entries()->all() as $entry ) {
			if ( self::check_user_cap_edit_entry( $entry->as_entry(), $view ) ) {
				// At least one entry is deletable for this user
				$visibility_cache_for_view[ $view->ID ] = true;
				return true;
			}
		}

		$visibility_cache_for_view[ $view->ID ] = false;

		return false;
	}

    /**
     * Include this extension templates path
     * @param array $file_paths List of template paths ordered
     */
    public function add_template_path( $file_paths ) {

        // Index 100 is the default GravityView template path.
        $file_paths[ 110 ] = self::$file;

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
     * @param string|array $field_values Parameters to pass in to the Edit Entry form to prefill data. Uses the same format as Gravity Forms "Allow field to be populated dynamically" {@since 1.9.2} {@see https://www.gravityhelp.com/documentation/article/allow-field-to-be-populated-dynamically/ }
     * @return string
     */
    public static function get_edit_link( $entry, $view_id, $post_id = null, $field_values = '' ) {

        $nonce_key = self::get_nonce_key( $view_id, $entry['form_id'], $entry['id']  );

        $base = gv_entry_link( $entry, $post_id ? : $view_id  );

        $url = add_query_arg( array(
            'edit' => wp_create_nonce( $nonce_key )
        ), $base );

        if( $post_id ) {
	        $url = add_query_arg( array( 'gvid' => $view_id ), $url );
        }

	    /**
	     * Allow passing params to dynamically populate entry with values
	     * @since 1.9.2
	     */
	    if( !empty( $field_values ) ) {

		    if( is_array( $field_values ) ) {
			    // If already an array, no parse_str() needed
			    $params = $field_values;
		    } else {
			    parse_str( $field_values, $params );
		    }

		    $url = add_query_arg( $params, $url );
	    }

		/**
		 * @filter `gravityview/edit/link` Filter the edit URL link.
		 * @param[in,out] string $url The url.
		 * @param array $entry The entry.
		 * @param \GV\View $view The View.
		 */
		return apply_filters( 'gravityview/edit/link', $url, $entry, \GV\View::by_id( $view_id  ) );
    }

	/**
	 * Edit mode doesn't allow certain field types.
	 * @param  array $fields  Existing blacklist fields
	 * @param  string|null $context Context
	 * @return array          If not edit context, original field blacklist. Otherwise, blacklist including post fields.
	 */
	public function modify_field_blacklist( $fields = array(), $context = NULL ) {

		if( empty( $context ) || $context !== 'edit' ) {
			return $fields;
		}

		$add_fields = $this->get_field_blacklist();

		return array_merge( $fields, $add_fields );
	}

	/**
	 * Returns array of field types that should not be displayed in Edit Entry
	 *
	 * @since 1.20
	 *
	 * @param array $entry Gravity Forms entry array
	 *
	 * @return array Blacklist of field types
	 */
	function get_field_blacklist( $entry = array() ) {

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
		 * @filter `gravityview/edit_entry/field_blacklist` Array of fields that should not be displayed in Edit Entry
		 * @since 1.20
		 * @param array $fields Blacklist field type array
		 * @param array $entry Gravity Forms entry array
		 */
		$fields = apply_filters( 'gravityview/edit_entry/field_blacklist', $fields, $entry );

		return $fields;
	}


    /**
     * checks if user has permissions to edit a specific entry
     *
     * Needs to be used combined with GravityView_Edit_Entry::user_can_edit_entry for maximum security!!
     *
     * @param  array $entry Gravity Forms entry array
     * @param \GV\View|int $view ID of the view you want to check visibility against {@since 1.9.2}. Required since 2.0
     * @return bool
     */
    public static function check_user_cap_edit_entry( $entry, $view = 0 ) {

        // No permission by default
        $user_can_edit = false;

		// get user_edit setting
		if ( empty( $view ) ) {
			// @deprecated path
			$view_id = GravityView_View::getInstance()->getViewId();
			$user_edit = GravityView_View::getInstance()->getAtts( 'user_edit' );
		} else {
			if ( $view instanceof \GV\View ) {
				$view_id = $view->ID;
			} else {
				$view_id = $view;
			}

			// in case is specified and not the current view
			$user_edit = GVCommon::get_template_setting( $view_id, 'user_edit' );
		}

        // If they can edit any entries (as defined in Gravity Forms)
        // Or if they can edit other people's entries
        // Then we're good.
        if( GVCommon::has_cap( array( 'gravityforms_edit_entries', 'gravityview_edit_others_entries' ), $entry['id'] ) ) {

            gravityview()->log->debug( 'User has ability to edit all entries.' );

            $user_can_edit = true;

        } else if( !isset( $entry['created_by'] ) ) {

            gravityview()->log->error( 'Entry `created_by` doesn\'t exist.');

            $user_can_edit = false;

        } else {

            $current_user = wp_get_current_user();

            // User edit is disabled
            if( empty( $user_edit ) ) {

                gravityview()->log->debug( 'User Edit is disabled. Returning false.' );

                $user_can_edit = false;
            }

            // User edit is enabled and the logged-in user is the same as the user who created the entry. We're good.
            else if( is_user_logged_in() && intval( $current_user->ID ) === intval( $entry['created_by'] ) ) {

                gravityview()->log->debug( 'User {user_id} created the entry.', array( 'user_id', $current_user->ID ) );

                $user_can_edit = true;

            } else if( ! is_user_logged_in() ) {

                gravityview()->log->debug( 'No user defined; edit entry requires logged in user' );

	            $user_can_edit = false; // Here just for clarity
            }

        }

        /**
         * @filter `gravityview/edit_entry/user_can_edit_entry` Modify whether user can edit an entry.
         * @since 1.15 Added `$entry` and `$view_id` parameters
         * @param[in,out] boolean $user_can_edit Can the current user edit the current entry? (Default: false)
         * @param[in] array $entry Gravity Forms entry array {@since 1.15}
         * @param[in] int $view_id ID of the view you want to check visibility against {@since 1.15}
         */
        $user_can_edit = apply_filters( 'gravityview/edit_entry/user_can_edit_entry', $user_can_edit, $entry, $view_id );

        return (bool) $user_can_edit;
    }



} // end class

//add_action( 'plugins_loaded', array('GravityView_Edit_Entry', 'getInstance'), 6 );
GravityView_Edit_Entry::getInstance();

