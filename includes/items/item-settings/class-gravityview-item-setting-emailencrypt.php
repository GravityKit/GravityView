<?php
/**
 * @file class-gravityview-item-setting-emailencrypt.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Emailencrypt extends GravityView_Item_Setting {

    var $name = 'emailencrypt';

    var $type = 'checkbox';

    var $value = true;

    public function __construct() {

        $this->label = esc_html__( 'Encrypt Email Address', 'gravityview' );
        $this->description = esc_html__( 'Make it harder for spammers to get email addresses from your entries. Email addresses will not be visible with Javascript disabled.', 'gravityview' );

        parent::__construct();

    }

}