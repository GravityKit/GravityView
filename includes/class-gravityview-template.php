<?php

/**
 * Class used to register a new template to be shown in GravityView presets.
 */
abstract class GravityView_Template
{
    /**
     * @var string template unique id
     */
    public $template_id;

    /**
     * @var array  {
     * @var string $slug - template slug (frontend)
     * @var string $css_source - url path to CSS file, to be enqueued (frontend)
     * @var string $type - 'custom' or 'preset' (admin)
     * @var string $label - template nicename (admin)
     * @var string $description - short about text (admin)
     * @var string $logo - template icon (admin)
     * @var string $preview - template image for previewing (admin)
     * @var string $buy_source - url source for buying this template
     * @var string $preset_form - path to Gravity Form form XML file
     * @var string $preset_config - path to View config (XML)
     *             }
     */
    public $settings;

    /**
     * @var array form fields extra options
     */
    public $field_options;

    /**
     * @var array define the active areas
     */
    public $active_areas;

    public function __construct($id, $settings = [], $field_options = [], $areas = [])
    {
        if (empty($id)) {
            return;
        }

        $this->template_id = $id;

        $this->merge_defaults($settings);

        $this->field_options = $field_options;
        $this->active_areas = $areas;

        $this->add_hooks();
    }

    /**
     * Add filters and actions for the templates.
     *
     * @since 1.15
     */
    private function add_hooks()
    {
        add_filter('gravityview_register_directory_template', [$this, 'register_template']);

        // presets hooks:
        // form xml
        add_filter('gravityview_template_formxml', [$this, 'assign_form_xml'], 10, 2);
        // fields config xml
        add_filter('gravityview_template_fieldsxml', [$this, 'assign_fields_xml'], 10, 2);

        // assign active areas
        add_filter('gravityview_template_active_areas', [$this, 'assign_active_areas'], 10, 3);

        // field options
        add_filter('gravityview_template_field_options', [$this, 'assign_field_options'], 10, 5);

        // template slug
        add_filter("gravityview_template_slug_{$this->template_id}", [$this, 'assign_view_slug'], 10, 2);

        // register template CSS
        add_action('wp_enqueue_scripts', [$this, 'register_styles']);
    }

    /**
     * Merge the template settings with the default settings.
     *
     * Sets the `settings` object var.
     *
     * @param array $settings Defined template settings
     *
     * @return array Merged template settings.
     */
    private function merge_defaults($settings = [])
    {
        $defaults = [
            'slug'          => '',
            'css_source'    => '',
            'type'          => '',
            'label'         => '',
            'description'   => '',
            'logo'          => '',
            'preview'       => '',
            'buy_source'    => '',
            'preset_form'   => '',
            'preset_fields' => '',
        ];

        $this->settings = wp_parse_args($settings, $defaults);

        return $this->settings;
    }

    /**
     * Register the template to display in the admin.
     *
     *
     * @param mixed $templates
     *
     * @return array Array of templates available for GV
     */
    public function register_template($templates)
    {
        $templates[$this->template_id] = $this->settings;

        return $templates;
    }

    /**
     * Assign active areas (for admin configuration).
     *
     *
     * @param array  $areas
     * @param string $template (default: '')
     *
     * @return array Array of active areas
     */
    public function assign_active_areas($areas, $template = '', $context = 'directory')
    {
        if ($this->template_id === $template) {
            $areas = $this->get_active_areas($context);
        }

        return $areas;
    }

    public function get_active_areas($context)
    {
        if (isset($this->active_areas[$context])) {
            return $this->active_areas[$context];
        } else {
            return $this->active_areas;
        }
    }

    /**
     * Assign template specific field options.
     *
     * @param array        $options  (default: array())
     * @param string       $template (default: '')
     * @param string       $field_id key for the field
     * @param string|array $context  Context for the field; `directory` or `single` for example.
     *
     * @return array Array of field options
     */
    public function assign_field_options($field_options, $template_id, $field_id = null, $context = 'directory', $input_type = '')
    {
        if ($this->template_id === $template_id) {
            foreach ($this->field_options as $key => $field_option) {
                $field_context = \GV\Utils::get($field_option, 'context');

                // Does the field option only apply to a certain context?
                // You can define multiple contexts as an array:  `context => array("directory", "single")`
                $context_matches = is_array($field_context) ? in_array($context, $field_context) : $context === $field_context;

                // If the context matches (or isn't defined), add the field options.
                if ($context_matches) {
                    $field_options[$key] = $field_option;
                }
            }
        }

        return $field_options;
    }

    /**
     * Set the Gravity Forms import form information by using the `preset_form` field defined in the template.
     *
     * @see GravityView_Admin_Views::pre_get_form_fields()
     * @see GravityView_Admin_Views::create_preset_form()
     *
     * @return string Path to XML file
     */
    public function assign_form_xml($xml = '', $template = '')
    {
        if ($this->settings['type'] === 'preset' && !empty($this->settings['preset_form']) && $this->template_id === $template) {
            return $this->settings['preset_form'];
        }

        return $xml;
    }

    /**
     * Set the Gravity Forms import form by using the `preset_fields` field defined in the template.
     *
     * @see GravityView_Admin_Views::pre_get_form_fields()
     *
     * @return string Path to XML file
     */
    public function assign_fields_xml($xml = '', $template = '')
    {
        if ($this->settings['type'] === 'preset' && !empty($this->settings['preset_fields']) && $this->template_id === $template) {
            return $this->settings['preset_fields'];
        }

        return $xml;
    }

    /**
     * Assign the template slug when loading the presentation template (frontend).
     *
     *
     * @param mixed $default
     *
     * @return string
     */
    public function assign_view_slug($default, $context)
    {
        if (!empty($this->settings['slug'])) {
            return $this->settings['slug'];
        }
        if (!empty($default)) {
            return $default;
        }

        // last resort, template_id
        return $this->template_id;
    }

    /**
     * If the template has a CSS file defined in the `css_source` setting, register it
     * It will be registered using `gravityview_style_{template_id}` format.
     *
     * @return void
     */
    public function register_styles()
    {
        if (!empty($this->settings['css_source'])) {
            wp_register_style('gravityview_style_'.$this->template_id, $this->settings['css_source'], [], GravityView_Plugin::version, 'all');
        }
    }
}
