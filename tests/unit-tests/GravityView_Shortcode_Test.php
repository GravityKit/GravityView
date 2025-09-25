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
				'show_only_approved' => 0,
			)
		) );
		$view = \GV\View::from_post( $post );

		$request = new \GV\Mock_Request();
		$request->returns['is_view'] = &$view;
		gravityview()->request = $request;

		// Not set
		$value = do_shortcode( '[gravityview detail=first_entry]' );
		$this->assertEquals( '0', $value );

		// Disable caching as we'll be running the same query but after creating new entries.
		add_filter( 'gk/gravityview/view/entries/cache', '__return_false' );
		add_filter( 'gravityview_use_cache', '__return_false' );

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

		remove_all_filters( 'gk/gravityview/view/entries/cache' );
		remove_all_filters( 'gravityview_use_cache' );

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
				'show_only_approved' => 0,
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

		// Disable caching as we'll be running the same query but after creating new entries.
		add_filter( 'gk/gravityview/view/entries/cache', '__return_false' );
		add_filter( 'gravityview_use_cache', '__return_false' );

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

		remove_all_filters( 'gk/gravityview/view/entries/cache' );
		remove_all_filters( 'gravityview_use_cache' );
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

			'Search operators that are valid should not be stripped' => array(
				'original' => array(
					'id' => 123,
					'search_operator' => 'is',
				),
				'expected' => array(
					'id' => 123,
					'search_operator' => 'is',
				)
			),

			'More search operators that are valid should not be stripped' => array(
				'original' => array(
					'id' => 123,
					'search_operator' => 'contains',
				),
				'expected' => array(
					'id' => 123,
					'search_operator' => 'contains',
				)
			),

			'Search operators that do not exist should be stripped' => array(
				'original' => array(
					'id' => 123,
					'search_operator' => 'this is not valid',
				),
				'expected' => array(
					'id' => 123,
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

	public function test_shortcode_single_view_from_directory() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
				),
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Expected Field Value',
					),
				),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => $field = sprintf( '[%d] Entry %s', 1, wp_generate_password( 12, false ) ),
		) );
		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		$request = new \GV\Mock_Request();
		$request->returns['is_entry'] = $entry;
		gravityview()->request = $request;

		global $post;

		$post = $this->factory->post->create_and_get( array( 'post_content' => '[gravityview id="' . $view->ID . '"]' ) );

		$shortcode = new \GV\Shortcodes\gravityview();

		$this->assertStringContainsString( $field, $shortcode->callback( array( 'id' => $view->ID ) ) );

		gravityview()->request = new \GV\Frontend_Request();
	}

	public function test_shortcode_search() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$post = $this->factory->view->create_and_get( array(
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
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'abcxyz',
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'abc',
		) );

		$shortcode = new \GV\Shortcodes\gravityview();
		$content = $shortcode->callback( array( 'id' => $view->ID ) );
		$this->assertStringContainsString( 'data-label="Text">abc</td>', $content );
		$this->assertStringContainsString( 'data-label="Text">abcxyz</td>', $content );

		$shortcode = new \GV\Shortcodes\gravityview();
		$content = $shortcode->callback( array( 'id' => $view->ID, 'search_field' => '1', 'search_value' => 'abcxyz' ) );
		$this->assertStringNotContainsString( 'data-label="Text">abc</td>', $content );
		$this->assertStringContainsString( 'data-label="Text">abcxyz</td>', $content );

		$shortcode = new \GV\Shortcodes\gravityview();
		$content = $shortcode->callback( array( 'id' => $view->ID, 'search_field' => '1', 'search_value' => 'abc', 'search_operator' => 'is' ) );
		$this->assertStringContainsString( 'data-label="Text">abc</td>', $content );
		$this->assertStringNotContainsString( 'data-label="Text">abcxyz</td>', $content );
	}

	/**
	 * Test that shortcode attributes with problematic characters (< > & [ ]) are properly encoded/decoded.
	 *
	 * @covers \GV\Shortcode::preprocess_shortcode_attributes
	 * @covers \GV\Shortcode::normalize_attributes
	 */
	public function test_shortcode_attribute_encoding() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'settings' => array(
				'show_only_approved' => 0,
			),
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => '10',
		) );

		$secret = \GV\View::from_post( $view )->get_validation_secret();

		// Test 1: create content with problematic characters that would break WordPress parsing.
		$content_with_less_than = sprintf(
			'[gravityview id="%d" secret="%s" search_field="1" search_value="5" search_operator="<"] and [test attr="<"]',
			$view->ID,
			$secret
		);

		// Process through the_content filter (which should apply our preprocessing).
		$filtered_content = apply_filters( 'the_content', $content_with_less_than );

		// The shortcode should be processed and render a view container.
		$this->assertStringContainsString( 'gv-container', $filtered_content, 'GravityView shortcode with < operator should render' );

		// The view should be properly rendered (not remain as shortcode text).
		$this->assertStringNotContainsString( '[gravityview', $filtered_content, 'GravityView shortcode should be processed, not remain as text' );

		// The non-GravityView [test] shortcode should remain (WordPress may convert quotes).
		$this->assertTrue(
			strpos( $filtered_content, '[test attr="<"]' ) !== false ||
			strpos( $filtered_content, '[test attr=&#8221;<"]' ) !== false,
			'Non-GravityView shortcode should remain unprocessed'
		);

		// Test 2: test with multiple problematic characters.
		$content_complex = sprintf(
			'[gravityview id="%d" secret="%s" search_field="1" search_value="5 & more" search_operator=">"] Content',
			$view->ID,
			$secret
		);

		$filtered_complex = apply_filters( 'the_content', $content_complex );

		// Should render the View.
		$this->assertStringContainsString( 'gv-container', $filtered_complex, 'GravityView with & and > should render' );
		$this->assertStringNotContainsString( '[gravityview', $filtered_complex, 'Complex shortcode should be processed' );
	}
}
