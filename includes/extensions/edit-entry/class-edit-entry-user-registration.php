<?php
/**
 * GravityView Edit Entry - Sync User Registration (when using the GF User Registration Add-on)
 *
 * @since 1.11
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * GravityView Edit Entry - Sync User Registration (when using the GF User Registration Add-on)
 */
class GravityView_Edit_Entry_User_Registration {

	/**
	 * @var GravityView_Edit_Entry
	 */
    protected $loader;

    /**
     * @var WP_User|null Temporary storage used by restore_user_details()
     */
    private $_user_before_update = null;

    function __construct( GravityView_Edit_Entry $loader ) {
        $this->loader = $loader;
    }

	/**
	 * @since 1.11
	 */
	public function load() {

        /**
	     * @filter `gravityview/edit_entry/user_registration/trigger_update` Choose whether to update user information via User Registration add-on when an entry is updated?
	     * @since 1.11
	     * @param boolean $boolean Whether to trigger update on user registration (default: true)
	     */
        if( apply_filters( 'gravityview/edit_entry/user_registration/trigger_update', true ) ) {
            add_action( 'gravityview/edit_entry/after_update' , array( $this, 'update_user' ), 10, 2 );

            add_action( 'gform_user_updated', array( $this, 'restore_user_details' ), 10, 4 );
        }
    }

    /**
     * Update the WordPress user profile based on the GF User Registration create feed
     *
     * @since 1.11
     *
     * @param array $form Gravity Forms form array
     * @param string $entry_id Gravity Forms entry ID
     * @return void
     */
    public function update_user( $form = array(), $entry_id = 0 ) {

        if( !class_exists( 'GFAPI' ) || !class_exists( 'GFUser' ) || empty( $entry_id ) ) {
            return;
        }

        $entry = GFAPI::get_entry( $entry_id );

	    /**
	     * @filter `gravityview/edit_entry/user_registration/entry` Modify entry details before updating the user via User Registration add-on
	     * @since 1.11
	     * @param array $entry Gravity Forms entry
	     * @param array $form Gravity Forms form
	     */
        $entry = apply_filters( 'gravityview/edit_entry/user_registration/entry', $entry, $form );

        /**
         * @since 1.14
         */
        $config = GFUser::get_active_config( $form, $entry );

        /**
         * @filter `gravityview/edit_entry/user_registration/config` Modify the User Registration Addon feed configuration
         * @since 1.14
         * @param[in,out] array $config Gravity Forms User Registration feed configuration for the form
         * @param[in] array $form Gravity Forms form array
         * @param[in] array $entry Gravity Forms entry being edited
         */
        $config = apply_filters( 'gravityview/edit_entry/user_registration/config', $config, $form, $entry );

        $this->_user_before_update = get_userdata( $entry['created_by'] );

        // The priority is set to 3 so that default priority (10) will still override it
        add_filter( 'send_password_change_email', '__return_false', 3 );
        add_filter( 'send_email_change_email', '__return_false', 3 );

        // Trigger the User Registration update user method
        GFUser::update_user( $entry, $form, $config );

        remove_filter( 'send_password_change_email', '__return_false', 3 );
        remove_filter( 'send_email_change_email', '__return_false', 3 );
    }

    /**
     * Restore the Display Name and roles of a user after being updated by Gravity Forms User Registration Addon
     *
     * @see GFUser::update_user()
     * @param int $user_id WP User ID that was updated by Gravity Forms User Registration Addon
     * @param array $config Gravity Forms User Registration Addon form feed configuration
     * @param array $entry The Gravity Forms entry that was just updated
     * @return void
     */
    public function restore_user_details( $user_id = 0, $config = array(), $entry = array() ) {

        $user_after_update = get_userdata( $user_id );

        $restored_user = $user_after_update;

	    // Restore previous display_name
        $restored_user->display_name = $this->_user_before_update->display_name;

        // Restore previous roles
        $restored_user->roles = array();
        foreach( $this->_user_before_update->roles as $role ) {
            $restored_user->add_role( $role );
        }

	    // Don't have WP update the password.
	    unset( $restored_user->data->user_pass );

        /**
         * Modify the user data after updated by Gravity Forms User Registration but before restored by GravityView
         * @since 1.14
         * @param WP_User $restored_user The user with restored details about to be updated by wp_update_user()
         * @param WP_User $user_before_update The user before being updated by Gravity Forms User Registration
         * @param WP_User $user_after_update The user after being updated by Gravity Forms User Registration
         * @param array   $entry The Gravity Forms entry that was just updated
         */
        $restored_user = apply_filters( 'gravityview/edit_entry/user_registration/restored_user', $restored_user, $this->_user_before_update, $user_after_update, $entry );

        $updated = wp_update_user( $restored_user );

        if( is_wp_error( $updated ) ) {
            do_action('gravityview_log_error', __METHOD__ . sprintf( ' - There was an error updating user #%d details', $user_id ), $updated );
        } else {
            do_action('gravityview_log_debug', __METHOD__ . sprintf( ' - User #%d details restored', $user_id ) );
        }

        $this->_user_before_update = null;
        unset( $updated, $restored_user, $user_after_update );
    }

} //end class
