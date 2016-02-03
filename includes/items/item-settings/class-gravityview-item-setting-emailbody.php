<?php
/**
 * @file class-gravityview-item-setting-emailbody.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Emailbody extends GravityView_Item_Setting {

    var $name = 'emailbody';

    var $type = 'textarea';

    var $value = '';

    var $merge_tags = 'force';

    var $class = 'widefat';

    public function __construct() {

        $this->label = esc_html__( 'Email Body', 'gravityview' );
        $this->description = esc_html__( 'Set the default email content.', 'gravityview' );

        parent::__construct();

    }

}
