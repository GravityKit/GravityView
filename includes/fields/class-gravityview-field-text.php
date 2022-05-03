<?php
/**
 * @file class-gravityview-field-text.php
 */
class GravityView_Field_Text extends GravityView_Field
{
    public $name = 'text';

    public $_gf_field_class_name = 'GF_Field_Text';

    public $is_searchable = true;

    public $search_operators = ['contains', 'is', 'isnot', 'starts_with', 'ends_with'];

    public $group = 'standard';

    public $icon = 'dashicons-editor-textcolor';

    public function __construct()
    {
        $this->label = esc_html__('Single Line Text', 'gravityview');
        parent::__construct();
    }
}

new GravityView_Field_Text();
