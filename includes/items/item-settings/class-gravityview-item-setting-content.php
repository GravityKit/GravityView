<?php
/**
 * @file class-gravityview-item-setting-content.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Content extends GravityView_Item_Setting {

    var $name = 'content';

    var $type = 'textarea';

    var $class = 'code';

    var $value = '';

    var $merge_tags = 'force';

    var $show_all_fields = true;

    public function __construct() {

        $this->label = esc_html__( 'Custom Content', 'gravityview' );
        $this->description = sprintf( __( 'Enter text or HTML. Also supports shortcodes. You can show or hide data using the %s shortcode (%slearn more%s).', 'gravityview' ), '<code>[gvlogic]</code>', '<a href="http://docs.gravityview.co/article/252-gvlogic-shortcode">', '</a>' );

        parent::__construct();
    }

}