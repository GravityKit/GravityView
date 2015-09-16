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

        $config = $this->maybe_prevent_user_role_reset( $config, $form, $entry );

        /**
         * @filter `gravityview/edit_entry/user_registration/config` Modify the User Registration Addon feed configuration
         * @since 1.14
         * @param[in,out] array $config Gravity Forms User Registration feed configuration for the form
         * @param[in] array $form Gravity Forms form array
         * @param[in] array $entry Gravity Forms entry being edited
         */
        $config = apply_filters( 'gravityview/edit_entry/user_registration/config', $config, $form, $entry );

        // Trigger the User Registration update user method
        GFUser::update_user( $entry, $form, $config );

    }

    /**
     * Prevent GF User Registration Addon from resetting the user role to the feed default
     *
     * @since 1.14
     *
     * @param array $config Gravity Forms User Registration feed configuration for the form
     * @param array $form Gravity Forms form array
     * @param array $entry Gravity Forms entry being edited
     *
     * @return array Modified $config array, if $reset_role is enabled
     */
    private function maybe_prevent_user_role_reset( $config, $form, $entry ) {

        /**
         * @filter `gravityview/edit_entry/user_registration/reset_role` Whether to reset the role to original role specified in the Gravity Forms User Registration Addon feed
         * By default, Gravity Forms will reset the role to the role specified in the Feed configuration. We disable that by default.
         * @since 1.14
         * @param[in,out] boolean $reset_role Whether to reset the role. Default: `false`
         * @param[in] array $config Gravity Forms User Registration feed configuration for the form
         * @param[in] array $form Gravity Forms form array
         * @param[in] array $entry Gravity Forms entry being edited
         */
        $reset_role = apply_filters( 'gravityview/edit_entry/user_registration/reset_role', false, $config, $form, $entry );

        // GF checks for a `role` setting in the feed meta. By unsetting that, we prevent them from updating the role.
        if( false === $reset_role ) {
            unset( $config['meta']['role'] );
        }

        return $config;
    }

} //end class
