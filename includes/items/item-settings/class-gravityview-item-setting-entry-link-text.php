<?php
/**
 * @file class-gravityview-item-setting-entry-link-text.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Entry_Link_Text extends GravityView_Item_Setting {

    var $name = 'entry_link_text';

    var $type = 'text';

    var $merge_tags = true;

    public function __construct() {

        $this->label = esc_html__( 'Link Text:', 'gravityview' );

        $this->value = esc_html__('View Details', 'gravityview');

        parent::__construct();
    }

}