<?php
/**
 * @file class-gravityview-item-setting-anchor-text.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Anchor_Text extends GravityView_Item_Setting {

    var $name = 'anchor_text';

    var $type = 'text';

    var $value = '';

    var $merge_tags = 'force';


    public function __construct() {

        $this->label = esc_html__( 'Link Text:', 'gravityview' );
        $this->description = esc_html__( 'Define custom link text. Leave blank to display the URL', 'gravityview' );
        parent::__construct();
    }

}