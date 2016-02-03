<?php
/**
 * @file class-gravityview-item-setting-source-link-text.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Source_Link_Text extends GravityView_Item_Setting {

    var $name = 'source_link_text';

    var $type = 'text';

    var $value = NULL;

    var $merge_tags = true;

    public function __construct() {

        $this->label = esc_html__( 'Link Text:', 'gravityview' );
        $this->tooltip = esc_html__( 'Customize the link text. If empty, the link text will be the the URL.', 'gravityview' );
		parent::__construct();
    }

}