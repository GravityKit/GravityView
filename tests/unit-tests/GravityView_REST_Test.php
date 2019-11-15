<?php

if ( ! class_exists( 'GV_RESTUnitTestCase' ) ) {
	return;
}

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
		$this->assertArrayHasKey( '/' . \GV\REST\Core::get_namespace() . '/views/(?P<id>[\d]+)/entries(?:\.(?P<format>html|json|csv))?', $routes );
		$this->assertArrayHasKey( '/' . \GV\REST\Core::get_namespace() . '/views/(?P<id>[\d]+)/entries/(?P<s_id>[\w-]+)(?:\.(?P<format>html|json))?', $routes );
	}

	public function test_context_param() {
		$request  = new WP_REST_Request( 'OPTIONS', '/gravityview/v1/views' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );
		$data     = $response->get_data();

		$this->assertEquals( array( 'page', 'limit', 'post_id' ), array_keys( $data['endpoints'][0]['args'] ) );

		$form = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form['id'],
			'1' => 'set all the fields!',
			'2' => -100,
		) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$request  = new WP_REST_Request( 'OPTIONS', '/gravityview/v1/views/' . $view->ID );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );
		$data     = $response->get_data();

		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );

		$request  = new WP_REST_Request( 'OPTIONS', '/gravityview/v1/views/' . $view->ID . '/entries' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );
		$data     = $response->get_data();

		$this->assertEquals( array( 'page', 'limit', 'post_id' ), array_keys( $data['endpoints'][0]['args'] ) );

		$request  = new WP_REST_Request( 'OPTIONS', '/gravityview/v1/views/' . $view->ID . '/entries/' . $entry['id'] );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );
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
			'limit' => 1,
			'page' => 1,
		) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$views = $response->get_data();
		$this->assertCount( 1, $views['views'] );
		$this->assertEquals( 3, $views['total'] );
		$this->assertEquals( $view3->ID, $views['views'][0]['ID'] );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views' );
		$request->set_query_params( array(
			'limit' => 2,
			'page' => 2,
		) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$views = $response->get_data();
		$this->assertCount( 1, $views['views'] );
		$this->assertEquals( 3, $views['total'] );
		$this->assertEquals( $view->ID, $views['views'][0]['ID'] );

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

		add_filter( 'gravityview/rest/entry/fields', $callback = function( $allowed ) {
			$allowed[] = 'ip';
			return $allowed;
		} );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries' );
		$request->set_query_params( array(
			'limit' => 1,
			'page' => 1,
		) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		remove_filter( 'gravityview/rest/entry/fields', $callback );

		$entries = $response->get_data();
		$this->assertCount( 1, $entries['entries'] );
		$this->assertEquals( 3, $entries['total'] );
		$this->assertEquals( $entry2['id'], $entries['entries'][0]['id'] );
		$this->assertEqualSets( array( 'id', 1, 'ip' ), array_keys( $entries['entries'][0] ) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries.json' );
		$request->set_query_params( array(
			'limit' => 2,
			'page' => 2,
		) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$entries = $response->get_data();
		$this->assertCount( 1, $entries['entries'] );
		$this->assertEquals( 3, $entries['total'] );
		$this->assertEquals( $entry['id'], $entries['entries'][0]['id'] );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries.html' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );
		$this->assertEquals( 3, $response->headers['X-Item-Count'] );

		$html = $response->get_data();
		$this->assertContains( '<meta http-equiv="X-Item-Count" content="3" />', $html );
		$this->assertContains( 'gv-table-view', $html );
		$this->assertContains( 'set all the fields!', $html );
		$this->assertContains( 'set all the fields! 1', $html );
		$this->assertContains( 'set all the fields! 2', $html );

		$this->assertTrue( add_filter( 'gravityview/rest/entries/html/insert_meta', '__return_false' ) );
		$response = rest_get_server()->dispatch( $request );
		$html = $response->get_data();
		$this->assertNotContains( '<meta http-equiv="X-Item-Count"', $html );
		$this->assertTrue( remove_filter( 'gravityview/rest/entries/html/insert_meta', '__return_false' ) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries.html' );
		$request->set_query_params( array(
			'page' => 99,
		) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );
		$this->assertEquals( 0, $response->headers['X-Item-Count'] );

		$html = $response->get_data();
		$this->assertContains( '<meta http-equiv="X-Item-Count" content="0" />', $html );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries.csv' );
		ob_start(); // CSV binary data is output ad hoc
		$response = rest_get_server()->dispatch( $request );
		$csv = ob_get_clean();
		$this->assertEquals( 200, $response->status );
		$this->assertEquals( 3, $response->headers['X-Item-Count'] );
		$this->assertEquals( 'text/csv', $response->headers['Content-Type'] );

		$this->assertStringStartsWith( chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ), $csv );
		$this->assertContains( $entry2['id'] . ',"set all the fields! 2"', $csv );
	}

	public function test_get_items_csv_complex() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		// Views
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Order ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Item',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '8',
						'label' => 'Customer Name',
					),
				),
			),
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'A pair of shoes',
			'8.3' => 'Winston',
			'8.6' => 'Potter',
		) );

		$entry2 = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => '=Broomsticks x 8',
			'8.3' => 'Harry',
			'8.6' => 'Churchill',
		) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries.csv' );
		ob_start(); // CSV binary data is output ad hoc
		$response = rest_get_server()->dispatch( $request );
		$csv = ob_get_clean();
		$this->assertEquals( 200, $response->status );
		$this->assertEquals( 2, $response->headers['X-Item-Count'] );

		$this->assertStringStartsWith( chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ), $csv );
		$this->assertContains( 'id,16,8', $csv );
		$this->assertContains( $entry2['id'] . ',"\'=Broomsticks x 8","Harry Churchill"', $csv );
		$this->assertContains( $entry['id'] . ',"A pair of shoes","Winston Potter"', $csv );
		$this->assertStringEndsWith( '"', $csv );
	}

	public function test_get_items_custom_content() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'The ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'The ID again, just because',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'label' => 'C1',
						'content' => 'hello',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'label' => 'C2',
						'content' => 'world',
					),
				),
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'The ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'The ID again, just because',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'label' => 'C1',
						'content' => 'hello',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'label' => 'C2',
						'content' => 'world',
					),
				),
			),
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'expect the unexpected',
		) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries.json' );
		$response = rest_get_server()->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( array(
			'id'        => $entry['id'],
			'id(2)'     => $entry['id'],
			'custom'    => 'hello',
			'custom(2)' => 'world',
		), current( $data['entries'] ) );

		add_filter( 'gravityview/api/field/key', $callback = function( $key ) {
			return str_replace( array( '(', ')' ), '/', $key );
		} );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries/' . $entry['id'] );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$this->assertEquals( array(
			'id'        => $entry['id'],
			'id/2/'     => $entry['id'],
			'custom'    => 'hello',
			'custom/2/' => 'world',
		), $response->get_data() );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries.csv' );
		ob_start(); // CSV binary data is output ad hoc
		$response = rest_get_server()->dispatch( $request );
		$csv = ob_get_clean();

		$this->assertContains( 'id,id/2/,custom,custom/2/', $csv );
		$this->assertContains( "{$entry['id']},{$entry['id']},hello,world", $csv );

		remove_filter( 'gravityview/api/field/key', $callback );
	}

	public function test_get_entries_filter() {
		$form = $this->factory->form->create_and_get();

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
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"search_all","input":"input_text"}]',
					),
				),
			),
		) );

		// Entries
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form['id'],
			'1' => 'world',
			'2' => -100,
		) );
		$entry1 = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form['id'],
			'1' => 'hello world',
			'2' => -100,
		) );
		$entry2 = $this->factory->entry->import_and_get( 'simple_entry.json', array(
			'form_id' => $form['id'],
			'1' => 'hello',
			'2' => -100,
		) );

		$_GET['filter_1'] = 'hello';

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$entries = $response->get_data();
		$this->assertCount( 1, $entries['entries'] );
		$this->assertEquals( 1, $entries['total'] );
		$this->assertEquals( $entry2['id'], $entries['entries'][0]['id'] );
	}

	public function test_get_item() {
		$form = $this->factory->form->create_and_get();

		// Views
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Text',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'content' => 'Hello, world!',
						'label' => 'Custom',
					),
				),
			),
		) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$data = $response->get_data();
		$this->assertEquals( $view->ID, $data['ID'] );

		// Entry
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

		add_filter( 'gravityview/rest/entry/fields', $callback = function( $allowed ) {
			$allowed[] = 'ip';
			return $allowed;
		} );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries/' . $entry['id'] );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$entry = $response->get_data();
		$this->assertCount( 4, $entry );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries/' . $entry['id'] . '.html' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$html = $response->get_data();
		$this->assertContains( 'gv-table-view', $html );
		$this->assertContains( 'set all the fields!', $html );

		remove_filter( 'gravityview/rest/entry/fields', $callback );
	}

	public function test_get_security() {
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
			'settings' => array( 'show_only_approved' => true ),
		) );
		$view2 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view3 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view4 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view5 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view6 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view7 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		// These should not be seen by regular users
		wp_update_post( array( 'ID' => $view4->ID, 'post_password' => '123' ) );
		wp_update_post( array( 'ID' => $view5->ID, 'post_status' => 'private' ) );
		wp_update_post( array( 'ID' => $view6->ID, 'post_status' => 'trash' ) );
		wp_update_post( array( 'ID' => $view7->ID, 'post_status' => 'draft' ) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views' );
		$request->set_query_params( array(
			'limit' => 1,
			'page' => 1,
		) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$views = $response->get_data();
		$this->assertCount( 1, $views['views'] );
		$this->assertEquals( 4, $views['total'] );
		$this->assertEquals( $view4->ID, $views['views'][0]['ID'] );
		$this->assertCount( 2, $views['views'][0] ); // No details on password protected post

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

		gform_update_meta( $entry2['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view6->ID . '/entries' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 500, $response->status );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$entries = $response->get_data();
		$this->assertCount( 1, $entries['entries'] );
		$this->assertEquals( 1, $entries['total'] );
		$this->assertEquals( $entry2['id'], $entries['entries'][0]['id'] );

		// Entry
		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries/' . $entry2['id'] );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries/' . $entry1['id'] );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 500, $response->status );

		// View

		// Password protected
		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view4->ID );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 500, $response->status );

		// Protected
		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view5->ID );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 500, $response->status );

		// Trashed
		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view6->ID );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 500, $response->status );

		// Draft
		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view7->ID );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 500, $response->status );

		$view8 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		// REST disabled for View
		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view8->ID );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		add_filter( 'gravityview/view/output/rest', '__return_false' );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 500, $response->status );

		remove_filter( 'gravityview/view/output/rest', '__return_false' );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$view9 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'], 'settings' => array( 'rest_disable' => '1' ) ) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view9->ID );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 500, $response->status );

		$view10 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'], 'settings' => array( 'rest_disable' => '0' ) ) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view10->ID );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		/** Enable the REST API */
		gravityview()->plugin->settings->set( array( 'rest_api' => '0' ) );
		add_action( 'gravityview/settings/defaults', $callback = function( $defaults ) {
			$defaults['rest_api'] = '0';
			return $defaults;
		} );
		$view11 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'], 'settings' => array( 'rest_disable' => '0' ) ) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view11->ID );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 500, $response->status );

		$view12 = $this->factory->view->create_and_get( array( 'form_id' => $form['id'], 'settings' => array( 'rest_enable' => '1' ) ) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view12->ID );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		remove_action( 'gravityview/settings/defaults', $callback );
	}

	public function test_get_information_disclosure() {
		$user_id = $this->factory->user->create( array(
			'role' => 'administrator',
		) );

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
			'settings' => array( 'show_only_approved' => true ),
		) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views' );
		$request->set_query_params( array(
			'limit' => 1,
			'page' => 1,
		) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$views = $response->get_data();
		$this->assertCount( 1, $views['views'] );
		$this->assertEquals( 1, $views['total'] );
		$this->assertEquals( $view->ID, $views['views'][0]['ID'] );

		$this->assertNotContains( 'settings', array_keys( $views['views'][0] ) );
		$this->assertNotContains( 'form', array_keys( $views['views'][0] ) );
		$this->assertNotContains( 'search_criteria', array_keys( $views['views'][0] ) );

		wp_set_current_user( $user_id );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$views = $response->get_data();
		$this->assertCount( 1, $views['views'] );
		$this->assertEquals( 1, $views['total'] );
		$this->assertEquals( $view->ID, $views['views'][0]['ID'] );

		$this->assertContains( 'settings', array_keys( $views['views'][0] ) );
		$this->assertContains( 'form', array_keys( $views['views'][0] ) );
		$this->assertContains( 'search_criteria', array_keys( $views['views'][0] ) );

		wp_set_current_user( 0 );
	}

	public function test_get_items_csv_raw() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		// Views
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '4',
						'label' => 'Email',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '7',
						'label' => 'A List',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '5',
						'label' => 'File',
					),
				),
			),
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'4' => 'support@gravityview.co',
			'7' => serialize( array(
				array( 'Column 1' => 'one', 'Column 2' => 'two' ),
				array( 'Column 1' => 'three', 'Column 2' => 'four' ),
			) ),
			'5' => json_encode( array(
				'http://one.jpg',
				'http://two.mp3',
			) ),
		) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries.csv' );
		ob_start(); // CSV binary data is output ad hoc
		$response = rest_get_server()->dispatch( $request );
		$csv = ob_get_clean();
		$this->assertEquals( 200, $response->status );
		$this->assertEquals( 1, $response->headers['X-Item-Count'] );

		$this->assertStringStartsWith( chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ), $csv );
		$this->assertContains( 'one,two', $csv );
		$this->assertNotContains( '<', $csv );
		$this->assertNotContains( '[', $csv );
		$this->assertContains( 'one.jpg', $csv );
		$this->assertContains( 'two.mp3', $csv );
	}

	public function test_get_items_raw() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		// Views
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '4',
						'label' => 'Email',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '7',
						'label' => 'A List',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '5',
						'label' => 'File',
					),
				),
			),
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'4' => 'support@gravityview.co',
			'7' => serialize( array(
				array( 'Column 1' => 'one', 'Column 2' => 'two' ),
				array( 'Column 1' => 'three', 'Column 2' => 'four' ),
			) ),
			'5' => json_encode( array(
				'http://one.jpg',
				'http://two.mp3',
			) ),
		) );

		$request  = new WP_REST_Request( 'GET', '/gravityview/v1/views/' . $view->ID . '/entries/' . $entry['id'] );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();

		$this->assertEquals( 'support@gravityview.co', $data[4] );
		$this->assertEquals( array(
			array( 'Column 1' => 'one', 'Column 2' => 'two' ),
			array( 'Column 1' => 'three', 'Column 2' => 'four' ),
		), $data[7] );
		$this->assertEquals( array(
			'http://one.jpg',
			'http://two.mp3',
		), $data[5] );
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
