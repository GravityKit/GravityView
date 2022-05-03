<?php

/**
 * GravityView_Default_Template_List class.
 * Defines List (default) template.
 */
class GravityView_Default_Template_List extends GravityView_Template
{
    public function __construct($id = 'default_list', $settings = [], $field_options = [], $areas = [])
    {
        $rtl = is_rtl() ? '-rtl' : '';

        $list_settings = [
            'slug'        => 'list',
            'type'        => 'custom',
            'label'       => __('List', 'gravityview'),
            'description' => __('Display items in a listing view.', 'gravityview'),
            'logo'        => plugins_url('includes/presets/default-list/logo-default-list.png', GRAVITYVIEW_FILE),
            'css_source'  => gravityview_css_url('list-view'.$rtl.'.css', GRAVITYVIEW_DIR.'templates/css/'),
        ];

        $settings = wp_parse_args($settings, $list_settings);

        $field_options = [
            'show_as_link' => [
                'type'     => 'checkbox',
                'label'    => __('Link to single entry', 'gravityview'),
                'value'    => false,
                'context'  => 'directory',
                'priority' => 1190,
                'group'    => 'display',
            ],
        ];

        $areas = [
            [
                '1-1' => [
                    [
                        'areaid'   => 'list-title',
                        'title'    => __('Listing Title', 'gravityview'),
                        'subtitle' => '',
                    ],
                    [
                        'areaid'   => 'list-subtitle',
                        'title'    => __('Subheading', 'gravityview'),
                        'subtitle' => __('Data placed here will be bold.', 'gravityview'),
                    ],
                ],
                '1-3' => [
                    [
                        'areaid'   => 'list-image',
                        'title'    => __('Image', 'gravityview'),
                        'subtitle' => __('Leave empty to remove.', 'gravityview'),
                    ],
                ],
                '2-3' => [
                    [
                        'areaid'   => 'list-description',
                        'title'    => __('Other Fields', 'gravityview'),
                        'subtitle' => __('Below the subheading, a good place for description and other data.', 'gravityview'),
                    ],
                ],
            ],
            [
                '1-2' => [
                    [
                        'areaid'   => 'list-footer-left',
                        'title'    => __('Footer Left', 'gravityview'),
                        'subtitle' => '',
                    ],
                ],
                '2-2' => [
                    [
                        'areaid'   => 'list-footer-right',
                        'title'    => __('Footer Right', 'gravityview'),
                        'subtitle' => '',
                    ],
                ],
            ],
        ];

        parent::__construct($id, $settings, $field_options, $areas);
    }
}

new GravityView_Default_Template_List();
