<?php
/**
 * @file class-gravityview-item-setting-wpautop.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Wpautop extends GravityView_Item_Setting {

    var $name = 'wpautop';

    var $type = 'checkbox';

    var $value = '';

    public function __construct() {

        $this->label = esc_html__( 'Automatically add paragraphs to content', 'gravityview' );
        $this->tooltip = esc_html__( 'Wrap each block of text in an HTML paragraph tag (recommended for text).', 'gravityview' );

        parent::__construct();

    }

}