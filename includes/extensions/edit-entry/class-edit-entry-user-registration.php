<?php
/**
 * GravityView Edit Entry - Sync User Registration (when using the GF User Registration Add-on)
 *
 * @since 1.10.2
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Add [gv_edit_entry_link] shortcode
 */
class GravityView_Edit_Entry_User_Registration {

	/**
	 * @var GravityView_Edit_Entry
	 */
    protected $loader;

    function __construct( GravityView_Edit_Entry $loader ) {
        $this->loader = $loader;
    }

    function load() {
        if( apply_filters( 'gravityview/edit_entry/user_registration/trigger_update', true ) ) {
            add_action( 'gravityview/edit_entry/after_update' , array( $this, 'update_user' ), 10, 2 );
        }
    }

    /**
     *
     * Update the WordPress user profile based on the GF User Registration create feed
     *
     * @param $form array Gravity Forms form object
     * @param $entry_id string Gravity Forms entry ID
     */
    function update_user( $form, $entry_id ) {

        if( !class_exists( 'GFAPI' ) || !class_exists( 'GFUser' ) ) {
            return;
        }

        $entry = GFAPI::get_entry( $entry_id );

        $entry = apply_filters( 'gravityview/edit_entry/user_registration/entry', $entry, $form );

        // Trigger the User Registration update user method
        GFUser::update_user( $entry, $form );

    }

} //end class
