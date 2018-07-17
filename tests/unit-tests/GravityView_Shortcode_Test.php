<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group shortcode
 * @group gravityviewshortcode
 */
class GravityView_Shortcode_Test extends GV_UnitTestCase {

	/**
	 * Just covers that it renders something and requires an ID
	 * @covers GravityView_Shortcode::shortcode
	 */
	function test_shortcode() {

		// No ID attribute
		$value = do_shortcode( '[gravityview]' );
		$this->assertEquals( '', $value );

		$view_id = $this->factory->view->create();

		$value = do_shortcode( '[gravityview id="'.$view_id.'" hoolo="3"]' );
		$this->assertNotEmpty( $value );
		$this->assertTrue( strpos( $value, 'gv-container' ) > 0 );
	}

	/**
	 * Test page_size, first_entry and last_entry values for the `detail` attribute
	 * @covers GravityView_Shortcode::get_view_detail
	 * @covers GravityView_View::setTotalEntries
	 */
	function test_shortcode_get_view_detail_PAGING() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'settings' => array(
				'page_size' => 10,
			)
		) );
		$view = \GV\View::from_post( $post );

		$request = new \GV\Mock_Request();
		$request->returns['is_view'] = &$view;
		gravityview()->request = $request;

		// Not set
		$value = do_shortcode( '[gravityview detail=first_entry]' );
		$this->assertEquals( '0', $value );

		foreach ( range( 1, 50 ) as $i ) {
			$entry = $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
				'16' => sprintf( '[%d] Entry %s', $i, wp_generate_password( 12 ) ),
			) );
		}

		$value = do_shortcode( '[gravityview detail=first_entry]' );
		$this->assertEquals( '1', $value );

		$value = do_shortcode( '[gravityview detail=last_entry]' );
		$this->assertEquals( '10', $value );

		$value = do_shortcode( '[gravityview detail=page_size]' );
		$this->assertEquals( '10', $value );

		$view->settings->update( array(
			'offset' => 20,
			'page_size' => 20,
		) );

		$value = do_shortcode( '[gravityview detail=first_entry]' );
		$this->assertEquals( '21', $value );

		$value = do_shortcode( '[gravityview detail=last_entry]' );
		$this->assertEquals( '40', $value );

		$value = do_shortcode( '[gravityview detail=page_size]' );
		$this->assertEquals( '20', $value );

		add_filter( 'gravityview/shortcode/detail/page_size', '__return_empty_string' );
		add_filter( 'gravityview/shortcode/detail/first_entry', '__return_empty_string' );
		add_filter( 'gravityview/shortcode/detail/last_entry', '__return_empty_string' );
		$value = do_shortcode( '[gravityview detail=page_size]' );
		$this->assertEquals( '', $value );
		$value = do_shortcode( '[gravityview detail=first_entry]' );
		$this->assertEquals( '', $value );
		$value = do_shortcode( '[gravityview detail=last_entry]' );
		$this->assertEquals( '', $value );
		remove_filter( 'gravityview/shortcode/detail/page_size', '__return_empty_string' );
		remove_filter( 'gravityview/shortcode/detail/first_entry', '__return_empty_string' );
		remove_filter( 'gravityview/shortcode/detail/last_entry', '__return_empty_string' );

		gravityview()->request = new \GV\Frontend_Request();
	}

	/**
	 * @covers GravityView_Shortcode::get_view_detail
	 * @covers GravityView_View::setTotalEntries
	 */
	function test_shortcode_get_view_detail_TOTAL_ENTRIES() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'settings' => array(
				'page_size' => 10,
			)
		) );
		$view = \GV\View::from_post( $post );

		$request = new \GV\Mock_Request();
		$request->returns['is_view'] = &$view;
		gravityview()->request = $request;

		// Not set
		$value = do_shortcode( '[gravityview detail=first_entry]' );
		$this->assertEquals( '0', $value );

		$value = do_shortcode( '[gravityview detail=total_entries]' );
		$this->assertEquals( '0', $value );

		foreach ( range( 1, 1050 ) as $i ) {
			$entry = $this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status' => 'active',
				'16' => sprintf( '[%d] Entry %s', $i, wp_generate_password( 12 ) ),
			) );
		}

		$value = do_shortcode( '[gravityview detail=total_entries]' );
		$this->assertEquals( '1,050', $value );

		add_filter( 'gravityview/shortcode/detail/total_entries', '__return_empty_string' );
		$value = do_shortcode( '[gravityview detail=total_entries]' );
		$this->assertEquals( '', $value );
		remove_filter( 'gravityview/shortcode/detail/total_entries', '__return_empty_string' );

		gravityview()->request = new \GV\Frontend_Request();
	}

	/**
	 * @covers GravityView_Shortcode::parse_and_sanitize_atts
	 */
	public function test_parse_and_sanitize_atts() {

		$method = new ReflectionMethod( 'GravityView_Shortcode', 'parse_and_sanitize_atts' );
		$method->setAccessible( true );
		$GravityView_Shortcode = new GravityView_Shortcode;

		$tests = array(
			'Strip nothing, make sure it is working' => array(
				'original' => array(
					'id' => 123,
				),
				'expected' => array(
					'id' => 123,
				)
			),
			'Test {get} merge tag, valid value' => array(
				'get' => array( 'view_id' => 123 ),
				'original' => array(
					'id' => '{get:view_id}'
				),
				'expected' => array(
					'id' => 123
				)
			),
			'Test {get} merge tag, invalid value (string, not numeric)' => array(
				'get' => array( 'view_id' => 'asdasdsd' ),
				'original' => array(
					'id' => '{get:view_id}'
				),
				'expected' => array()
			),

			'Strip attributes not defined in GravityView_View_Data::get_default_args' => array(
				'original' => array(
					'id' => 123,
					'page_size' => 12,
					'not_defined_attribute' => 1
				),
				'expected' => array(
					'id' => 123,
					'page_size' => 12
				)
			),

			'Number settings that are not numbers should be stripped' => array(
				'original' => array(
					'id' => 'asdasd'
				),
				'expected' => array(
				)
			),

			'Number settings that numbers should not be stripped' => array(
				'original' => array(
					'search_field' => '123.10'
				),
				'expected' => array(
					'search_field' => 123.10,
				)
			),

			'Checkbox "0" string should be `0`' => array(
				'original' => array(
					'id' => 123,
					'lightbox' => '0'
				),
				'expected' => array(
					'id' => 123,
					'lightbox' => 0
				)
			),

			'Checkbox "1" string should be `1`' => array(
				'original' => array(
					'id' => 123,
					'lightbox' => '1'
				),
				'expected' => array(
					'id' => 123,
					'lightbox' => 1
				)
			),

			'Checkbox non-numeric string should be based on gv_empty()' => array(
				'original' => array(
					'id' => 123,
					'lightbox' => ''
				),
				'expected' => array(
					'id' => 123,
					'lightbox' => 0
				)
			),

			'Select option must exist' => array(
				'original' => array(
					'id' => 123,
					'lightbox' => '0'
				),
				'expected' => array(
					'id' => 123,
					'lightbox' => 0
				)
			),

			'Select options that exist should not be stripped' => array(
				'original' => array(
					'id' => 123,
					'sort_direction' => 'ASC'
				),
				'expected' => array(
					'id' => 123,
					'sort_direction' => 'ASC'
				)
			),

			'Select options that do not exist should be stripped' => array(
				'original' => array(
					'id' => 123,
					'sort_direction' => 'asdsadasd'
				),
				'expected' => array(
					'id' => 123
				)
			),

			'{get} should not pass unsanitized stuff' => array(
				'get' => array(
					'danger' => '<script>alert()</script>'
				),
				'original' => array(
					'search_value' => '{get:danger}'
				),
				'expected' => array(
					'search_value' => esc_html( '<script>alert()</script>' )
				)
			),
		);

		foreach ( $tests as $description => $test ) {

			if( ! empty( $test['get'] ) ) {
				$_GET = (array)$test['get'];
			}

			$this->assertEquals( $test['expected'], $method->invoke( $GravityView_Shortcode, $test['original'] ), $description );
		}

	}

	/**
	 * @covers GravityView_Shortcode::shortcode
	 * @see https://github.com/gravityview/GravityView/issues/851
	 */
	public function test_shortcode_search_criteria() {
		$view_id = $this->factory->view->create();

		$_this = &$this;
		add_filter( 'gravityview_get_entries', function( $parameters ) use ( $_this ) {
			$_this->assertEquals( $parameters['paging'], array( 'page_size' => 13, 'offset' => 7 ) );
			return $parameters;
		} );

		do_shortcode( '[gravityview id="'.$view_id.'" page_size="13" offset="7"]' );

		remove_all_filters( 'gravityview_get_entries' );

		add_filter( 'gravityview_get_entries', function( $parameters ) use ( $_this ) {
			$_this->assertEquals( $parameters['paging'], array( 'page_size' => 5, 'offset' => 5 ) );
			return $parameters;
		} );

		$_GET['pagenum'] = 2;
		do_shortcode( '[gravityview id="'.$view_id.'" page_size="5" offset="0"]' );

		remove_all_filters( 'gravityview_get_entries' );

		add_filter( 'gravityview_get_entries', function( $parameters ) use ( $_this ) {
			$_this->assertEquals( $parameters['paging'], array( 'page_size' => 5, 'offset' => 23 ) );
			return $parameters;
		} );

		$_GET['pagenum'] = 5;
		do_shortcode( '[gravityview id="'.$view_id.'" page_size="5" offset="3"]' );

		remove_all_filters( 'gravityview_get_entries' );

		unset( $_GET['pagenum'] );
	}

	public function test_shortcode_abstract() {
		$shortcode = new \GV\Shortcode();
		$this->assertEmpty( $shortcode->callback( array() ) );
	}
}
