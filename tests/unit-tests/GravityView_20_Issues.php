<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * Issues uncovered in 2.0
 */
class GV_20_Issues_Test extends GV_UnitTestCase {
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

		\GV\View::_flush_cache();

		set_current_screen( 'front' );
		wp_set_current_user( 0 );
	}

	public function test_search_widget_embedded() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
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
		$view = \GV\View::from_post( $post );

		$entries = array();
		foreach ( range( 1, 8 ) as $i ) {
			$entry = $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
				'16' => sprintf( '[%d] Entry %s', $i, wp_generate_password( 12 ) ),
			) );
			$entries []= \GV\GF_Entry::by_id( $entry['id'] );
		}

		global $post;
		$post = $this->factory->post->create_and_get();
		$post->post_content = sprintf( '[gravityview id="%d"]', $view->ID );

		$content = apply_filters( 'the_content', $post->post_content );

		$this->assertContains( get_permalink( $post->ID ), $content );
	}
}
