<?php
/**
 * @file class-gravityview-item-setting-make-clickable.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Make_Clickable extends GravityView_Item_Setting {

    var $name = 'make_clickable';

    var $type = 'checkbox';

    var $value = false;

    public function __construct() {

        $this->label = esc_html__( 'Convert text URLs to HTML links', 'gravityview' );
        $this->tooltip = esc_html__( 'Converts URI, www, FTP, and email addresses in HTML links', 'gravityview' );
		parent::__construct();
    }

}