<?php
/**
 * @file class-gravityview-item-setting-show-map-link.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Show_Map_Link extends GravityView_Item_Setting {

    var $name = 'show_map_link';

    var $type = 'checkbox';

    var $value = true;

    public function __construct() {

        $this->label = esc_html__( 'Show Map Link:', 'gravityview' );
        $this->description = esc_html__( 'Display a "Map It" link below the address', 'gravityview' );

        $this->add_visibility_condition( 'item_type', 'is', 'field' );

        parent::__construct();
    }

}
