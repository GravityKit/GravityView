<?php
/**
 * @file class-gravityview-item-setting-width.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Width extends GravityView_Item_Setting {

    var $name = 'width';

    var $type = 'number';

    var $value = '';

    var $class = 'code widefat';

    public function __construct() {

        $this->label = esc_html__( 'Percent Width', 'gravityview' );
        $this->description = esc_html__( 'Leave blank for column width to be based on the field content.', 'gravityview');

        $this->add_visibility_condition( 'item_type', 'is', 'field' );
        $this->add_visibility_condition( 'context', 'is', 'directory' );
        $this->add_visibility_condition( 'template', 'like', 'table' );


        parent::__construct();
    }

}
