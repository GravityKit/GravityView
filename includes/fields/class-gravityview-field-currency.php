<?php
/**
 * @file class-gravityview-field-currency.php
 *
 * @since 1.16
 */
class GravityView_Field_Currency extends GravityView_Field
{
    public $name = 'currency';

    public $is_searchable = true;

    public $is_numeric = true;

    public $search_operators = ['is', 'isnot'];

    public $group = 'pricing';

    public $_custom_merge_tag = 'currency';

    /**
     * GravityView_Field_Currency constructor.
     */
    public function __construct()
    {
        $this->label = esc_html__('Currency', 'gravityview');
        parent::__construct();
    }
}

new GravityView_Field_Currency();
