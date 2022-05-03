<?php
/**
 * @file class-gravityview-field-section.php
 */

/**
 * Add custom options for HTML field.
 */
class GravityView_Field_Section extends GravityView_Field
{
    public $name = 'section';

    public $is_searchable = false;

    public $_gf_field_class_name = 'GF_Field_Section';

    public $group = 'standard';

    public $icon = 'dashicons-minus';

    public function __construct()
    {
        $this->label = esc_html__('Section', 'gravityview');

        parent::__construct();

        add_filter('gravityview_field_entry_value_section', [$this, 'prevent_empty_field']);
    }

    /**
     * Prevent Sections from being hidden when "Hide Empty Fields" is checked in View settings.
     *
     * @since 1.15.1
     *
     * @param string $output Existing section field output
     *
     * @return string If output was empty, return an empty HTML comment tag. Otherwise, return output.
     */
    public function prevent_empty_field($output = '')
    {
        return empty($output) ? '<!-- -->' : $output;
    }

    public function field_options($field_options, $template_id, $field_id, $context, $input_type, $form_id)
    {
        unset($field_options['search_filter'], $field_options['show_as_link']);

        // Set the default CSS class to gv-section, which applies a border and top/bottom margin
        $field_options['custom_class']['value'] = 'gv-section';

        return $field_options;
    }
}

new GravityView_Field_Section();
