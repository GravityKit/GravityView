<?php
/**
 * @file class-gravityview-field-checkbox.php
 */
class GravityView_Field_Checkbox extends GravityView_Field
{
    public $name = 'checkbox';

    public $is_searchable = true;

    /**
     * @see GFCommon::get_field_filter_settings Gravity Forms suggests checkboxes should just be "is"
     *
     * @var array
     */
    public $search_operators = ['is', 'in', 'not in', 'isnot', 'contains'];

    public $_gf_field_class_name = 'GF_Field_Checkbox';

    public $group = 'standard';

    public $icon = 'dashicons-yes';

    public function __construct()
    {
        $this->label = esc_html__('Checkbox', 'gravityview');
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

        // It's the parent field, not an input
        if (floor($field_id) === floatval($field_id)) {
            return $field_options;
        }

        if ($this->is_choice_value_enabled()) {
            $desc = esc_html__('This input has a label and a value. What should be displayed?', 'gravityview');
            $default = 'value';
            $choices = [
                'tick'  => __('A check mark, if the input is checked', 'gravityview'),
                'value' => __('Value of the input', 'gravityview'),
                'label' => __('Label of the input', 'gravityview'),
            ];
        } else {
            $desc = '';
            $default = 'tick';
            $choices = [
                'tick'  => __('A check mark, if the input is checked', 'gravityview'),
                'label' => __('Label of the input', 'gravityview'),
            ];
        }

        $field_options['choice_display'] = [
            'type'     => 'radio',
            'class'    => 'vertical',
            'label'    => __('What should be displayed:', 'gravityview'),
            'value'    => $default,
            'desc'     => $desc,
            'choices'  => $choices,
            'group'    => 'display',
            'priority' => 100,
        ];

        return $field_options;
    }
}

new GravityView_Field_Checkbox();
