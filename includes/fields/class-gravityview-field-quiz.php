<?php
/**
 * @file class-gravityview-field-gquiz.php
 */
class GravityView_Field_Quiz extends GravityView_Field
{
    public $name = 'quiz';

    public $group = 'advanced';

    public $icon = 'dashicons-forms';

    public function __construct()
    {
        $this->label = esc_html__('Quiz', 'gravityview');
        parent::__construct();
    }

    public function field_options($field_options, $template_id, $field_id, $context, $input_type, $form_id)
    {
        if ('edit' === $context) {
            return $field_options;
        }

        $new_fields = [
            'quiz_show_explanation' => [
                'type'       => 'checkbox',
                'label'      => __('Show Answer Explanation?', 'gravityview'),
                'desc'       => __('If the field has an answer explanation, show it?', 'gravityview'),
                'value'      => false,
                'merge_tags' => false,
            ],
        ];

        return $new_fields + $field_options;
    }
}

new GravityView_Field_Quiz();
