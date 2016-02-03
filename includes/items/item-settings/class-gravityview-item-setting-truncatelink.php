<?php
/**
 * @file class-gravityview-item-setting-truncatelink.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Truncatelink extends GravityView_Item_Setting {

    var $name = 'truncatelink';

    var $type = 'checkbox';

    var $value = true;

    public function __construct() {

        $this->label = esc_html__( 'Shorten Link Display', 'gravityview' );
        $this->description = esc_html__(  'Don&rsquo;t show the full URL, only show the domain.', 'gravityview' );
        $this->tooltip = esc_html__( 'Only show the domain for a URL instead of the whole link.', 'gravityview' );
        parent::__construct();
    }

}