<?php
/**
 * GravityView Edit Entry - Sync User Registration (when using the GF User Registration Add-on)
 *
 * @since 1.11
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
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
	 * @var GravityView_Edit_Entry $loader
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
		add_action( 'wp', array( $this, 'add_hooks' ), 10 );
    }

	/**
	 * Add hooks to trigger updating the user
	 *
	 * @since 1.18
	 */
    public function add_hooks() {

	    /**
	     * Choose whether to update user information via User Registration add-on when an entry is updated.
	     *
	     * @since 1.11
	     *
	     * @param bool $trigger_update Whether to trigger update on user registration. Default: true.
	     */
	    if ( apply_filters( 'gravityview/edit_entry/user_registration/trigger_update', true ) ) {

	    	add_action( 'gravityview/edit_entry/after_update', array( $this, 'update_user' ), 10, 2 );

		    // last resort in case the current user display name don't match any of the defaults
		    add_action( 'gform_user_updated', array( $this, 'restore_display_name' ), 10, 4 );
	    }
    }

    /**
     * Update the WordPress user profile based on the GF User Registration create feed
     *
     * @since 1.11
     *
     * @param array  $form Gravity Forms form array
     * @param string $entry_id Gravity Forms entry ID
     * @return void
     */
    public function update_user( $form = array(), $entry_id = 0 ) {

		// Only proceed if the registration class exists and is active.
	    if ( ! class_exists( 'GF_User_Registration' ) ) {
		    return;
	    }

        if ( ! class_exists( 'GFAPI' ) ) {
	        gravityview()->log->error( 'GFAPI class not found; not updating the user' );
	        return;
        }

		if ( empty( $entry_id ) ) {
			gravityview()->log->error( 'Entry ID is empty [{entry_id}]; not updating the user', array( 'entry_id' => $entry_id ) );
			return;
		}

        $gf_user_registration = GF_User_Registration::get_instance();

        $entry = GFAPI::get_entry( $entry_id );

	    /**
	     * Modify entry details before updating the user via User Registration add-on.
	     *
	     * @since 1.11
	     *
	     * @param array $entry Gravity Forms entry.
	     * @param array $form Gravity Forms form.
	     */
	    $entry = apply_filters( 'gravityview/edit_entry/user_registration/entry', $entry, $form );

	    $config = $this->get_feed_configuration( $entry, $form );

        // Make sure the feed is active
	    if ( ! \GV\Utils::get( $config, 'is_active', false ) ) {
			return;
	    }

	    // If an Update feed, make sure the conditions are met.
	    if ( 'update' === \GV\Utils::get( $config, 'meta/feedType' ) ) {
	    	if ( ! $gf_user_registration->is_feed_condition_met( $config, $form, $entry ) ) {
			    return;
		    }
	    }

	    // Do not update user if the user hasn't been registered (happens when manual activation is enabled in User Registration feed)
	    $username = \GV\Utils::get( $config, 'meta/username', null );
	    if ( ! isset( $entry[ $username ] ) || ! get_user_by( 'login', $entry[ $username ] ) ) {
		    return;
	    }

        // The priority is set to 3 so that default priority (10) will still override it
        add_filter( 'send_password_change_email', '__return_false', 3 );
        add_filter( 'send_email_change_email', '__return_false', 3 );

        // Trigger the User Registration update user method
        $gf_user_registration->update_user( $entry, $form, $config );

        remove_filter( 'send_password_change_email', '__return_false', 3 );
        remove_filter( 'send_email_change_email', '__return_false', 3 );

        // Prevent double-triggering by removing the hook
	    remove_action( 'gravityview/edit_entry/after_update', array( $this, 'update_user' ), 10 );
    }

	/**
	 * Get the User Registration feed configuration for the entry & form
	 *
	 * @uses GF_User_Registration::get_single_submission_feed
	 * @uses GravityView_Edit_Entry_User_Registration::match_current_display_name
	 *
	 * @since 1.20
	 *
	 * @param $entry
	 * @param $form
	 *
	 * @return array
	 */
    public function get_feed_configuration( $entry, $form ) {

	    /** @var \GF_User_Registration $gf_user_registration */
	    $gf_user_registration = GF_User_Registration::get_instance();

	    $config = $gf_user_registration->get_single_submission_feed( $entry, $form );

	    // Return empty array if no feed found, but still allow filtering.
	    if ( empty( $config ) ) {
		    // Filter is documented below.
		    return apply_filters( 'gravityview/edit_entry/user_registration/config', [], $form, $entry );
	    }

	    /**
	     * Keep the current user role or override with the role defined in the Create feed.
	     *
	     * @since 1.15
	     *
	     * @param bool  $preserve_role Preserve current user role. Default: true.
	     * @param array $config        Gravity Forms User Registration feed configuration for the form.
	     * @param array $form          Gravity Forms form array.
	     * @param array $entry         Gravity Forms entry being edited.
	     */
	    $preserve_role = apply_filters( 'gravityview/edit_entry/user_registration/preserve_role', true, $config, $form, $entry );

	    if ( $preserve_role ) {
		    $config['meta']['role'] = 'gfur_preserve_role';
	    }

	    $displayname = $this->match_current_display_name( $entry['created_by'] );

	    /**
	     * Make sure the current display name is not changed with the update user method.
	     *
	     * @since 1.15
	     */
	    $config['meta']['displayname'] = $displayname ? $displayname : $config['meta']['displayname'];

	    /**
	     * Modify the User Registration Addon feed configuration.
	     *
	     * @since 1.14
	     *
	     * @param array $config Gravity Forms User Registration feed configuration for the form.
	     * @param array $form   Gravity Forms form array.
	     * @param array $entry  Gravity Forms entry being edited.
	     */
	    $config = apply_filters( 'gravityview/edit_entry/user_registration/config', $config, $form, $entry );

	    return $config;
    }

    /**
     * Calculate the user display name format
     *
     * @since 1.15
     * @since 1.20 Returns false if user not found at $user_id
     *
     * @param int $user_id WP User ID
     * @return false|string Display name format as used inside Gravity Forms User Registration. Returns false if user not found.
     */
    public function match_current_display_name( $user_id ) {

	    $user = get_userdata( $user_id );

	    if ( ! $user ) {
		    return false;
	    }

	    $names = $this->generate_display_names( $user );

	    $format = array_search( $user->display_name, $names, true );

	    /**
	     * In case we can't find the current display name format, trigger last resort method at the 'gform_user_updated' hook
	     *
	     * @see restore_display_name
	     */
	    if ( false === $format ) {
		    $this->_user_before_update = $user;
	    }

	    return $format;
    }

    /**
     * Generate an array of all the user display names possibilities
     *
     * @since 1.15
     *
     * @param object $profileuser WP_User object
     * @return array List all the possible display names for a certain User object
     */
    public function generate_display_names( $profileuser ) {

        $public_display             = array();
        $public_display['nickname'] = $profileuser->nickname;
        $public_display['username'] = $profileuser->user_login;

        if ( ! empty( $profileuser->first_name ) ) {
	        $public_display['firstname'] = $profileuser->first_name;
        }

        if ( ! empty( $profileuser->last_name ) ) {
	        $public_display['lastname'] = $profileuser->last_name;
        }

        if ( ! empty( $profileuser->first_name ) && ! empty( $profileuser->last_name ) ) {
            $public_display['firstlast'] = $profileuser->first_name . ' ' . $profileuser->last_name;
            $public_display['lastfirst'] = $profileuser->last_name . ' ' . $profileuser->first_name;
        }

        $public_display = array_map( 'trim', $public_display );
        $public_display = array_unique( $public_display );

        return $public_display;
    }


    /**
     * Restore the Display Name and roles of a user after being updated by Gravity Forms User Registration Addon
     *
     * @see GFUser::update_user()
     * @param int    $user_id WP User ID that was updated by Gravity Forms User Registration Addon
     * @param array  $config Gravity Forms User Registration Addon form feed configuration
     * @param array  $entry The Gravity Forms entry that was just updated
     * @param string $password User password
     * @return int|false|WP_Error|null True: User updated; False: $user_id not a valid User ID; WP_Error: User update error; Null: Method didn't process
     */
    public function restore_display_name( $user_id = 0, $config = array(), $entry = array(), $password = '' ) {

        /**
		 * Whether display names should be restored to before updating an entry.
		 *
		 * Otherwise, display names will be reset to the format specified in Gravity Forms User Registration "Update" feed.
		 *
		 * @since 1.14.4
		 *
		 * @param bool $restore_display_name Restore Display Name? Default: true.
		 */
        $restore_display_name = apply_filters( 'gravityview/edit_entry/restore_display_name', true );

        $is_update_feed = ( $config && 'update' === \GV\Utils::get( $config, 'meta/feed_type' ) );

	    /**
	     * Don't restore display name:
	     *   - either disabled,
	     *   - or it is an Update feed (we only care about Create feed)
	     *   - or we don't need as we found the correct format before updating user.
	     *
	     * @since 1.14.4
	     */
	    if ( ! $restore_display_name || $is_update_feed || is_null( $this->_user_before_update ) ) {
		    return null;
	    }

        $user_after_update = get_userdata( $user_id );

        // User not found
	    if ( ! $user_after_update ) {
	    	gravityview()->log->error( 'User not found at $user_id #{user_id}', array( 'user_id' => $user_id ) );
		    return false;
	    }

        $restored_user = $user_after_update;

	    // Restore previous display_name
        $restored_user->display_name = $this->_user_before_update->display_name;

	    // Don't have WP update the password.
	    unset( $restored_user->data->user_pass, $restored_user->user_pass );

        /**
		 * Modify the user data after updated by Gravity Forms User Registration but before restored by GravityView.
		 *
		 * @since 1.14
		 *
		 * @param WP_User $restored_user      The user with restored details about to be updated by wp_update_user().
		 * @param WP_User $user_before_update The user before being updated by Gravity Forms User Registration.
		 * @param WP_User $user_after_update  The user after being updated by Gravity Forms User Registration.
		 * @param array   $entry              The Gravity Forms entry that was just updated.
		 */
        $restored_user = apply_filters( 'gravityview/edit_entry/user_registration/restored_user', $restored_user, $this->_user_before_update, $user_after_update, $entry );

        $updated = wp_update_user( $restored_user );

        if ( is_wp_error( $updated ) ) {
            gravityview()->log->error(
                'There was an error updating user #{user_id} details',
                array(
					'user_id' => $user_id,
					'data'    => $updated,
                )
            );
        } else {
            gravityview()->log->debug( 'User #{user_id} details restored', array( 'user_id' => $user_id ) );
        }

        $this->_user_before_update = null;

        unset( $restored_user, $user_after_update );

        return $updated;
    }
}//end class
