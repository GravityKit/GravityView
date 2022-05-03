<?php
/**
 * @file class-gravityview-field-email.php
 */

/**
 * Add custom options for email fields.
 */
class GravityView_Field_Email extends GravityView_Field
{
    public $name = 'email';

    public $is_searchable = true;

    public $search_operators = ['is', 'isnot', 'contains', 'starts_with', 'ends_with'];

    public $_gf_field_class_name = 'GF_Field_Email';

    public $group = 'advanced';

    public $icon = 'dashicons-email';

    public function __construct()
    {
        $this->label = esc_html__('Email', 'gravityview');
        parent::__construct();
    }

    public function field_options($field_options, $template_id, $field_id, $context, $input_type, $form_id)
    {

        // It makes no sense to use this as the link.
        unset($field_options['show_as_link']);

        if ('edit' === $context) {
            return $field_options;
        }

        $email_options = [
            'emailmailto' => [
                'type'  => 'checkbox',
                'value' => true,
                'label' => __('Link the Email Address', 'gravityview'),
                'desc'  => __('Clicking the link will generate a new email.', 'gravityview'),
                'group' => 'field',
            ],
            'emailsubject' => [
                'type'       => 'text',
                'label'      => __('Email Subject', 'gravityview'),
                'value'      => '',
                'desc'       => __('Set the default email subject line.', 'gravityview'),
                'merge_tags' => 'force',
                'requires'   => 'emailmailto',
                'group'      => 'field',
            ],
            'emailbody' => [
                'type'       => 'textarea',
                'label'      => __('Email Body', 'gravityview'),
                'value'      => '',
                'desc'       => __('Set the default email content.', 'gravityview'),
                'merge_tags' => 'force',
                'class'      => 'widefat code',
                'requires'   => 'emailmailto',
                'group'      => 'field',
            ],
            'emailencrypt' => [
                'type'     => 'checkbox',
                'value'    => true,
                'label'    => __('Encrypt Email Address', 'gravityview'),
                'desc'     => __('Make it harder for spammers to get email addresses from your entries. Email addresses will not be visible with Javascript disabled.', 'gravityview'),
                'group'    => 'advanced',
                'priority' => 100,
            ],
        ];

        return $email_options + $field_options;
    }
}

new GravityView_Field_Email();
