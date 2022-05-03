<?php
/**
 * @file class-gravityview-field-name.php
 */
class GravityView_Field_Name extends GravityView_Field
{
    public $name = 'name';

    /** @see GF_Field_Name */
    public $_gf_field_class_name = 'GF_Field_Name';

    public $group = 'advanced';

    public $search_operators = ['is', 'isnot', 'contains'];

    public $is_searchable = true;

    public $icon = 'dashicons-admin-users';

    public function __construct()
    {
        $this->label = esc_html__('Name', 'gravityview');
        parent::__construct();
    }
}

new GravityView_Field_Name();
