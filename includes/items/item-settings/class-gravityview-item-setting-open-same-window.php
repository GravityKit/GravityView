<?php
/**
 * @file class-gravityview-item-setting-open-same-window.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Open_Same_window extends GravityView_Item_Setting {

    var $name = 'open_same_window';

    var $type = 'checkbox';

    var $value = false;

    public function __construct() {

        $this->label = esc_html__( 'Open link in the same window?', 'gravityview' );
        parent::__construct();
    }

}