<?php
/**
 * @file class-gravityview-item-setting-only-loggedin.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Only_Loggedin extends GravityView_Item_Setting {

    var $name = 'only_loggedin';

    var $type = 'checkbox';

    var $value = false;

    public function __construct() {

        $this->label = esc_html__( 'Make visible only to logged-in users?', 'gravityview' );

        $this->add_visibility_condition( 'item_type', 'is', 'field' );


        parent::__construct();

    }

}