<?php
/**
 * @file class-gravityview-field-radio.php
 */
class GravityView_Field_Radio extends GravityView_Field
{
    public $name = 'radio';

    public $is_searchable = true;

    public $search_operators = ['is', 'in', 'not in', 'isnot', 'contains'];

    public $_gf_field_class_name = 'GF_Field_Radio';

    public $group = 'standard';

    public $icon = 'dashicons-marker';

    public function __construct()
    {
        $this->label = esc_html__('Radio Buttons', 'gravityview');
        parent::__construct();
    }

    /**
     * Add `choice_display` setting to the field.
     *
     * @param array  $field_options
     * @param string $template_id
     * @param string $field_id
     * @param string $context
     * @param string $input_type
     *
     * @since 1.17
     *
     * @return array
     */
    public function field_options($field_options, $template_id, $field_id, $context, $input_type, $form_id)
    {

        // Set the $_field_id var
        $field_options = parent::field_options($field_options, $template_id, $field_id, $context, $input_type, $form_id);

        if ($this->is_choice_value_enabled()) {
            $field_options['choice_display'] = [
                'type'    => 'radio',
                'value'   => 'value',
                'label'   => __('What should be displayed:', 'gravityview'),
                'desc'    => __('This input has a label and a value. What should be displayed?', 'gravityview'),
                'choices' => [
                    'value' => __('Value of the input', 'gravityview'),
                    'label' => __('Label of the input', 'gravityview'),
                ],
                'group'   => 'display',
            ];
        }

        return $field_options;
    }
}

new GravityView_Field_Radio();
