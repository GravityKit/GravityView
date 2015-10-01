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
	     * If you want to update the user information when an entry is updated
	     *
	     * @since 1.11
	     * @param boolean $boolean Whether to trigger update on user registration (default: true)
	     */
        if( apply_filters( 'gravityview/edit_entry/user_registration/trigger_update', true ) ) {
            add_action( 'gravityview/edit_entry/after_update' , array( $this, 'update_user' ), 10, 2 );
        }
    }

    /**
     *
     * Update the WordPress user profile based on the GF User Registration create feed
     *
     * @since 1.11
     *
     * @param array $form Gravity Forms form array
     * @param string $entry_id Gravity Forms entry ID
     */
    public function update_user( $form = array(), $entry_id = 0 ) {

        if( !class_exists( 'GFAPI' ) || !class_exists( 'GFUser' ) || empty( $entry_id ) ) {
            return;
        }

        $entry = GFAPI::get_entry( $entry_id );

	    /**
	     * Modify the entry details before updating the user
	     *
	     * @since 1.11
	     * @param array $entry GF entry
	     * @param array $form GF form
	     */
        $entry = apply_filters( 'gravityview/edit_entry/user_registration/entry', $entry, $form );

        // Trigger the User Registration update user method
        GFUser::update_user( $entry, $form );

    }

} //end class
