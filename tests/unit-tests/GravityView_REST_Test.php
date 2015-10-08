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
 * @since 1.14.4
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
	 *
	 * @since 1.14.4
	 *
	 * @covers GravityView_REST_Util::get_namespace()
	 */
	public function test_namespace() {
		$this->assertEquals( 'gravity-view/v1', GravityView_REST_Util::get_namespace() );
	}

	/**
	 * Test that main namespace routes exist
	 *
	 * @since 1.14.4
	 * @covers GravityView_Plugin::boot_api((
	 * @covers GravityView_REST_Route::register_routes()
	 */
	public function test_namespace_exists(){

		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/' . GravityView_REST_Util::get_namespace(), $routes );
	}

	/**
	 * Test that view routes exist
	 *
	 * @since 1.14.4
	 * @covers GravityView_Plugin::boot_api()
	 * @covers GravityView_REST_Views_Route::register_routes()
	 * @covers GravityView_REST_Route::register_routes()
	 */
	public function test_view_routes_exists(){
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/' . GravityView_REST_Util::get_namespace() . '/views', $routes );
		$this->assertArrayHasKey( '/' . GravityView_REST_Util::get_namespace() . '/views/(?P<id>[\d]+)/entries', $routes );

		$this->assertArrayHasKey( '/' . GravityView_REST_Util::get_namespace() . '/views/(?P<id>[\d]+)/entries/(?P<s_id>[\d]+)', $routes );


	}

	/**
	 * Test that entry routes exist
	 *
	 * @since 1.14.4
	 * @covers GravityView_Plugin::boot_api()
	 * @covers GravityView_REST_Entries_Route::register_routes()
	 * @covers GravityView_REST_Route::register_routes()
	 */
	public function test_entries_routes_exists(){
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/' . GravityView_REST_Util::get_namespace() . '/entries', $routes );
		$this->assertArrayHasKey( '/' . GravityView_REST_Util::get_namespace() . '/entries/(?P<id>[\d]+)', $routes );
		$this->assertArrayHasKey( '/' . GravityView_REST_Util::get_namespace() . '/entries/(?P<id>[\d]+)/field', $routes );

		$this->assertArrayHasKey( '/' . GravityView_REST_Util::get_namespace() . '/entries/(?P<id>[\d]+)/field/(?P<s_id>[\d]+)', $routes );


	}

}
