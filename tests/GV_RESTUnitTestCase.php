<?php

// Less than WP 4.7
if (!class_exists('WP_Test_REST_Controller_Testcase')) {
    return;
}

abstract class GV_RESTUnitTestCase extends WP_Test_REST_Controller_Testcase
{
    /**
     * @var GV_UnitTest_Factory
     */
    public $factory;

    /**
     * @inheritDoc
     */
    public function setUp()
    {
        parent::setUp();

        /* Remove temporary tables which causes problems with GF */
        remove_all_filters('query', 10);

        /* Ensure the database is correctly set up */
        if (function_exists('gf_upgrade')) {
            gf_upgrade()->upgrade_schema();
        }

        $this->factory = new GV_UnitTest_Factory($this);

        if (version_compare(GFForms::$version, '2.2', '<')) {
            @GFForms::setup_database();
        }
    }

    /**
     * @inheritDoc
     */
    public function tearDown()
    {
        /** @see https://core.trac.wordpress.org/ticket/29712 */
        wp_set_current_user(0);
        parent::tearDown();
    }

    /**
     * Multisite-agnostic way to delete a user from the database.
     *
     * @since 1.20 - Included in WP 4.3.0+, so we need to stub for 4.0+
     */
    public static function delete_user($user_id)
    {
        if (is_multisite()) {
            return wpmu_delete_user($user_id);
        } else {
            return wp_delete_user($user_id);
        }
    }
}
