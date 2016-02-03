<?php
/**
 * @file class-gravityview-item-setting-trim-words.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Trim_Words extends GravityView_Item_Setting {

    var $name = 'trim_words';

    var $type = 'number';

    var $value = null;

    public function __construct() {

        $this->label = esc_html__( 'Maximum words shown', 'gravityview' );
        $this->tooltip = esc_html__( 'Enter the number of words to be shown. If specified it truncates the text. Leave it blank if you want to show the full text.', 'gravityview' );
		parent::__construct();
    }

}