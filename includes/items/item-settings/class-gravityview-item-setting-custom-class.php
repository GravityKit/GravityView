<?php
/**
 * @file class-gravityview-item-setting-custom-class.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Custom_Class extends GravityView_Item_Setting {

    var $name = 'custom_class';

    var $type = 'text';

    var $value = '';

    var $merge_tags = true;

    var $tooltip = 'gv_css_merge_tags';

    public function __construct() {

        $this->label = esc_html__( 'Custom Class', 'gravityview' );
        $this->description = esc_html__( 'This class will be added to the field container', 'gravityview');

        $this->add_visibility_condition( 'item_type', 'is', 'field' );

        parent::__construct();
    }

}