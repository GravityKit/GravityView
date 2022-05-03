<?php
/**
 * @file class-gravityview-field-transaction-id.php
 *
 * @since 1.16
 */
class GravityView_Field_Transaction_ID extends GravityView_Field
{
    public $name = 'transaction_id';

    public $is_searchable = true;

    public $is_numeric = true;

    public $search_operators = ['is', 'isnot', 'starts_with', 'ends_with'];

    public $group = 'pricing';

    public $_custom_merge_tag = 'transaction_id';

    public $icon = 'dashicons-cart';

    /**
     * GravityView_Field_Payment_Amount constructor.
     */
    public function __construct()
    {
        $this->label = esc_html__('Transaction ID', 'gravityview');
        parent::__construct();
    }
}

new GravityView_Field_Transaction_ID();
