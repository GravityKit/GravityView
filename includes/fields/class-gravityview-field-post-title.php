<?php
/**
 * @file class-gravityview-field-post-title.php
 */

/**
 * Add custom options for date fields.
 */
class GravityView_Field_Post_Title extends GravityView_Field
{
    public $name = 'post_title';

    public $is_searchable = true;

    public $search_operators = ['is', 'isnot', 'contains', 'starts_with', 'ends_with'];

    /** @see GF_Field_Post_Title */
    public $_gf_field_class_name = 'GF_Field_Post_Title';

    public $group = 'post';

    public $icon = 'dashicons-edit';

    public function __construct()
    {
        $this->label = esc_html__('Post Title', 'gravityview');
        parent::__construct();
    }

    public function field_options($field_options, $template_id, $field_id, $context, $input_type, $form_id)
    {
        if ('edit' === $context) {
            return $field_options;
        }

        $this->add_field_support('link_to_post', $field_options);

        $this->add_field_support('dynamic_data', $field_options);

        return $field_options;
    }
}

new GravityView_Field_Post_Title();
