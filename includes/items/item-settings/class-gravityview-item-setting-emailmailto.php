<?php
/**
 * @file class-gravityview-item-setting-emailmailto.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Emailmailto extends GravityView_Item_Setting {

    var $name = 'emailmailto';

    var $type = 'checkbox';

    var $value = true;

    public function __construct() {

        $this->label = esc_html__( 'Link the Email Address', 'gravityview' );
        $this->description = esc_html__( 'Clicking the link will generate a new email.', 'gravityview' );

        parent::__construct();

    }

}
