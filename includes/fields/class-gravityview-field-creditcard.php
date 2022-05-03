<?php
/**
 * @file class-gravityview-field-creditcard.php
 */
class GravityView_Field_CreditCard extends GravityView_Field
{
    public $name = 'creditcard';

    public $is_searchable = false;

    public $_gf_field_class_name = 'GF_Field_CreditCard';

    public $group = 'payment';

    public $icon = 'dashicons-cart';

    public function __construct()
    {
        $this->label = esc_html__('Credit Card', 'gravityview');
        parent::__construct();
    }
}

new GravityView_Field_CreditCard();
