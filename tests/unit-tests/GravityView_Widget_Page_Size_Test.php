<?php
defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group widgets
 * @covers \GV\Widgets\Page_Size
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
		$this->assertContains( "<option value='25' selected='selected'>25</option>", $future, 'default page size should be selected' );

		// Update default page size
		$view->settings->update( array( 'page_size' => 50 ) );

		$future = $renderer->render( $view );
		$this->assertContains( "<option value='50' selected='selected'>50</option>", $future, 'default page size should be selected' );

		$view->settings->update( array( 'page_size' => 1 ) );
		$future = $renderer->render( $view );
		$this->assertContains( "<option value='1' selected='selected'>", $future, 'default page size should be added, if not exists already' );

		// Restore default page size
		$view->settings->update( array( 'page_size' => 25 ) );

		add_filter( 'gravityview/widgets/page_size/page_sizes', $page_sizes_callback = function() {
			return array(
				array( 'value' => 12345, 'text' => 'page_sizes12345' ),
				array( 'value' => 15, 'text' => '<a>& don\'t forget to escape me!</a>' ),
			);
		} );

		$future = $renderer->render( $view );
		$this->assertContains( 'page_sizes12345', $future );
		$this->assertContains( '&lt;a&gt;&amp; don&#039;t forget to escape me!&lt;/a&gt;', $future );

		$this->assertTrue( remove_filter( 'gravityview/widgets/page_size/page_sizes', $page_sizes_callback ) );
    }

	/**
	 * @covers \GV\Widgets\Page_Size::get_page_sizes
	 */
    public function test_page_sizes_filter() {

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
					    'id' => 'page_size',
				    ),
			    ),
		    ),
	    ) );

	    $view = \GV\View::from_post( $post );

	    $context = \GV\Template_Context::from_template( array( 'view' => $view ) );

	    $original_page_sizes = \GV\Widgets\Page_Size::get_page_sizes( $context );

	    $view->settings->update( array( 'page_size' => 100 ) );

	    $page_sizes = \GV\Widgets\Page_Size::get_page_sizes( $context );

	    $this->assertEquals( count( $page_sizes ), count( $original_page_sizes ), 'Size values should be unique' );

	    $view->settings->update( array( 'page_size' => 9999 ) );

	    $page_sizes = \GV\Widgets\Page_Size::get_page_sizes( $context );

	    $this->assertEquals( 9999, array_pop( $page_sizes )['value'], 'Should be sorted small to large' );

	    $view->settings->update( array( 'page_size' => 1 ) );

	    $page_sizes = \GV\Widgets\Page_Size::get_page_sizes( $context );

	    $this->assertEquals( 1, array_shift( $page_sizes )['value'], 'Should be sorted small to large' );

	    $expected_result = array(
		    array( 'value' => 12345, 'text' => 'page_sizes12345' ),
		    array( 'value' => 15, 'text' => '<a>& don\'t forget to escape me!</a>' ),
	    );

	    add_filter( 'gravityview/widgets/page_size/page_sizes', $page_sizes_callback = function() use ( $expected_result ) {
		    return $expected_result;
	    } );

	    $view->settings->update( array( 'page_size' => 20000 ) );

	    $page_sizes = \GV\Widgets\Page_Size::get_page_sizes( $context );

	    $this->assertSame( $expected_result, $page_sizes );

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
