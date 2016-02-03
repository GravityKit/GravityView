<?php
/**
 * @file class-gravityview-item-setting-emailsubject.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Emailsubject extends GravityView_Item_Setting {

    var $name = 'emailsubject';

    var $type = 'text';

    var $value = '';

    var $merge_tags = 'force';

    public function __construct() {

        $this->label = esc_html__( 'Email Subject', 'gravityview' );
        $this->description = esc_html__( 'Set the default email subject line.', 'gravityview' );

        parent::__construct();

    }

}
