<?php

/**
 * @since 1.2
 */
class GravityView_Change_Entry_Creator {

    function __construct() {

        add_action('plugins_loaded', array( $this, 'load'), 100 );

        add_action('plugins_loaded', array( $this, 'prevent_conflicts') );
    }

    /**
     * Disable previous functionality; use this one as the canonical.
     * @return void
     */
    function prevent_conflicts() {

    	// Plugin that was provided here:
    	// @link https://gravityview.co/support/documentation/201991205/
    	remove_action("gform_entry_info", 'gravityview_change_entry_creator_form', 10, 2);
    	remove_action("gform_after_update_entry", 'gravityview_update_entry_creator', 10, 2);

    	// Disable for Gravity Forms Add-ons 3.6.2 and lower
    	if( class_exists( 'KWS_GF_Change_Lead_Creator' ) ) {

    		$Old_Lead_Creator = new KWS_GF_Change_Lead_Creator;

    		// Now, no validation is required in the methods; let's hook in.
    		remove_action('admin_init', array( $Old_Lead_Creator, 'set_screen_mode' ) );

    		remove_action("gform_entry_info", array( $Old_Lead_Creator, 'add_select' ), 10, 2);

    		remove_action("gform_after_update_entry", array( $Old_Lead_Creator, 'update_entry_creator' ), 10, 2);
    	}

    }

    /**
     * @since  3.6.3
     * @return void
     */
    function load() {

    	// Does GF exist?
        if( !class_exists('GFCommon') ) {
            return;
        }

        // Can the user edit entries?
        if( !GFCommon::current_user_can_any("gravityforms_edit_entries") ) {
            return;
        }

        // If screen mode isn't set, then we're in the wrong place.
        if( empty( $_REQUEST['screen_mode'] ) ) {
            return;
        }

        // Now, no validation is required in the methods; let's hook in.
        add_action('admin_init', array( &$this, 'set_screen_mode' ) );

        add_action("gform_entry_info", array( &$this, 'add_select' ), 10, 2);

        add_action("gform_after_update_entry", array( &$this, 'update_entry_creator' ), 10, 2);

    }

    /**
     * Allows for edit links to work with a link instead of a form (GET instead of POST)
     * @return void
     */
    function set_screen_mode() {

    	// If $_GET['screen_mode'] is set to edit, set $_POST value
        if( rgget('screen_mode') === 'edit' ) {
            $_POST["screen_mode"] = 'edit';
        }

    }

    /**
     * When the entry creator is changed, add a note to the entry
     * @param  array $form   GF entry array
     * @param  int $entry_id Entry ID
     * @return void
     */
    function update_entry_creator($form, $entry_id) {
            global $current_user;

        // Update the entry
        $created_by = absint( rgpost('created_by') );

        RGFormsModel::update_lead_property( $entry_id, 'created_by', $created_by );

        // If the creator has changed, let's add a note about who it used to be.
        $originally_created_by = rgpost('originally_created_by');

        // If there's no owner and there didn't used to be, keep going
        if( empty( $originally_created_by ) && empty( $created_by ) ) {
            return;
        }

        // If the values have changed
        if( absint( $originally_created_by ) !== absint( $created_by ) ) {

            $user_data = get_userdata($current_user->ID);

            $user_format = _x('%s (ID #%d)', 'The name and the ID of users who initiated changes to entry ownership', 'gravityview');

            $original_name = $created_by_name = esc_attr_x( 'No User', 'To show that the entry was unassigned from an actual user to no user.', 'gravityview');

            if( !empty( $originally_created_by ) ) {
                $originally_created_by_user_data = get_userdata($originally_created_by);
                $original_name = sprintf( $user_format, $originally_created_by_user_data->display_name, $originally_created_by_user_data->ID );
            }

            if( !empty( $created_by ) ) {
                $created_by_user_data =  get_userdata($created_by);
                $created_by_name = sprintf( $user_format, $created_by_user_data->display_name, $created_by_user_data->ID );
            }

            RGFormsModel::add_note( $entry_id, $current_user->ID, $user_data->display_name, sprintf( __('Changed entry creator from %s to %s', 'gravityview'), $original_name, $created_by_name ) );
        }

    }

    /**
     * Output the select to change the entry creator
     * @param int $form_id GF Form ID
     * @param array $entry    GF entry array
     * @return void
     */
    function add_select($form_id, $entry ) {

        if( rgpost('screen_mode') !== 'edit' ) {
            return;
        }

        /**
         * There are issues with too many users where it breaks the select. We try to keep it at a reasonable number.
         * @link   text http://codex.wordpress.org/Function_Reference/get_users
         * @var  array Settings array
         */
        $get_users_settings = apply_filters( 'gravityview_change_entry_creator_user_parameters', array( 'number' => 300 ) );

        $users = get_users( $get_users_settings );

        $output = '<label for="change_created_by">';
        $output .= esc_html__('Change Entry Creator:', 'gravityview');
        $output .= '</label>
        <select name="created_by" id="change_created_by" class="widefat">';
        $output .= '<option value=""> &mdash; '.esc_attr_x( 'No User', 'No user assigned to the entry', 'gravityview').' &mdash; </option>';
        foreach($users as $user) {
            $output .= '<option value="'. $user->ID .'"'. selected( $entry['created_by'], $user->ID, false ).'>'.esc_attr( $user->display_name.' ('.$user->user_nicename.')' ).'</option>';
        }
        $output .= '</select>';
        $output .= '<input name="originally_created_by" value="'.esc_attr( $entry['created_by'] ).'" type="hidden" />';
        echo $output;

    }

}

new GravityView_Change_Entry_Creator;
