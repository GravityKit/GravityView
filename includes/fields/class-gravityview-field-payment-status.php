<?php
/**
 * @file class-gravityview-field-payment-status.php
 *
 * @since 1.16
 */
class GravityView_Field_Payment_Status extends GravityView_Field
{
    public $name = 'payment_status';

    public $is_searchable = true;

    public $search_operators = ['is', 'in', 'not in', 'isnot'];

    public $group = 'pricing';

    public $_custom_merge_tag = 'payment_status';

    public $icon = 'dashicons-cart';

    /**
     * GravityView_Field_Payment_Status constructor.
     */
    public function __construct()
    {
        $this->label = esc_html__('Payment Status', 'gravityview');
        $this->description = esc_html__('The current payment status of the entry (ie "Processing", "Failed", "Cancelled", "Approved").', 'gravityview');
        parent::__construct();
    }
}

new GravityView_Field_Payment_Status();
