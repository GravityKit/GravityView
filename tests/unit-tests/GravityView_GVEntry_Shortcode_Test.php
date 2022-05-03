<?php

defined('DOING_GRAVITYVIEW_TESTS') || exit;

/**
 * @group shortcode
 */
class GravityView_GVEntry_Shortcode_Test extends GV_UnitTestCase
{
    public function test_shortcode()
    {
        $form = $this->factory->form->import_and_get('complete.json');
        $settings = \GV\View_Settings::defaults();
        $settings['show_only_approved'] = 0;
        $view = $this->factory->view->create_and_get([
            'form_id' => $form['id'],
            'fields'  => [
                'single_table-columns' => [
                    wp_generate_password(4, false) => [
                        'id'    => '16',
                        'label' => 'Textarea',
                    ],
                    wp_generate_password(4, false) => [
                        'id'    => 'id',
                        'label' => 'Entry ID',
                    ],
                ],
            ],
            'settings' => $settings,
        ]);
        $view = \GV\View::from_post($view);

        $entry = $this->factory->entry->create_and_get([
            'form_id' => $form['id'],
            'status'  => 'active',
            '16'      => 'hello',
        ]);

        $atts = [
            'view_id'  => $view->ID,
            'entry_id' => $entry['id'],
        ];

        $atts_id = [
            'view_id' => $view->ID,
            'id'      => $entry['id'],
        ];

        $gventry = new \GV\Shortcodes\gventry();

        $this->assertContains('<span class="gv-field-label">Textarea</span></th><td><p>hello</p>', $gventry->callback($atts));

        $this->assertContains('<span class="gv-field-label">Textarea</span></th><td><p>hello</p>', $gventry->callback($atts_id));

        $get_atts = [
            'view_id'  => '{get:view_id}',
            'entry_id' => '{get:entry_id}',
        ];

        $_GET = $atts;

        $this->assertContains('<span class="gv-field-label">Textarea</span></th><td><p>hello</p>', $gventry->callback($get_atts), '$_GET merge tags not being replaced');

        $_GET = [];

        $another_entry = $this->factory->entry->create_and_get([
            'form_id' => $form['id'],
            'status'  => 'active',
            '16'      => 'well, o!',
        ]);

        /** Test the filters */
        $_this = &$this;
        add_filter('gravityview/shortcodes/gventry/atts', function ($atts) use ($_this, $another_entry, $entry) {
            $_this->assertEquals($entry['id'], $atts['entry_id']);
            $atts['entry_id'] = $another_entry['id'];

            return $atts;
        });

        $this->assertContains('<span class="gv-field-label">Textarea</span></th><td><p>well, o!</p>', $gventry->callback($atts));

        add_filter('gravityview/shortcodes/gventry/output', function ($output) {
            return 'heh, o!';
        });

        $this->assertEquals('heh, o!', $gventry->callback($atts));

        remove_all_filters('gravityview/shortcodes/gventry/atts');
        remove_all_filters('gravityview/shortcodes/gventry/output');

        $and_another_entry = $this->factory->entry->create_and_get([
            'form_id' => $form['id'],
            'status'  => 'active',
            '16', 'zzzZzz :)',
        ]);

        /**
         * Last/first tests.
         *
         * Note to self: first means the latest entry (topmost, first in the list of entries)
         * last means the other way around.
         */
        $atts['entry_id'] = 'first';
        $this->assertContains(sprintf('<span class="gv-field-label">Entry ID</span></th><td>%d</td></tr>', $and_another_entry['id']), $gventry->callback($atts));

        $atts['entry_id'] = 'last';
        $this->assertContains(sprintf('<span class="gv-field-label">Entry ID</span></th><td>%d</td></tr>', $entry['id']), $gventry->callback($atts));
    }

    public function test_failures()
    {
        set_current_screen('dashboard');

        $gventry = new \GV\Shortcodes\gventry();
        $this->assertEmpty($gventry->callback([]));

        set_current_screen('front');

        $gventry = new \GV\Shortcodes\gventry();
        $this->assertEmpty($gventry->callback([]));

        $form = $this->factory->form->import_and_get('complete.json');
        $settings = \GV\View_Settings::defaults();
        $settings['show_only_approved'] = 0;
        $view = $this->factory->view->create_and_get([
            'form_id' => $form['id'],
            'fields'  => [
                'directory_table-columns' => [
                    wp_generate_password(4, false) => [
                        'id'    => '16',
                        'label' => 'Textarea',
                    ],
                    wp_generate_password(4, false) => [
                        'id'    => 'id',
                        'label' => 'Entry ID',
                    ],
                ],
            ],
            'settings' => $settings,
        ]);
        $view = \GV\View::from_post($view);

        $atts = [
            'view_id'  => $view->ID,
            'entry_id' => -100,
        ];

        $this->assertEmpty($gventry->callback($atts));

        $atts = [
            'view_id'  => $view->ID,
            'entry_id' => 'last',
        ];

        $this->assertEmpty($gventry->callback($atts));

        $atts = [
            'view_id'  => $view->ID,
            'entry_id' => 'first',
        ];

        $this->assertEmpty($gventry->callback($atts));

        $entry = $this->factory->entry->create_and_get([
            'form_id' => $form['id'],
            'status'  => 'active',
        ]);

        $form2 = $this->factory->form->import_and_get('complete.json');

        $entry2 = $this->factory->entry->create_and_get([
            'form_id' => $form2['id'],
            'status'  => 'active',
            '16'      => 'hello',
        ]);

        $atts = [
            'view_id'  => $view->ID,
            'entry_id' => $entry2['id'],
        ];

        $this->assertEmpty($gventry->callback($atts));

        wp_update_post(['ID' => $view->ID, 'post_password' => '123']);

        $atts = [
            'view_id'  => $view->ID,
            'entry_id' => $entry['id'],
        ];

        $this->assertContains('password', $gventry->callback($atts));
    }
}
