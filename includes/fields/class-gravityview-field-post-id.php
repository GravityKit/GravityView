<?php
/**
 * @file class-gravityview-field-post-id.php
 *
 * @since 1.7
 */

/**
 * Add custom options for Post ID fields.
 *
 * @since 1.7
 */
class GravityView_Field_Post_ID extends GravityView_Field
{
    public $name = 'post_id';

    public $is_searchable = true;

    public $search_operators = ['is', 'isnot', 'greater_than', 'less_than'];

    public $group = 'post';

    /**
     * GravityView_Field_Post_ID constructor.
     */
    public function __construct()
    {
        $this->label = esc_html__('Post ID', 'gravityview');
        parent::__construct();
    }

    public function field_options($field_options, $template_id, $field_id, $context, $input_type, $form_id)
    {
        $this->add_field_support('link_to_post', $field_options);

        return $field_options;
    }
}

new GravityView_Field_Post_ID();
