<?php
/**
 * @file class-gravityview-item-setting-link-to-post.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Link_To_Post extends GravityView_Item_Setting {

    var $name = 'link_to_post';

    var $type = 'checkbox';

    var $value = false;

    public function __construct() {

        $this->label = esc_html__( 'Link to the post', 'gravityview' );
        $this->description = esc_html__( 'Link to the post created by the entry.', 'gravityview' );

        $this->add_visibility_condition( 'item_type', 'is', 'field' );
        $this->add_visibility_condition( 'item_id', 'in', array() );

        parent::__construct();

    }

}