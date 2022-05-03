<?php
/**
 * @file class-gravityview-field-gquiz_score.php
 */
class GravityView_Field_Quiz_Score extends GravityView_Field
{
    public $name = 'quiz_score';

    public $group = 'advanced';

    public $is_searchable = true;

    public $search_operators = ['is', 'isnot', 'greater_than', 'less_than'];

    public $icon = 'dashicons-forms';

    public function __construct()
    {
        $this->label = esc_html__('Quiz Score', 'gravityview');
        parent::__construct();
    }

    public function field_options($field_options, $template_id, $field_id, $context, $input_type, $form_id)
    {
        if ('edit' === $context) {
            return $field_options;
        }

        $new_fields = [
            'quiz_use_max_score' => [
                'type'       => 'checkbox',
                'label'      => __('Show Max Score?', 'gravityview'),
                'desc'       => __('Display score as the a fraction: "[score]/[max score]". If unchecked, will display score.', 'gravityview'),
                'value'      => true,
                'merge_tags' => false,
            ],
        ];

        return $new_fields + $field_options;
    }
}

new GravityView_Field_Quiz_Score();
