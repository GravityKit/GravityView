<?php
/**
 * @file class-gravityview-item-setting-link-to-file.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Link_To_File extends GravityView_Item_Setting {

    var $name = 'link_to_file';

    var $type = 'checkbox';

    var $value = false;

    public function __construct() {

        $this->label = esc_html__(  'Display as a Link:', 'gravityview' );
        $this->description = esc_html__( 'Display the uploaded files as links, rather than embedded content.', 'gravityview' );

        parent::__construct();

    }

}
