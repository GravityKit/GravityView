<?php
/**
 * @file class-gravityview-item-setting-custom-label.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Custom_Label extends GravityView_Item_Setting {

    var $name = 'custom_label';

    var $type = 'text';

    var $value = '';

    var $merge_tags = true;

    public function __construct() {

        $this->label = esc_html__( 'Custom Label:', 'gravityview' );

        $this->add_visibility_condition( 'item_type', 'is', 'field' );

        parent::__construct();
    }

}