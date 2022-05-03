<?php
/**
 * @file class-gravityview-field-page.php
 */
class GravityView_Field_Page extends GravityView_Field
{
    public $name = 'page';

    public $is_searchable = false;

    /** @see GF_Field_Page */
    public $_gf_field_class_name = 'GF_Field_Page';

    public $group = 'standard';

    public $icon = 'dashicons-media-text';

    public function __construct()
    {
        $this->label = esc_html__('Page', 'gravityview');
        parent::__construct();
    }
}

new GravityView_Field_Page();
