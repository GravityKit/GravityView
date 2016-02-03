<?php
/**
 * @file class-gravityview-item-setting-date-display.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Date_Display extends GravityView_Item_Setting {

    var $name = 'date_display';

    var $type = 'text';

    var $merge_tags = true;

    var $tooltip = 'gv_css_merge_tags';

    public function __construct() {

        $this->label = esc_html__( 'Override Date Format', 'gravityview' );
        $this->description = sprintf( __( 'Define how the date is displayed (using %sthe PHP date format%s)', 'gravityview'), '<a href="https://codex.wordpress.org/Formatting_Date_and_Time">', '</a>' );

        /**
         * @filter `gravityview_date_format` Override the date format with a [PHP date format](https://codex.wordpress.org/Formatting_Date_and_Time)
         * @param[in,out] null|string $date_format Date Format (default: null)
         */
        $this->value = apply_filters( 'gravityview_date_format', null );

        $this->add_visibility_condition( 'item_type', 'is', 'field' );

        parent::__construct();
    }

}