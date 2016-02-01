<?php
/**
 * @file class-gravityview-item-setting-link-to-term.php
 * @package GravityView
 * @subpackage includes/items/item-settings
 */

class GravityView_Item_Setting_Link_To_Term extends GravityView_Item_Setting {

    var $name = 'link_to_term';

    var $type = 'checkbox';

    var $value = false;

    public function __construct() {

        $this->label = esc_html__( 'Link to the category or tag', 'gravityview' );
        $this->description = esc_html__( 'Link to the current category or tag. "Link to single entry" must be unchecked.', 'gravityview' );

        $this->add_visibility_condition( 'item_type', 'is', 'field' );
        $this->add_visibility_condition( 'item_id', 'in', array() );

        parent::__construct();

    }

}