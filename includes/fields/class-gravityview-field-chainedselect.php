<?php
/**
 * @file class-gravityview-field-chainedselect.php
 */
class GravityView_Field_Chained_Select extends GravityView_Field
{
    public $name = 'chainedselect';

    public $is_searchable = true;

    public $search_operators = ['is', 'isnot'];

    public $_gf_field_class_name = 'GF_Field_ChainedSelect';

    public $group = 'add-ons';

    public $icon = 'dashicons-admin-links';

    public function __construct()
    {
        $this->label = esc_html__('Chained Select', 'gravityview');
        parent::__construct();
    }
}

new GravityView_Field_Chained_Select();
