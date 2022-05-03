<?php
/**
 * GravityView placeholder templates.
 *
 * @file class-gravityview-placeholder-template.php
 *
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 *
 * @link      http://gravityview.co
 *
 * @copyright Copyright 2021, Katz Web Services, Inc.
 *
 * @since 2.10
 */
class GravityView_Placeholder_Template extends GravityView_Template
{
    public function __construct($id = 'template_placeholder', $settings = [])
    {
        $default_template_settings = [
            'type'        => 'custom',
            'buy_source'  => 'https://gravityview.co/pricing/',
            'slug'        => '',
            'label'       => '',
            'description' => '',
            'logo'        => '',
            'price_id'    => '',
            'textdomain'  => '',
        ];

        $settings = wp_parse_args($settings, $default_template_settings);

        $this->id = $id;
        $this->settings = $settings;

        parent::__construct($id, $settings, [], []);
    }
}
