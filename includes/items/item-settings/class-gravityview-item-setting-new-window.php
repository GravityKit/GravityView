<?php
/**
 * @file class-gravityview-item-setting-new-window.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_New_Window extends GravityView_Item_Setting {

    var $name = 'new_window';

    var $type = 'checkbox';

    var $value = false;

    public function __construct() {

        $this->label = esc_html__( 'Open link in a new tab or window?', 'gravityview' );

        $this->add_visibility_condition( 'item_type', 'is', 'field' );
        $this->add_visibility_condition( 'item_id', 'in', array() );

        parent::__construct();

    }

}