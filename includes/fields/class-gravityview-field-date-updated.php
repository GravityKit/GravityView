<?php
/**
 * @file class-gravityview-field-date-updated.php
 */
class GravityView_Field_Date_Updated extends GravityView_Field_Date_Created
{
    public $name = 'date_updated';

    public $is_searchable = true;

    public $_custom_merge_tag = 'date_updated';

    public $search_operators = ['less_than', 'greater_than', 'is', 'isnot'];

    public $group = 'meta';

    public $contexts = ['single', 'multiple', 'export'];

    public $icon = 'dashicons-calendar-alt';

    /**
     * GravityView_Field_Date_Updated constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->label = esc_html__('Date Updated', 'gravityview');
        $this->default_search_label = $this->label;
        $this->description = esc_html__('The date the entry was last updated.', 'gravityview');

        add_filter('gravityview_field_entry_value_'.$this->name.'_pre_link', [$this, 'get_content'], 10, 4);
    }

    /**
     * Adds support for date_display setting for the field.
     *
     * @param array  $field_options
     * @param string $template_id
     * @param string $field_id
     * @param string $context
     * @param string $input_type
     * @param $form_id
     *
     * @return array
     */
    public function field_options($field_options, $template_id, $field_id, $context, $input_type, $form_id)
    {
        if ('edit' === $context) {
            return $field_options;
        }

        $this->add_field_support('date_display', $field_options);

        return $field_options;
    }

    /**
     * Filter the value of the field.
     *
     * @since 2.8.2
     *
     * @param string $output         HTML value output
     * @param array  $entry          The GF entry array
     * @param array  $field_settings Settings for the particular GV field
     * @param array  $field          Current field being displayed
     *
     * @return string values for this field based on the numeric values used by Gravity Forms
     */
    public function get_content($output = '', $entry = [], $field_settings = [], $field = [])
    {

        /** Overridden by a template. */
        if (\GV\Utils::get($field, 'field_path') !== gravityview()->plugin->dir('templates/fields/field-html.php')) {
            return $output;
        }

        return GVCommon::format_date($field['value'], 'format='.\GV\Utils::get($field_settings, 'date_display'));
    }
}

new GravityView_Field_Date_Updated();
