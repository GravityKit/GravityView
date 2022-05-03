<?php
/**
 * @file class-gravityview-field-id.php
 *
 * @since 2.10
 */
class GravityView_Field_ID extends GravityView_Field
{
    public $name = 'id';

    public $is_searchable = true;

    public $search_operators = ['is', 'isnot', 'greater_than', 'less_than', 'in', 'not_in'];

    public $group = 'meta';

    public $icon = 'dashicons-code-standards';

    public $is_numeric = true;

    public function __construct()
    {
        $this->label = esc_html__('Entry ID', 'gravityview');
        $this->description = __('The unique ID of the entry.', 'gravityview');
        parent::__construct();
    }

    public function field_options($field_options, $template_id, $field_id, $context, $input_type, $form_id)
    {
        if ('edit' === $context) {
            return $field_options;
        }

        if ('single' === $context) {
            unset($field_options['new_window']);
        }

        return $field_options;
    }
}

new GravityView_Field_ID();
