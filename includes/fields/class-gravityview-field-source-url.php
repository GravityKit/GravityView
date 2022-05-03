<?php
/**
 * @file class-gravityview-field-source-url.php
 */

/**
 * Add custom options for source_url fields.
 */
class GravityView_Field_Source_URL extends GravityView_Field
{
    public $name = 'source_url';

    public $is_searchable = true;

    public $search_operators = ['is', 'isnot', 'contains', 'starts_with', 'ends_with'];

    public $group = 'meta';

    public $icon = 'dashicons-admin-links';

    public function __construct()
    {
        $this->label = esc_html__('Source URL', 'gravityview');
        $this->description = esc_html__('The URL of the page where the form was submitted.', 'gravityview');
        parent::__construct();
    }

    public function field_options($field_options, $template_id, $field_id, $context, $input_type, $form_id)
    {

        // Don't link to entry; doesn't make sense.
        unset($field_options['show_as_link']);

        if ('edit' === $context) {
            return $field_options;
        }

        $add_options = [];
        $add_options['link_to_source'] = [
            'type'       => 'checkbox',
            'label'      => __('Link to URL:', 'gravityview'),
            'desc'       => __('Display as a link to the Source URL', 'gravityview'),
            'value'      => false,
            'merge_tags' => false,
        ];
        $add_options['source_link_text'] = [
            'type'       => 'text',
            'label'      => __('Link Text:', 'gravityview'),
            'desc'       => __('Customize the link text. If empty, the link text will be the URL.', 'gravityview'),
            'value'      => null,
            'merge_tags' => true,
            'requires'   => 'link_to_source',
        ];

        return $add_options + $field_options;
    }
}

new GravityView_Field_Source_URL();
