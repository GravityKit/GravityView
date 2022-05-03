<?php
/**
 * @file class-gravityview-field-id.php
 *
 * @since 2.10
 */
class GravityView_Field_IP extends GravityView_Field
{
    public $name = 'ip';

    public $is_searchable = true;

    public $search_operators = ['is', 'isnot', 'contains'];

    public $group = 'meta';

    public $icon = 'dashicons-laptop';

    public $is_numeric = true;

    public function __construct()
    {
        $this->label = __('User IP', 'gravityview');
        $this->description = __('The IP Address of the user who created the entry.', 'gravityview');
        parent::__construct();
    }
}

new GravityView_Field_IP();
