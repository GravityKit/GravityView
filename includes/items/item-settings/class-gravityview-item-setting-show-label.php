<?php
/**
 * @file class-gravityview-item-setting-show-label.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Show_Label extends GravityView_Item_Setting {

    var $name = 'show_label';

    var $type = 'checkbox';

    var $value = true;


    public function __construct() {

        $this->label = esc_html__( 'Show Label', 'gravityview' );

        $this->add_visibility_condition( 'item_type', 'is', 'field' );

        parent::__construct();
    }

}
