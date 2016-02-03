<?php
/**
 * @file class-gravityview-item-setting-date-display.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Time_Display extends GravityView_Item_Setting_Date_Display {

    var $name = 'time_display';

    public function __construct() {

        $this->label = esc_html__( 'Override Time Format', 'gravityview' );
        $this->description = sprintf( __( 'Define how the time is displayed (using %sthe PHP date format%s)', 'gravityview'), '<a href="https://codex.wordpress.org/Formatting_Date_and_Time">', '</a>' );

        parent::__construct();
    }

}