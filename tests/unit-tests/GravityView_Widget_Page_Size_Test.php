<?php
defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group widgets
 */
class GravityView_Widget_Page_Size_Test extends GV_UnitTestCase {
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

	public function test_page_size_widget_output() {
        $form = $this->factory->form->import_and_get( 'complete.json' );

        global $post;

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
                        'id' => 'page_size',
                    ),
                ),
            ),
        ) );

        $view = \GV\View::from_post( $post );

        $entries = new \GV\Entry_Collection();

        $renderer = new \GV\View_Renderer();

        gravityview()->request = new \GV\Mock_Request();
        gravityview()->request->returns['is_view'] = $view;

		foreach ( range( 1, 25 ) as $i ) {
		    $entry = $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
				'16' => sprintf( '[%d] Some text in a textarea (%s)', $i, wp_generate_password( 12 ) ),
			) );

			$entries->add( \GV\GF_Entry::from_entry( $entry ) );
		}

		$future = $renderer->render( $view );
		$this->assertContains( 'gv-page_size', $future );

		add_filter( 'gravityview/widgets/page_size/page_sizes', $page_sizes_callback = function() {
			return array(
				array( 'value' => 12345, 'text' => 'page_sizes12345' ),
			);
		} );

		$future = $renderer->render( $view );
		$this->assertContains( 'page_sizes12345', $future );

		$this->assertTrue( remove_filter( 'gravityview/widgets/page_size/page_sizes', $page_sizes_callback ) );
    }

	public function test_page_size_widget_functionality() {
        $form = $this->factory->form->import_and_get( 'complete.json' );

        global $post;

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
                        'id' => 'page_size',
                    ),
                ),
            ),
        ) );

        $view = \GV\View::from_post( $post );

        $entries = new \GV\Entry_Collection();

        $renderer = new \GV\View_Renderer();

        gravityview()->request = new \GV\Mock_Request();
        gravityview()->request->returns['is_view'] = $view;

		foreach ( range( 1, 100 ) as $i ) {
		    $entry = $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
				'16' => sprintf( '[%d] Some text in a textarea (%s)', $i, wp_generate_password( 12 ) ),
			) );

			$entries->add( \GV\GF_Entry::from_entry( $entry ) );
		}

		$future = $renderer->render( $view );
		$this->assertContains( 'gv-page_size', $future );

		add_filter( 'gravityview/widgets/page_size/page_sizes', $page_sizes_callback = function( $sizes ) {
			$sizes[] = array( 'value' => 7, 'text' => '7 entries per page' );
			return $sizes;
		} );

		$future = $renderer->render( $view );
		$this->assertContains( '[100] Some text in a textarea', $future );
		$this->assertContains( '[76] Some text in a textarea', $future );
		$this->assertNotContains( '[1] Some text in a textarea', $future );

		$_GET['page_size'] = 7;
        $view = \GV\View::from_post( $post );

		$future = $renderer->render( $view );
		$this->assertContains( "selected='selected'>7 entries per page", $future );
		$this->assertContains( '[100] Some text in a textarea', $future );
		$this->assertContains( '[94] Some text in a textarea', $future );
		$this->assertNotContains( '[93] Some text in a textarea', $future );

		$this->assertTrue( remove_filter( 'gravityview/widgets/page_size/page_sizes', $page_sizes_callback ) );
    }
	
	public function test_page_size_widget_not_present() {
        $form = $this->factory->form->import_and_get( 'complete.json' );

        global $post;

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
        ) );

        $view = \GV\View::from_post( $post );

        $entries = new \GV\Entry_Collection();

        $renderer = new \GV\View_Renderer();

        gravityview()->request = new \GV\Mock_Request();
        gravityview()->request->returns['is_view'] = $view;

		foreach ( range( 1, 100 ) as $i ) {
		    $entry = $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
				'16' => sprintf( '[%d] Some text in a textarea (%s)', $i, wp_generate_password( 12 ) ),
			) );

			$entries->add( \GV\GF_Entry::from_entry( $entry ) );
		}

		$future = $renderer->render( $view );
		$this->assertNotContains( 'gv-page_size', $future );

		$_GET['page_size'] = 10;
        $view = \GV\View::from_post( $post );

		$future = $renderer->render( $view );
		$this->assertContains( '[100] Some text in a textarea', $future );
		$this->assertContains( '[76] Some text in a textarea', $future );
		$this->assertNotContains( '[1] Some text in a textarea', $future );
    }
}
