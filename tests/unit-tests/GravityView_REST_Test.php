<?php
/**
 * Test for the GV REST API
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Josh Pollock <josh@joshpress.net>
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 2.0
 *
 * @group rest
 */
class GravityView_REST_Test extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server;
		do_action( 'rest_api_init');
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Test that namespace is set properly
	 * @since 2.0
	 */
	public function test_namespace() {
		$this->assertEquals( 'gravityview/v1', \GV\REST\Core::get_namespace() );
	}

	/**
	 * Test that main namespace routes exist
	 * @since 2.0
	 */
	public function test_namespace_exists() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/' . \GV\REST\Core::get_namespace(), $routes );
	}

	/**
	 * Test that view routes exist
	 * @since 2.0
	 */
	public function test_view_routes_exists() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/' . \GV\REST\Core::get_namespace() . '/views', $routes );
		$this->assertArrayHasKey( '/' . \GV\REST\Core::get_namespace() . '/views/(?P<id>[\d]+)/entries', $routes );
		$this->assertArrayHasKey( '/' . \GV\REST\Core::get_namespace() . '/views/(?P<id>[\d]+)/entries/(?P<s_id>[\w-]+)', $routes );
	}
}
