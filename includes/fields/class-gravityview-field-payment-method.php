<?php
/**
 * @file class-gravityview-field-payment-method.php
 *
 * @since 1.16
 */
class GravityView_Field_Payment_Method extends GravityView_Field
{
    public $name = 'payment_method';

    public $is_searchable = true;

    public $is_numeric = false;

    public $search_operators = ['is', 'isnot', 'contains'];

    public $group = 'pricing';

    public $_custom_merge_tag = 'payment_method';

    public $icon = 'dashicons-cart';

    /**
     * GravityView_Field_Date_Created constructor.
     */
    public function __construct()
    {
        $this->label = esc_html__('Payment Method', 'gravityview');
        $this->description = esc_html__('The way the entry was paid for (ie "Credit Card", "PayPal", etc.)', 'gravityview');
        parent::__construct();
    }
}

new GravityView_Field_Payment_Method();
