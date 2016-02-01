<?php
/**
 * @file class-gravityview-item-setting-name-display.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Name_Display extends GravityView_Item_Setting {

    var $name = 'name_display';

    var $type = 'select';

    var $class = 'widefat';

    var $value = 'display_name';

    public function __construct() {

        $this->label = esc_html__( 'User Format', 'gravityview' );

        $this->description = esc_html__( 'How should the User information be displayed?', 'gravityview' );

        $this->options = self::get_choices();

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
    static public function get_choices() {

        $select_choices = array(
            'display_name' => __('Display Name (Example: "Ellen Ripley")', 'gravityview' ),
            'user_login' => __('Username (Example: "nostromo")', 'gravityview' ),
            'ID' => __('User ID # (Example: 426)', 'gravityview' ),
        );

        /**
         * @filter `gravityview/item/setting/name_display/choices` Modify the name display choices
         * @see
         * @since
         * @param  array $select_choices Associative array of the name display choices vs their labels ( `user_login` => `Username` )
         */
        $select_choices = apply_filters( 'gravityview/item/setting/name_display/choices', $select_choices );

        return $select_choices;
    }

}


