<?php
/**
 * @file class-gravityview-item-setting-link-to-source.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Link_To_Source extends GravityView_Item_Setting {

    var $name = 'link_to_source';

    var $type = 'checkbox';

    var $value = false;

    public function __construct() {

        $this->label = esc_html__( 'Link to URL:', 'gravityview' );
        $this->tooltip = esc_html__( 'Display as a link to the Source URL', 'gravityview' );
		parent::__construct();
    }

}