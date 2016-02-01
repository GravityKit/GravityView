<?php
/**
 * @file class-gravityview-item-setting-only-loggedin-cap.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Only_Loggedin_Cap extends GravityView_Item_Setting {

    var $name = 'only_loggedin_cap';

    var $type = 'select';

    var $class = 'widefat';

    var $value = 'read';

    public function __construct() {

        $this->label = esc_html__( 'Make visible for:', 'gravityview' );

        $this->options = self::get_cap_choices();

        $this->add_visibility_condition( 'item_type', 'is', 'field' );

        parent::__construct();

    }


    /**
     * Get capabilities options for GravityView
     *
     * Parameters are only to pass to the filter.
     *
     * @return array Associative array, with the key being the capability and the value being the label shown.
     */
    static public function get_cap_choices() {

        $select_cap_choices = array(
            'read' => __( 'Any Logged-In User', 'gravityview' ),
            'publish_posts' => __( 'Author Or Higher', 'gravityview' ),
            'gravityforms_view_entries' => __( 'Can View Gravity Forms Entries', 'gravityview' ),
            'delete_others_posts' => __( 'Editor Or Higher', 'gravityview' ),
            'gravityforms_edit_entries' => __( 'Can Edit Gravity Forms Entries', 'gravityview' ),
            'manage_options' => __( 'Administrator', 'gravityview' ),
        );

        if( is_multisite() ) {
            $select_cap_choices['manage_network'] = __('Multisite Super Admin', 'gravityview' );
        }

        /**
         * @filter `gravityview_field_visibility_caps` Modify the capabilities shown in the field dropdown
         * @see http://docs.gravityview.co/article/96-how-to-modify-capabilities-shown-in-the-field-only-visible-to-dropdown
         * @since  1.0.1
         * @param  array $select_cap_choices Associative array of role slugs with labels ( `manage_options` => `Administrator` )
         */
        $select_cap_choices = apply_filters( 'gravityview_field_visibility_caps', $select_cap_choices );

        return $select_cap_choices;
    }

}