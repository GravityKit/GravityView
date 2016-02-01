<?php
/**
 * @file class-gravityview-item-setting-dynamic-data.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Dynamic_Data extends GravityView_Item_Setting {

    var $name = 'dynamic_data';

    var $type = 'checkbox';

    var $value = true;

    public function __construct() {

        $this->label = esc_html__( 'Use the live post data', 'gravityview' );
        $this->description = esc_html__( 'Instead of using the entry data, instead use the current post data.', 'gravityview' );


        $this->add_visibility_condition( 'item_type', 'is', 'field' );
        $this->add_visibility_condition( 'item_id', 'in', array() );

        parent::__construct();

    }

}