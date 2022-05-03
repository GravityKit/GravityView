<?php
/**
 * @file class-gravityview-field-html.php
 */

/**
 * Add custom options for HTML field.
 */
class GravityView_Field_HTML extends GravityView_Field
{
    public $name = 'html';

    public $is_searchable = false;

    public $is_sortable = false;

    public $_gf_field_class_name = 'GF_Field_HTML';

    public $group = 'standard';

    public $icon = 'dashicons-media-code';

    public function __construct()
    {
        $this->label = esc_html__('HTML', 'gravityview');
        parent::__construct();
    }

    public function field_options($field_options, $template_id, $field_id, $context, $input_type, $form_id)
    {
        unset($field_options['search_filter'], $field_options['show_as_link']);

        return $field_options;
    }
}

new GravityView_Field_HTML();
