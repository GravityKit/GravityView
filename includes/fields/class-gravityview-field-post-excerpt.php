<?php
/**
 * @file class-gravityview-field-post-excerpt.php
 */
class GravityView_Post_Excerpt extends GravityView_Field
{
    public $name = 'post_excerpt';

    public $is_searchable = true;

    public $search_operators = ['contains', 'is', 'isnot', 'starts_with', 'ends_with'];

    public $_gf_field_class_name = 'GF_Field_Post_Excerpt';

    public $group = 'post';

    public $icon = 'dashicons-format-quote';

    public function __construct()
    {
        $this->label = esc_html__('Post Excerpt', 'gravityview');
        parent::__construct();
    }

    public function field_options($field_options, $template_id, $field_id, $context, $input_type, $form_id)
    {
        unset($field_options['show_as_link']);

        if ('edit' === $context) {
            return $field_options;
        }

        $this->add_field_support('dynamic_data', $field_options);

        return $field_options;
    }
}

new GravityView_Post_Excerpt();
