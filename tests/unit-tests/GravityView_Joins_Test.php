<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * All future tests live here for now...
 *
 * ...at least until the future Test component appears.
 *
 * @group gvfuture
 */
class GravityView_Joins_Test extends GV_UnitTestCase {
	function setUp() {
		/** The future branch of GravityView requires PHP 5.3+ namespaces. */
		if ( version_compare( phpversion(), '5.3' , '<' ) ) {
			$this->markTestSkipped( 'The future code requires PHP 5.3+' );
			return;
		}

		$this->_reset_context();

		parent::setUp();
	}

	function tearDown() {
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
		) );
		$view = \GV\View::from_post( $view );

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
		) );
		$view = \GV\View::from_post( $view );

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
		) );
		$view = \GV\View::from_post( $post );

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
		) );
		$view = \GV\View::from_post( $post );

		$renderer = new \GV\View_Renderer();

		$renderer = new \GV\Legacy_Override_Template( $view );

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
}
