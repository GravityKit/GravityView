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
class GravityView_REST_Test extends GV_RESTUnitTestCase {
	public function test_register_routes() {
		$this->assertEquals( 'gravityview/v1', \GV\REST\Core::get_namespace() );

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/' . \GV\REST\Core::get_namespace(), $routes );
		$this->assertArrayHasKey( '/' . \GV\REST\Core::get_namespace() . '/views', $routes );
		$this->assertArrayHasKey( '/' . \GV\REST\Core::get_namespace() . '/views/(?P<id>[\d]+)', $routes );
		$this->assertArrayHasKey( '/' . \GV\REST\Core::get_namespace() . '/views/(?P<id>[\d]+)/entries(?:\.(?P<format>html|json))?', $routes );
		$this->assertArrayHasKey( '/' . \GV\REST\Core::get_namespace() . '/views/(?P<id>[\d]+)/entries/(?P<s_id>[\w-]+)(?:\.(?P<format>html|json))?', $routes );
	}

	public function test_context_param() {
		$request  = new WP_REST_Request( 'OPTIONS', '/gravityview/v1/views' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( array( 'paging[current_page]', 'paging[page_size]' ), array_keys( $data['endpoints'][0]['args'] ) );

		$form = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form['id'],
			'1' => 'set all the fields!',
			'2' => -100,
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$request  = new WP_REST_Request( 'OPTIONS', '/gravityview/v1/views/' . $view->ID );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );

		$request  = new WP_REST_Request( 'OPTIONS', '/gravityview/v1/views/' . $view->ID . '/entries' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( array( 'paging[current_page]', 'paging[page_size]' ), array_keys( $data['endpoints'][0]['args'] ) );

		$request  = new WP_REST_Request( 'OPTIONS', '/gravityview/v1/views/' . $view->ID . '/entries/' . $entry['id'] );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
	}

	public function test_get_items() {
		$form = $this->factory->form->create_and_get();

		// Views
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Text',
					),
				),
			),
		) );
		$view2 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view3 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views' );
		$request->set_query_params( array(
			'paging[page_size]' => 1,
			'paging[current_page]' => 1,
		) );
		$response = rest_get_server()->dispatch( $request );

		$views = $response->get_data();
		$this->assertCount( 1, $views );
		$this->assertEquals( $view3->ID, $views[0]['ID'] );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views' );
		$request->set_query_params( array(
			'paging[page_size]' => 2,
			'paging[current_page]' => 2,
		) );
		$response = rest_get_server()->dispatch( $request );

		$views = $response->get_data();
		$this->assertCount( 1, $views );
		$this->assertEquals( $view->ID, $views[0]['ID'] );

		// Entries
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form['id'],
			'1' => 'set all the fields!',
			'2' => -100,
		) );
		$entry1 = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form['id'],
			'1' => 'set all the fields! 1',
			'2' => -100,
		) );
		$entry2 = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form['id'],
			'1' => 'set all the fields! 2',
			'2' => -100,
		) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries' );
		$request->set_query_params( array(
			'paging[page_size]' => 1,
			'paging[current_page]' => 1,
		) );
		$response = rest_get_server()->dispatch( $request );

		$entries = $response->get_data();
		$this->assertCount( 1, $entries['entries'] );
		$this->assertEquals( $entry2['id'], $entries['entries'][0]['id'] );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries.json' );
		$request->set_query_params( array(
			'paging[page_size]' => 2,
			'paging[current_page]' => 2,
		) );
		$response = rest_get_server()->dispatch( $request );

		$entries = $response->get_data();
		$this->assertCount( 1, $entries );
		$this->assertEquals( $entry['id'], $entries['entries'][0]['id'] );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries.html' );
		$response = rest_get_server()->dispatch( $request );

		$html = $response->get_data();
		$this->assertContains( 'gv-table-view', $html );
		$this->assertContains( 'set all the fields!', $html );
		$this->assertContains( 'set all the fields! 1', $html );
		$this->assertContains( 'set all the fields! 2', $html );
	}

	public function test_get_item() {
	}

	public function test_create_item() {
	}

	public function test_update_item() {
	}

	public function test_delete_item() {
	}

	public function test_prepare_item() {
	}

	public function test_get_item_schema() {
	}
}
