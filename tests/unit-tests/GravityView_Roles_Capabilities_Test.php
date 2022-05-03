<?php

defined('DOING_GRAVITYVIEW_TESTS') || exit;

/**
 * @group roles
 * @group capabilities
 * @group caps
 */
class GravityView_Roles_Capabilities_Test extends GV_UnitTestCase
{
    public $default_roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];

    /**
     * @covers GravityView_Roles_Capabilities::get_instance
     */
    public function test_get_instance()
    {
        $Roles_Caps = GravityView_Roles_Capabilities::get_instance();

        $this->assertTrue(is_a($Roles_Caps, 'GravityView_Roles_Capabilities'));
    }

    /**
     * @covers GravityView_Roles_Capabilities::merge_with_all_caps
     */
    public function test_merge_with_all_caps()
    {
        $new_caps = [
            'example',
            'example2',
        ];

        $all_caps = GravityView_Roles_Capabilities::all_caps();

        $merged_caps = GravityView_Roles_Capabilities::merge_with_all_caps($new_caps);

        $this->assertEquals(array_merge($new_caps, GravityView_Roles_Capabilities::all_caps()), $merged_caps);

        $merged_caps_all_caps = GravityView_Roles_Capabilities::merge_with_all_caps($all_caps);

        // Check that array_unique works properly
        $this->assertEquals(GravityView_Roles_Capabilities::all_caps(), $merged_caps_all_caps);
    }

    /**
     * @covers GravityView_Roles_Capabilities::all_caps
     */
    public function test_all_caps()
    {
        $all_roles_array = GravityView_Roles_Capabilities::all_caps(false, false);

        foreach (['all', 'administrator', 'editor', 'author', 'contributor', 'subscriber'] as $role) {
            ${"$role"} = GravityView_Roles_Capabilities::all_caps($role);
        }

        $this->assertEquals($subscriber, $all_roles_array['subscriber']);
        $this->assertEquals($contributor, $all_roles_array['contributor']);
        $this->assertEquals($author, $all_roles_array['author']);
        $this->assertEquals($editor, $all_roles_array['editor']);
        $this->assertEquals($administrator, $all_roles_array['administrator']);
        $this->assertEquals($administrator, $all);
    }

    /**
     * @covers GravityView_Roles_Capabilities::maybe_add_full_access_caps
     */
    public function test_maybe_add_full_access_caps()
    {

        // Add GV global
        $gv_cap = ['gravityview_edit_settings'];
        $merged_gv = GravityView_Roles_Capabilities::maybe_add_full_access_caps($gv_cap);
        $this->assertEquals(['gravityview_edit_settings', 'gravityview_full_access'], $merged_gv);

        // Add GF global
        $gf_cap = ['gravityforms_edit_entries'];
        $merged_gf = GravityView_Roles_Capabilities::maybe_add_full_access_caps($gf_cap);
        $this->assertEquals(['gravityforms_edit_entries', 'gform_full_access'], $merged_gf);

        // Don't dupe
        $gv_full_cap = ['gform_full_access'];
        $merged_gv_full = GravityView_Roles_Capabilities::maybe_add_full_access_caps($gv_full_cap);
        $this->assertEquals($gv_full_cap, $merged_gv_full);
    }

    /**
     * @covers GravityView_Roles_Capabilities::has_cap
     * @covers GVCommon::has_cap
     */
    public function test_has_cap_cap_parameter()
    {
        add_filter('gravityview/security/require_unfiltered_html', '__return_false');

        foreach ($this->default_roles as $role) {

            // Create a user with the default roles
            $user = $this->factory->user->create_and_set(['role' => $role]);

            $this->assertNotEmpty($user->ID);

            // Get all the caps for that role
            $role_caps = GravityView_Roles_Capabilities::all_caps($role);

            // Make sure that the roles have each of the caps they should have
            foreach ($role_caps as $cap) {
                $this->assertTrue(GravityView_Roles_Capabilities::has_cap($cap), "Checking {$role} for {$cap} capability");
            }
        }

        remove_filter('gravityview/security/require_unfiltered_html', '__return_false');
    }

    public function authorless_view_statuses()
    {
        return [['draft'], ['private'], ['publish']];
    }

    /**
     * @ticket 27020
     * @dataProvider authorless_view_statuses
     */
    public function test_authorless_view($status)
    {
        // Make a post without an author
        $post = $this->factory->view->create(['post_author' => 0, 'post_type' => 'gravityview', 'post_status' => $status]);

        // Add an editor and contributor
        $editor = $this->factory->user->create_and_get(['role' => 'editor']);
        $contributor = $this->factory->user->create_and_get(['role' => 'contributor']);

        // editor can edit only drafts (no unfiltered cap), view, and trash
        $this->assertFalse($editor->has_cap('edit_gravityview', $post));
        $this->assertTrue($editor->has_cap('delete_gravityview', $post));
        $this->assertEquals($status !== 'draft', $editor->has_cap('read_gravityview', $post));

        // a contributor cannot (except read a published post)
        $this->assertFalse($contributor->has_cap('edit_gravityview', $post));
        $this->assertFalse($contributor->has_cap('delete_gravityview', $post));
        $this->assertEquals($status === 'publish', $contributor->has_cap('read_gravityview', $post));
    }

    /**
     * Test using the third $user->ID parameter for has_cap(), which checks against a provided $user_id
     * We create a user with no caps, check.
     *
     * @covers GravityView_Roles_Capabilities::has_cap
     * @covers GVCommon::has_cap
     */
    public function test_has_cap_user_id_parameter()
    {

        // Create a user with no capabilities
        $zero = $this->factory->user->create_and_set([
            'user_login' => 'zero',
            'role'       => 'zero',
        ]);

        $this->assertEquals($zero, wp_get_current_user());

        add_filter('gravityview/security/require_unfiltered_html', '__return_false');

        foreach ($this->default_roles as $role) {
            $user_id = $this->factory->user->create([
                'user_login' => $role,
                'role'       => $role,
            ]);

            $role_caps = GravityView_Roles_Capabilities::all_caps($role);

            foreach ($role_caps as $cap) {
                $this->assertTrue(GravityView_Roles_Capabilities::has_cap($cap, null, $user_id), "Checking {$role} for {$cap} capability with user #{$user_id}");
            }

            $this->assertEquals($zero, wp_get_current_user());
        }

        remove_filter('gravityview/security/require_unfiltered_html', '__return_false');

        $this->assertEquals($zero, wp_get_current_user());
    }

    /**
     * Test global gravityview_full_access permissions using the
     * We create a user with no caps, check.
     *
     * @covers GravityView_Roles_Capabilities::has_cap
     * @covers GVCommon::has_cap
     */
    public function test_has_cap_gravityview_full_access()
    {

        // Create a user with no capabilities
        $zero = $this->factory->user->create_and_set([
            'user_login' => 'zero',
            'role'       => 'zero',
        ]);

        $role_caps = GravityView_Roles_Capabilities::all_caps('all');

        // Zero can't access anything by default
        foreach ($role_caps as $cap) {
            $this->assertFalse(GravityView_Roles_Capabilities::has_cap($cap));
        }

        $zero->add_cap('gravityview_full_access');
        $zero->get_role_caps(); // WordPress 4.2 and lower need this to refresh caps

        // With GV full access, $zero is a $hero
        foreach ($role_caps as $cap) {
            $this->assertTrue(GravityView_Roles_Capabilities::has_cap($cap));
        }
    }

    /**
     * @covers GravityView_Roles_Capabilities::has_cap
     * @covers GVCommon::has_cap
     * @group metacaps
     */
    public function test_has_cap_single_post_cap()
    {
        $admin_id = $this->factory->user->create([
            'user_login' => 'administrator',
            'role'       => 'administrator',
        ]);

        // Create a user with no capabilities
        $zero = $this->factory->user->create_and_set([
            'user_login' => 'zero',
            'role'       => 'zero',
        ]);

        $admin_view_id = $this->factory->view->create(['post_author' => $admin_id]);
        $admin_private_view_id = $this->factory->view->create(['post_author' => $admin_id, 'post_status' => 'private']);
        $this->assertTrue(!empty($admin_view_id));

        $zero_view_id = $this->factory->view->create(['post_author' => $zero->ID]);
        $this->assertTrue(!empty($zero_view_id));

        $this->assertFalse(GravityView_Roles_Capabilities::has_cap(['edit_others_gravityviews', 'edit_gravityviews']));

        // Can't edit own View
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('edit_gravityview', $zero_view_id));

        // Can't edit others' View
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('edit_gravityview', $admin_view_id));
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('edit_gravityview', $admin_private_view_id));

        $zero->add_cap('edit_gravityviews');
        $zero->add_cap('edit_published_gravityviews');
        $zero->get_role_caps(); // WordPress 4.2 and lower need this to refresh caps

        // Can't edit own view without unfilted_html
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('edit_gravityview', $zero_view_id));

        $zero->add_cap('unfiltered_html');
        $zero->get_role_caps(); // WordPress 4.2 and lower need this to refresh caps

        $this->assertTrue(GravityView_Roles_Capabilities::has_cap('edit_gravityview', $zero_view_id));

        // Still can't edit others' View
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('edit_gravityview', $admin_view_id));

        $zero->add_cap('edit_others_gravityviews');
        $zero->get_role_caps(); // WordPress 4.2 and lower need this to refresh caps

        // CAN edit others' View
        $this->assertTrue(GravityView_Roles_Capabilities::has_cap('edit_gravityview', $admin_view_id));

        // Still can't edit other's PRIVATE View
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('edit_gravityview', $admin_private_view_id));

        $zero->add_cap('edit_private_gravityviews');
        $zero->get_role_caps(); // WordPress 4.2 and lower need this to refresh caps

        // And now user can edit other's PRIVATE View
        $this->assertTrue(GravityView_Roles_Capabilities::has_cap('edit_gravityview', $admin_private_view_id));

        //##
        //## RESET $zero
        //##
        $zero->remove_all_caps();

        $zero->add_cap('gravityview_full_access');
        $zero->get_role_caps(); // WordPress 4.2 and lower need this to refresh caps

        // With GV full access, $zero is a $hero
        $this->assertTrue(GravityView_Roles_Capabilities::has_cap('edit_gravityview', $admin_private_view_id));
    }

    public function test_non_logged_in_override()
    {
        // Create a user with no capabilities
        $zero = $this->factory->user->create_and_set([
            'user_login' => 'zero',
            'role'       => 'zero',
        ]);

        $this->assertTrue(is_user_logged_in());
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('gv_custom_test_cap'));
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('gv_custom_test_nocap'));

        $has_cap = function ($caps) {
            $caps['gv_custom_test_cap'] = true;

            return $caps;
        };
        add_filter('user_has_cap', $has_cap);

        $this->assertTrue(GravityView_Roles_Capabilities::has_cap('gv_custom_test_cap'));
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('gv_custom_test_nocap'));

        wp_set_current_user(0);

        $this->assertFalse(is_user_logged_in());

        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('gv_custom_test_cap'));
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('gv_custom_test_nocap'));

        $allow = function ($login) {
            return true;
        };

        add_filter('gravityview/capabilities/allow_logged_out', $allow);

        $this->assertTrue(GravityView_Roles_Capabilities::has_cap('gv_custom_test_cap'));
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('gv_custom_test_nocap'));
        $this->assertTrue(GravityView_Roles_Capabilities::has_cap(['gv_custom_test_cap', 'gv_custom_test_nocap']));
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap(['gv_custom_test_nocap', 'gv_custom_test_nocap_two']));
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('gravityview_full_access'));

        remove_filter('user_has_cap', $has_cap);

        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('gv_custom_test_cap'));
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('gv_custom_test_nocap'));
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap(['gv_custom_test_cap', 'gv_custom_test_nocap']));
        $this->assertTrue(GravityView_Roles_Capabilities::has_cap(['gravityview_edit_others_entries', 'gv_custom_test_nocap']));
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('gravityview_full_access'));

        remove_filter('gravityview/capabilities/allow_logged_out', $allow);

        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('gv_custom_test_cap'));
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('gv_custom_test_nocap'));
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap(['gravityview_edit_others_entries', 'gv_custom_test_nocap']));
        $this->assertFalse(GravityView_Roles_Capabilities::has_cap('gravityview_full_access'));
    }
}
