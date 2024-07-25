<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * All join and union tests live here.
 * @group multi
 */
class GravityView_Joins_Test extends GV_UnitTestCase {
	function setUp() : void {
		$this->_reset_context();

		parent::setUp();
	}

	function tearDown() : void {
		$this->_reset_context();
	}

	/**
	 * Resets the GravityView context, both old and new.
	 */
	private function _reset_context() {
		\GV\Mocks\Legacy_Context::reset();
		gravityview()->request = new \GV\Frontend_Request();

		global $wp_query, $post;

		$wp_query = new WP_Query();
		$post = null;
		$_GET = array();

		\GV\View::_flush_cache();

		set_current_screen( 'front' );
		wp_set_current_user( 0 );
	}

	public function test_basic_table_joins() {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$customers = $this->factory->form->import_and_get( 'simple.json' );
		$orders = $this->factory->form->import_and_get( 'complete.json' );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Ann',
			'2' => 1,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Bob',
			'2' => 2,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Carol',
			'2' => 3,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Derek',
			'2' => 4,
		) );

		$entries = array();

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Shoes',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Bacon',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 2,
			'16' => 'Book',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 3,
			'16' => 'Keyboard',
		) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $orders['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $orders['id'],
						'id' => 'id',
						'label' => 'Order ID',
					),
					wp_generate_password( 4, false ) => array(
						// Test without explicit form_id set
						'id' => '16',
						'label' => 'Item',
					),
					wp_generate_password( 4, false ) => array(
						'form_id' => $customers['id'],
						'id' => '1',
						'label' => 'Customer Name',
					),
				),
			),
			'joins' => array(
				array( $orders['id'], '9', $customers['id'], '2' ),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $view );

		if ( '\GF_Patched_Query' !== $view->get_query_class() ) {
			$this->markTestSkipped( 'Requires \GF_Patched_Query' );
		}

		$renderer = new \GV\View_Renderer();

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$out = $renderer->render( $view );
		$result = preg_replace( '/\s+/', '', wp_strip_all_tags( $out ) );

		$expected = array(
			'OrderIDItemCustomerName',
			$entries[3]['id'], 'Keyboard', 'Carol',
			$entries[2]['id'], 'Book', 'Bob',
			$entries[1]['id'], 'Bacon', 'Derek',
			$entries[0]['id'], 'Shoes', 'Derek',
			'OrderIDItemCustomerName',
		);

		$this->assertEquals( implode( '', $expected ), $result );
	}

	public function test_basic_list_joins() {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$customers = $this->factory->form->import_and_get( 'simple.json' );
		$orders = $this->factory->form->import_and_get( 'complete.json' );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Ann',
			'2' => 1,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Bob',
			'2' => 2,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Carol',
			'2' => 3,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Derek',
			'2' => 4,
		) );

		$entries = array();

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Shoes',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Bacon',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 2,
			'16' => 'Book',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 3,
			'16' => 'Keyboard',
		) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $orders['id'],
			'template_id' => 'preset_business_listings',
			'fields' => array(
				'directory_list-title' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Item',
					),
				),
				'directory_list-subtitle' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $orders['id'],
						'id' => 'id',
						'label' => 'Order ID',
					),
				),
				'directory_list-description' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $customers['id'],
						'id' => '1',
						'label' => 'Customer Name',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'custom_content',
						'content' => 'Thank you for your purchase!',
					),
				),
			),
			'joins' => array(
				array( $orders['id'], '9', $customers['id'], '2' ),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $view );

		if ( '\GF_Patched_Query' !== $view->get_query_class() ) {
			$this->markTestSkipped( 'Requires \GF_Patched_Query' );
		}

		$renderer = new \GV\View_Renderer();

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$out = $renderer->render( $view );
		$result = preg_replace( '/\s+/', '', wp_strip_all_tags( $out ) );

		$expected = array(
			'Item', 'Keyboard', 'OrderID', $entries[3]['id'], 'CustomerName', 'Carol',
			'Item', 'Book', 'OrderID', $entries[2]['id'], 'CustomerName', 'Bob',
			'Item', 'Bacon', 'OrderID', $entries[1]['id'], 'CustomerName', 'Derek',
			'Item', 'Shoes', 'OrderID', $entries[0]['id'], 'CustomerName', 'Derek',
		);

		$this->assertEquals( implode( '', $expected ), $result );
	}

	public function test_joins_with_approves() {
		add_filter('gk/gravityview/view/entries/cache', '__return_false');
		add_filter('gravityview_use_cache', '__return_false');

		$this->_reset_context();

		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$customers = $this->factory->form->import_and_get( 'simple.json' );
		$orders = $this->factory->form->import_and_get( 'complete.json' );

		$customer = $this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Ann',
			'2' => 1,
		) );

		$order = $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 1,
			'16' => 'Shoes',
		) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		global $post;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $orders['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $orders['id'],
						'id' => 'id',
						'label' => 'Order ID',
					),
					wp_generate_password( 4, false ) => array(
						// Test without explicit form_id set
						'id' => '16',
						'label' => 'Item',
					),
					wp_generate_password( 4, false ) => array(
						'form_id' => $customers['id'],
						'id' => '1',
						'label' => 'Customer Name',
					),
				),
			),
			'joins' => array(
				array( $orders['id'], '9', $customers['id'], '2' ),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		if ( '\GF_Patched_Query' !== $view->get_query_class() ) {
			$this->markTestSkipped( 'Requires \GF_Patched_Query' );
		}

		$view->settings->update( array( 'show_only_approved' => false ) );

		$entries = $view->get_entries();
		$this->assertCount( 1, $entries->all() );

		$view->settings->update( array( 'show_only_approved' => true ) );

		$entries = $view->get_entries();
		$this->assertCount( 0, $entries->all() );

		gform_update_meta( $order['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		$entries = $view->get_entries();
		$this->assertCount( 0, $entries->all() );

		gform_update_meta( $customer['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		$entries = $view->get_entries();
		$this->assertCount( 1, $entries->all() );

		gform_update_meta( $order['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::UNAPPROVED );

		$entries = $view->get_entries();
		$this->assertCount( 0, $entries->all() );

		$this->_reset_context();

		remove_all_filters( 'gk/gravityview/view/entries/cache' );
		remove_all_filters( 'gravityview_use_cache' );
	}

	public function test_legacy_template_table_joins() {
		$this->_reset_context();

		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$customers = $this->factory->form->import_and_get( 'simple.json' );
		$orders = $this->factory->form->import_and_get( 'complete.json' );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Ann',
			'2' => 1,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Bob',
			'2' => 2,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Carol',
			'2' => 3,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Derek',
			'2' => 4,
		) );

		$entries = array();

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Shoes',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Bacon',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 2,
			'16' => 'Book',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 3,
			'16' => 'Keyboard',
		) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		global $post;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $orders['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $orders['id'],
						'id' => 'id',
						'label' => 'Order ID',
					),
					wp_generate_password( 4, false ) => array(
						// Test without explicit form_id set
						'id' => '16',
						'label' => 'Item',
					),
					wp_generate_password( 4, false ) => array(
						'form_id' => $customers['id'],
						'id' => '1',
						'label' => 'Customer Name',
					),
				),
			),
			'joins' => array(
				array( $orders['id'], '9', $customers['id'], '2' ),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		if ( '\GF_Patched_Query' !== $view->get_query_class() ) {
			$this->markTestSkipped( 'Requires \GF_Patched_Query' );
		}

		$renderer = new \GV\Legacy_Override_Template( $view );

		$out = $renderer->render( 'table' );
		$result = preg_replace( '/\s+/', '', wp_strip_all_tags( $out ) );

		$expected = array(
			'OrderIDItemCustomerName',
			$entries[3]['id'], 'Keyboard', 'Carol',
			$entries[2]['id'], 'Book', 'Bob',
			$entries[1]['id'], 'Bacon', 'Derek',
			$entries[0]['id'], 'Shoes', 'Derek',
			'OrderIDItemCustomerName',
		);

		$this->assertEquals( implode( '', $expected ), $result );

		$this->_reset_context();
	}

	public function test_legacy_template_list_joins() {
		$this->_reset_context();

		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$customers = $this->factory->form->import_and_get( 'simple.json' );
		$orders = $this->factory->form->import_and_get( 'complete.json' );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Ann',
			'2' => 1,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Bob',
			'2' => 2,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Carol',
			'2' => 3,
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $customers['id'],
			'status' => 'active',
			'1' => 'Derek',
			'2' => 4,
		) );

		$entries = array();

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Shoes',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 4,
			'16' => 'Bacon',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 2,
			'16' => 'Book',
		) );

		$entries []= $this->factory->entry->create_and_get( array(
			'form_id' => $orders['id'],
			'status' => 'active',
			'9' => 3,
			'16' => 'Keyboard',
		) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		global $post;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $orders['id'],
			'template_id' => 'preset_business_listings',
			'fields' => array(
				'directory_list-title' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Item',
					),
				),
				'directory_list-subtitle' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $orders['id'],
						'id' => 'id',
						'label' => 'Order ID',
					),
				),
				'directory_list-description' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $customers['id'],
						'id' => '1',
						'label' => 'Customer Name',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'custom_content',
						'content' => 'Thank you for your purchase!',
					),
				),
			),
			'joins' => array(
				array( $orders['id'], '9', $customers['id'], '2' ),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		if ( '\GF_Patched_Query' !== $view->get_query_class() ) {
			$this->markTestSkipped( 'Requires \GF_Patched_Query' );
		}

		$renderer = new \GV\View_Renderer();

		$out = $renderer->render( 'list' );
		$result = preg_replace( '/\s+/', '', wp_strip_all_tags( $out ) );

		$expected = array(
			'Item', 'Keyboard', 'OrderID', $entries[3]['id'], 'CustomerName', 'Carol',
			'Item', 'Book', 'OrderID', $entries[2]['id'], 'CustomerName', 'Bob',
			'Item', 'Bacon', 'OrderID', $entries[1]['id'], 'CustomerName', 'Derek',
			'Item', 'Shoes', 'OrderID', $entries[0]['id'], 'CustomerName', 'Derek',
		);

		$this->assertEquals( implode( '', $expected ), $result );

		$this->_reset_context();
	}

	public function test_search_widget() {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$chefs = $this->factory->form->import_and_get( 'simple.json' );
		$souschefs = $this->factory->form->import_and_get( 'simple.json' );

		$this->factory->entry->create_and_get( array( 'form_id' => $chefs['id'], 'status' => 'active', '2' => 1, '1' => 'Maria Henry' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $chefs['id'], 'status' => 'active', '2' => 2, '1' => 'Henry Marrek' ) );

		$this->factory->entry->create_and_get( array( 'form_id' => $souschefs['id'], 'status' => 'active', '2' => 1, '1' => 'Mary Jane' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $souschefs['id'], 'status' => 'active', '2' => 1, '1' => 'Jane Henryson' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $souschefs['id'], 'status' => 'active', '2' => 2, '1' => 'Marick Bonobo' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $souschefs['id'], 'status' => 'active', '2' => 2, '1' => 'Henry Oswald' ) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $chefs['id'],
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '2',
						'label' => 'Group',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Name',
					),
				),
			),
			'joins' => array(
				array( $chefs['id'], '2', $souschefs['id'], '2' ),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"1","input":"input_text"}]',
					),
				),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		if ( '\GF_Patched_Query' !== $view->get_query_class() ) {
			$this->markTestSkipped( 'Requires \GF_Patched_Query' );
		}

		// For all unspecified cases the first form is always picked
		$_GET = array();
		$this->assertEquals( 4, $view->get_entries()->count() );

		$_GET = array( 'filter_1' => 'Henryson' );
		$this->assertEquals( 0, $view->get_entries()->count() );

		$_GET = array( 'filter_1' => 'Mari' );
		$entries = $view->get_entries()->all();
		$entries_chefs = array( $entries[0][ $chefs['id'] ]['1'], $entries[1][ $chefs['id'] ]['1'] );
		$entries_souschefs = array( $entries[0][ $souschefs['id'] ]['1'], $entries[1][ $souschefs['id'] ]['1'] );
		$this->assertCount( 2, $entries );
		$this->assertContains( 'Maria Henry', $entries_chefs );
		$this->assertContains( 'Mary Jane', $entries_souschefs );
		$this->assertContains( 'Maria Henry', $entries_chefs );
		$this->assertContains( 'Jane Henryson', $entries_souschefs );

		$_GET = array( 'filter_1:' . $chefs['id'] => 'Marr' );
		$entries = $view->get_entries()->all();
		$entries_chefs = array( $entries[0][ $chefs['id'] ]['1'], $entries[1][ $chefs['id'] ]['1'] );
		$entries_souschefs = array( $entries[0][ $souschefs['id'] ]['1'], $entries[1][ $souschefs['id'] ]['1'] );
		$this->assertCount( 2, $entries );
		$this->assertContains( 'Henry Marrek', $entries_chefs );
		$this->assertContains( 'Marick Bonobo', $entries_souschefs );
		$this->assertContains( 'Henry Marrek', $entries_chefs );
		$this->assertContains( 'Henry Oswald', $entries_souschefs );

		$_GET = array( 'filter_1:' . $souschefs['id'] => 'Marr' );
		$entries = $view->get_entries()->all();
		$this->assertCount( 4, $entries );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $chefs['id'],
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '2',
						'label' => 'Group',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Name',
					),
				),
			),
			'joins' => array(
				array( $chefs['id'], '2', $souschefs['id'], '2' ),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => sprintf( '[{"field":"1","form_id":"%d","input":"input_text"}]', $souschefs['id'] ),
					),
				),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		$_GET = array();
		$this->assertEquals( 4, $view->get_entries()->count() );

		$_GET = array( 'filter_1' => 'Henryson' );
		$this->assertEquals( 0, $view->get_entries()->count() );

		$_GET = array( 'filter_1' => 'Mari' );
		$entries = $view->get_entries()->all();
		$entries_chefs = array( $entries[0][ $chefs['id'] ]['1'], $entries[1][ $chefs['id'] ]['1'] );
		$entries_souschefs = array( $entries[0][ $souschefs['id'] ]['1'], $entries[1][ $souschefs['id'] ]['1'] );
		$this->assertContains( 'Maria Henry', $entries_chefs );
		$this->assertContains( 'Mary Jane', $entries_souschefs );
		$this->assertContains( 'Maria Henry', $entries_chefs );
		$this->assertContains( 'Jane Henryson', $entries_souschefs );

		$_GET = array( 'filter_1:' . $chefs['id'] => 'Marr' );
		$this->assertCount( 4, $view->get_entries()->all() );

		$_GET = array( 'filter_1:' . $chefs['id'] => 'Bono' );
		$this->assertCount( 4, $view->get_entries()->all() );

		$_GET = array( 'filter_1:' . $souschefs['id'] => 'Bono' );
		$entries = $view->get_entries()->all();
		$this->assertCount( 1, $entries );
		$this->assertEquals( 'Henry Marrek', $entries[0][ $chefs['id'] ]['1'] );
		$this->assertEquals( 'Marick Bonobo', $entries[0][ $souschefs['id'] ]['1'] );
	}

	public function test_search_widget_global_search( $mode = 'any' ) {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$chefs = $this->factory->form->import_and_get( 'simple.json' );
		$souschefs = $this->factory->form->import_and_get( 'simple.json' );

		$this->factory->entry->create_and_get( array( 'form_id' => $chefs['id'], 'status' => 'active', '2' => 1, '1' => 'Maria Henry' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $chefs['id'], 'status' => 'active', '2' => 2, '1' => 'Henry Marrek' ) );

		$this->factory->entry->create_and_get( array( 'form_id' => $souschefs['id'], 'status' => 'active', '2' => 1, '1' => 'Mary Jane' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $souschefs['id'], 'status' => 'active', '2' => 1, '1' => 'Jane Henryson' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $souschefs['id'], 'status' => 'active', '2' => 2, '1' => 'Marick Bonobo' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $souschefs['id'], 'status' => 'active', '2' => 2, '1' => 'Henry Oswald' ) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $chefs['id'],
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '2',
						'label' => 'Group',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Name',
					),
				),
			),
			'joins' => array(
				array( $chefs['id'], '2', $souschefs['id'], '2' ),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"search_all","input":"input_text"}]',
						'search_mode' => $mode,
					),
				),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		if ( '\GF_Patched_Query' !== $view->get_query_class() ) {
			$this->markTestSkipped( 'Requires \GF_Patched_Query' );
		}

		$_GET = array( 'gv_search' => 'en', 'mode' => $mode );
		$this->assertEquals( 4, $view->get_entries()->count() );

		$_GET = array( 'gv_search' => 'rick', 'mode' => $mode );
		$this->assertEquals( 1, $view->get_entries()->count() );
	}

	public function test_search_widget_global_search_all() {
		return $this->test_search_widget_global_search( 'all' );
	}

	/**
	 * https://github.com/gravityview/Multiple-Forms/issues/38
	 */
	public function test_single_entry_join_permissions() {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$chefs = $this->factory->form->import_and_get( 'simple.json' );
		$souschefs = $this->factory->form->import_and_get( 'simple.json' );

		$none = $this->factory->form->import_and_get( 'simple.json' );

		$c1 = $this->factory->entry->create_and_get( array( 'form_id' => $chefs['id'], 'status' => 'active', '2' => 1, '1' => 'Maria Henry' ) );
		$c2 = $this->factory->entry->create_and_get( array( 'form_id' => $chefs['id'], 'status' => 'active', '2' => 2, '1' => 'Henry Marrek' ) );

		$s1 = $this->factory->entry->create_and_get( array( 'form_id' => $souschefs['id'], 'status' => 'active', '2' => 1, '1' => 'Mary Jane' ) );
		$s2 = $this->factory->entry->create_and_get( array( 'form_id' => $souschefs['id'], 'status' => 'active', '2' => 1, '1' => 'Jane Henryson' ) );
		$s3 = $this->factory->entry->create_and_get( array( 'form_id' => $souschefs['id'], 'status' => 'active', '2' => 2, '1' => 'Marick Bonobo' ) );
		$s4 = $this->factory->entry->create_and_get( array( 'form_id' => $souschefs['id'], 'status' => 'active', '2' => 2, '1' => 'Henry Oswald' ) );

		$n1 = $this->factory->entry->create_and_get( array( 'form_id' => $souschefs['id'], 'status' => 'active', '1' => 'None' ) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $chefs['id'],
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'form_id' => $chefs['id'],
						'label' => 'Entry ID',
						'show_as_link' => true,
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'form_id' => $souschefs['id'],
						'label' => 'Entry ID',
						'show_as_link' => true,
					),
				),
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'form_id' => $chefs['id'],
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'form_id' => $souschefs['id'],
						'label' => 'Entry ID',
					),
				),
			),
			'joins' => array(
				array( $chefs['id'], '2', $souschefs['id'], '2' ),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$output = do_shortcode( '[gravityview id=' . $view->ID . ']' );

		$this->assertStringContainsString( 'entry=' . implode( ',', array( $c1['id'], $s1['id'] ) ), $output );
		$this->assertStringContainsString( 'entry=' . implode( ',', array( $c1['id'], $s2['id'] ) ), $output );
		$this->assertStringContainsString( 'entry=' . implode( ',', array( $c2['id'], $s3['id'] ) ), $output );
		$this->assertStringContainsString( 'entry=' . implode( ',', array( $c2['id'], $s4['id'] ) ), $output );

		$output = $view->content( '' );

		$this->assertStringContainsString( 'entry=' . implode( ',', array( $c1['id'], $s1['id'] ) ), $output );
		$this->assertStringContainsString( 'entry=' . implode( ',', array( $c1['id'], $s2['id'] ) ), $output );
		$this->assertStringContainsString( 'entry=' . implode( ',', array( $c2['id'], $s3['id'] ) ), $output );
		$this->assertStringContainsString( 'entry=' . implode( ',', array( $c2['id'], $s4['id'] ) ), $output );

		gravityview()->request->returns['is_entry'] = \GV\Multi_Entry::from_entries( array(
			\GV\GF_Entry::by_id( $c1['id'] ), \GV\GF_Entry::by_id( $s1['id'] ),
		) );

		$output = do_shortcode( '[gravityview id=' . $view->ID . ']' );
		$this->assertStringNotContainsString( 'not allowed', $output );

		$output = $view->content( '' );
		$this->assertStringNotContainsString( 'not allowed', $output );

		gravityview()->request->returns['is_entry'] = \GV\Multi_Entry::from_entries( array(
			\GV\GF_Entry::by_id( $c2['id'] ), \GV\GF_Entry::by_id( $s3['id'] ),
		) );

		$output = do_shortcode( '[gravityview id=' . $view->ID . ']' );
		$this->assertStringNotContainsString( 'not allowed', $output );

		$output = $view->content( '' );
		$this->assertStringNotContainsString( 'not allowed', $output );

		gravityview()->request->returns['is_entry'] = \GV\Multi_Entry::from_entries( array(
			\GV\GF_Entry::by_id( $c1['id'] ), \GV\GF_Entry::by_id( $n1['id'] ),
		) );

		$output = do_shortcode( '[gravityview id=' . $view->ID . ']' );
		$this->assertStringContainsString( 'not allowed', $output );

		$output = $view->content( '' );
		$this->assertStringContainsString( 'not allowed', $output );
	}

	/**
	 * @since 2.2.2
	 */
	public function test_union_simple() {
		$this->_reset_context();

		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_UNIONS ) ) {
			$this->markTestSkipped( 'Requires \GF_Patched_Query' );
		}

		$form_1 = $this->factory->form->import_and_get( 'simple.json' );
		$form_2 = $this->factory->form->import_and_get( 'complete.json' );

		$this->factory->entry->create_and_get( array( 'form_id' => $form_2['id'], 'status' => 'active', '16' => 'neptune@gravitykit.com' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $form_1['id'], 'status' => 'active', '1'  => 'earth@gravitykit.com' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $form_1['id'], 'status' => 'active', '1'  => 'saturn@gravitykit.com' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $form_2['id'], 'status' => 'active', '16' => 'venus@gravitykit.com' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $form_2['id'], 'status' => 'active', '16' => 'mars@gravitykit.com' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $form_1['id'], 'status' => 'active', '1'  => 'uranus@gravitykit.com' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $form_2['id'], 'status' => 'active', '16' => 'jupiter@gravitykit.com' ) );
		$this->factory->entry->create_and_get( array( 'form_id' => $form_2['id'], 'status' => 'active', '16' => 'mercury@gravitykit.com' ) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		global $post;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form_1['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $form_1['id'],
						'id' => 'id',
						'label' => 'ID',
						'unions' => array(
							$form_2['id'] => 'id',
						),
					),
					wp_generate_password( 4, false ) => array(
						'form_id' => $form_1['id'],
						'id' => '1',
						'label' => 'Item',
						'unions' => array(
							$form_2['id'] => '16',
						),
					),
				),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		$this->assertCount( 8, $expected_entries = wp_list_pluck( $view->get_entries()->all(), 'ID' ) );

		$view->settings->update( array( 'page_size' => 3 ) );

		$this->assertCount( 3, $actual_entries = wp_list_pluck( $view->get_entries()->all(), 'ID' ) );

		$_GET = array( 'pagenum' => 2 );

		$this->assertCount( 3, $entries = wp_list_pluck( $view->get_entries()->all(), 'ID' ) );
		$actual_entries = array_merge( $actual_entries, $entries );

		$_GET = array( 'pagenum' => 3 );

		$this->assertCount( 2, $entries = wp_list_pluck( $view->get_entries()->all(), 'ID' ) );
		$actual_entries = array_merge( $actual_entries, $entries );

		$this->assertEquals( $expected_entries, $actual_entries );

		$_GET = array();
		$view->settings->update( array( 'page_size' => 25 ) );

		add_filter( 'gravityview_search_criteria', $callback = function( $criteria ) {
			$criteria['search_criteria']['field_filters'] []= array(
				'key' => '1',
				'operator' => 'contains',
				'value' => 's',
			);
			return $criteria;
		} );

		$this->assertCount( 4, $view->get_entries()->all() );

		remove_filter( 'gravityview_search_criteria', $callback );

		$_GET = array( 'sort' => '1', 'dir' => 'asc' );

		$this->assertCount( 8, $entries = $view->get_entries()->all() );

		$expected = $actual = array_map( function( $e ) {
			return empty( $e['1'] ) ? $e['16'] : $e['1'];
		}, $entries );

		sort( $expected );

		$this->assertEquals( $expected, $actual );
	}

	public function test_joins_on_entry_columns() {
		$this->_reset_context();

		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$step1 = $this->factory->form->import_and_get( 'simple.json' );
		$step2 = $this->factory->form->import_and_get( 'simple.json' );

		$entry1_1 = $this->factory->entry->create_and_get( array(
			'form_id' => $step1['id'],
			'status' => 'active',
			'1' => 'Entry 1',
		) );

		$entry1_2 = $this->factory->entry->create_and_get( array(
			'form_id' => $step2['id'],
			'status' => 'active',
			'1' => 'After Entry 1',
			'2' => $entry1_1['id'],
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $step1['id'],
			'status' => 'active',
			'1' => 'Unrelated 1',
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $step2['id'],
			'status' => 'active',
			'1' => 'Unrelated 2',
		) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $step1['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $step1['id'],
						'id'      => 'id',
						'label'   => 'Step 1',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'form_id' => $step2['id'],
						'label' => 'Step 2',
					),
				),
			),
			'joins' => array(
				array( $step1['id'], 'id', $step2['id'], '2' ),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		if ( '\GF_Patched_Query' !== $view->get_query_class() ) {
			$this->markTestSkipped( 'Requires \GF_Patched_Query' );
		}

		$entries = $view->get_entries()->all();

		$this->assertCount( 2, $entries );
		$this->assertCount( 1, $entries[0]->entries );
		$this->assertCount( 2, $entries[1]->entries );
		$this->assertEquals( $entry1_1['id'], $entries[1][$step1['id']]['id'] );
		$this->assertEquals( $entry1_2['id'], $entries[1][$step2['id']]['id'] );
		$this->assertEquals( $entries[1][$step1['id']]['id'], $entries[1][$step2['id']]['2'] );

		$this->_reset_context();
	}

	public function test_joins_joined_status() {
		$this->_reset_context();

		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$step1 = $this->factory->form->import_and_get( 'simple.json' );
		$step2 = $this->factory->form->import_and_get( 'simple.json' );

		$entry1_1 = $this->factory->entry->create_and_get( array(
			'form_id' => $step1['id'],
			'status' => 'active',
			'1' => 'Entry 1',
		) );

		$entry1_2 = $this->factory->entry->create_and_get( array(
			'form_id' => $step2['id'],
			'status' => 'active',
			'1' => 'After Entry 1',
			'2' => $entry1_1['id'],
		) );

		$inactive = $this->factory->entry->create_and_get( array(
			'form_id' => $step2['id'],
			'status' => 'trash',
			'1' => 'Not active',
			'2' => $entry1_1['id'],
		) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $step1['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $step1['id'],
						'id'      => 'id',
						'label'   => 'Step 1',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'form_id' => $step2['id'],
						'label' => 'Step 2',
					),
				),
			),
			'joins' => array(
				array( $step1['id'], 'id', $step2['id'], '2' ),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		if ( '\GF_Patched_Query' !== $view->get_query_class() ) {
			$this->markTestSkipped( 'Requires \GF_Patched_Query' );
		}

		$entries = $view->get_entries()->all();

		$this->assertCount( 1, $entries );

		$this->_reset_context();
	}

	public function test_joins_custom_content() {
		$this->_reset_context();

		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$step1 = $this->factory->form->import_and_get( 'simple.json' );
		$step2 = $this->factory->form->import_and_get( 'simple.json' );

		$entry1_1 = $this->factory->entry->create_and_get( array(
			'form_id' => $step1['id'],
			'status' => 'active',
			'1' => 'Entry 1',
		) );

		$entry1_2 = $this->factory->entry->create_and_get( array(
			'form_id' => $step2['id'],
			'status' => 'active',
			'1' => 'After Entry 1',
			'2' => $entry1_1['id'],
		) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $step1['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $step1['id'],
						'id'      => 'custom',
						'label'   => 'Step 1',
						'content' => '{entry_id}',
					),
					wp_generate_password( 4, false ) => array(
						'form_id' => $step2['id'],
						'id'      => 'custom',
						'label' => 'Step 2',
						'content' => '{entry_id}',
					),
				),
			),
			'joins' => array(
				array( $step1['id'], 'id', $step2['id'], '2' ),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		if ( '\GF_Patched_Query' !== $view->get_query_class() ) {
			$this->markTestSkipped( 'Requires \GF_Patched_Query' );
		}

		$renderer = new \GV\View_Renderer();

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$out = $renderer->render( $view );

		$this->assertStringContainsString( sprintf( 'Step 1">%s<', $entry1_1['id'] ), $out );
		$this->assertStringContainsString( sprintf( 'Step 2">%s<', $entry1_2['id'] ), $out );

		$this->_reset_context();
	}

	public function test_joins_on_entry_meta() {
		$this->_reset_context();

		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_JOINS ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$step1 = $this->factory->form->import_and_get( 'simple.json' );
		$step2 = $this->factory->form->import_and_get( 'simple.json' );

		$entry1_1 = $this->factory->entry->create_and_get( array(
			'form_id' => $step1['id'],
			'status' => 'active',
			'1' => 'Entry 1',
		) );

		$entry1_2 = $this->factory->entry->create_and_get( array(
			'form_id' => $step2['id'],
			'status' => 'active',
			'1' => 'After Entry 1',
		) );

		gform_update_meta( $entry1_2['id'], 'child', $entry1_1['id'] );

		$this->factory->entry->create_and_get( array(
			'form_id' => $step1['id'],
			'status' => 'active',
			'1' => 'Unrelated 1',
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $step2['id'],
			'status' => 'active',
			'1' => 'Unrelated 2',
		) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $step1['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'form_id' => $step1['id'],
						'id'      => 'id',
						'label'   => 'Step 1',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'form_id' => $step2['id'],
						'label' => 'Step 2',
					),
				),
			),
			'joins' => array(
				array( $step1['id'], 'id', $step2['id'], 'child' ),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		if ( '\GF_Patched_Query' !== $view->get_query_class() ) {
			$this->markTestSkipped( 'Requires \GF_Patched_Query' );
		}

		$entries = $view->get_entries()->all();

		$this->assertCount( 2, $entries );
		$this->assertCount( 1, $entries[0]->entries );
		$this->assertCount( 2, $entries[1]->entries );
		$this->assertEquals( $entry1_1['id'], $entries[1][$step1['id']]['id'] );
		$this->assertEquals( $entry1_2['id'], $entries[1][$step2['id']]['id'] );
		$this->assertEquals( $entries[1][$step1['id']]['id'], gform_get_meta( $entries[1][$step2['id']]['id'], 'child' ) );

		$this->_reset_context();
	}
}
