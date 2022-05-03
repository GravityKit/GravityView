<?php
/**
 * @file class-gravityview-field-captcha.php
 */
class GravityView_Field_Captcha extends GravityView_Field
{
    public $name = 'captcha';

    public $is_searchable = false;

    public $_gf_field_class_name = 'GF_Field_CAPTCHA';

    public $group = 'advanced';

    public $icon = 'dashicons-shield-alt';

    public function __construct()
    {
        $this->label = esc_html__('CAPTCHA', 'gravityview');
        parent::__construct();
    }
}

new GravityView_Field_Captcha();
