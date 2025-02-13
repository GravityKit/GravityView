<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group gvcommon
 */
class GVCommon_Test extends GV_UnitTestCase {


	/**
	 * @covers GVCommon::has_gravityview_shortcode()
	 */
	function test_has_gravityview_shortcode() {

		$post_without_shortcode = $this->factory->post->create_and_get(array('post_content' => '[gravityview is not a shortcode'));
		$post_with_shortcode = $this->factory->post->create_and_get(array('post_content' => '[gravityview]'));
		$gravityview_post = $this->factory->view->create_and_get();

		$this->assertTrue( GVCommon::has_gravityview_shortcode( $gravityview_post ) );
		$this->assertTrue( GVCommon::has_gravityview_shortcode( $post_with_shortcode ) );
		$this->assertFalse( GVCommon::has_gravityview_shortcode( $post_without_shortcode ) );
	}

	/**
	 * @covers GVCommon::matches_operation()
	 * @since 1.22.1
	 */
	function test_matches_operation() {

		$this->assertTrue( GVCommon::matches_operation( 'example', 'example', 'equals' ) );
		$this->assertFalse( GVCommon::matches_operation( 'bad example', 'example', 'equals' ) );
		$this->assertTrue( GVCommon::matches_operation( 'example', 'EXAMPLE', 'equals' ), 'Not lowercased as expected' );

		$this->assertTrue( GVCommon::matches_operation( array('json', 'encoded' ), '["json","encoded"]', 'equals' ) );

		$this->assertTrue( GVCommon::matches_operation( 12, '12', 'is' ), 'String numbers should equal ints' );
		$this->assertTrue( GVCommon::matches_operation( '12', '12', 'greater_than_or_is' ) );
		$this->assertTrue( GVCommon::matches_operation( '15', '12', 'greater_than_or_is' ) );
		$this->assertFalse( GVCommon::matches_operation( '10', '12', 'greater_than_or_is' ) );
		$this->assertTrue( GVCommon::matches_operation( '12', '12', 'greater_than_or_equals' ) );
		$this->assertTrue( GVCommon::matches_operation( '15', '12', 'greater_than_or_equals' ) );
		$this->assertFalse( GVCommon::matches_operation( '10', '12', 'greater_than_or_equals' ) );

		$this->assertTrue( GVCommon::matches_operation( '12', '12', 'less_than_or_is' ) );
		$this->assertTrue( GVCommon::matches_operation( '10', '12', 'less_than_or_is' ) );
		$this->assertFalse( GVCommon::matches_operation( '15', '12', 'less_than_or_is' ) );
		$this->assertTrue( GVCommon::matches_operation( '12', '12', 'less_than_or_equals' ) );
		$this->assertTrue( GVCommon::matches_operation( '10', '12', 'less_than_or_equals' ) );
		$this->assertFalse( GVCommon::matches_operation( '15', '12', 'less_than_or_equals' ) );

		$this->assertTrue( GVCommon::matches_operation( 'Fiona Apple', 'Apple', 'contains' ) );
		$this->assertTrue( GVCommon::matches_operation( 'Fiona Apple', 'Happy Melodies', 'not_contains' ) );

		// Does an empty string contains emptiness? Yes.
		$this->assertFalse( GVCommon::matches_operation( '', '', 'contains' ) );
		$this->assertFalse( GVCommon::matches_operation( 'Not Empty', '', 'contains' ) );

		// "contains" is not "in"
		$this->assertTrue( GVCommon::matches_operation( '"BMW", "Audi"', 'Audi', 'contains' ) );
		$this->assertFalse( GVCommon::matches_operation( '"BMW", "Audi"', 'Audi', 'in' ) );

		// Test string comparisons
		/**
		 * @see https://github.com/gravityforms/gravityforms/commit/9d37fee852fe9946f030938bfa231e762f687728
		 * @see https://github.com/gravityview/GravityView-Advanced-Filter-Extension/issues/45
		 */
		$this->assertTrue( GVCommon::matches_operation( 1, 'b', 'less_than' ) );
		$this->assertTrue( GVCommon::matches_operation( 1, 'a', 'less_than' ) );
		$this->assertTrue( GVCommon::matches_operation( 2, 'a', 'less_than' ) );
		$this->assertTrue( GVCommon::matches_operation( 2, 'a', '<' ) );
		$this->assertTrue( GVCommon::matches_operation( 'a', 'b', 'less_than' ) );
		$this->assertTrue( GVCommon::matches_operation( 'c', 'z', 'less_than' ) );
		$this->assertTrue( GVCommon::matches_operation( 'z', '1', '>' ) );
		$this->assertTrue( GVCommon::matches_operation( 'z', '1', 'greater_than' ) );
		$this->assertTrue( GVCommon::matches_operation( 'z', 1, 'greater_than' ) );
		$this->assertFalse( GVCommon::matches_operation( 1, 'b', 'greater_than' ) );
		$this->assertTrue( GVCommon::matches_operation( '4.1E+6', '4100000', 'is' ) );
		$this->assertTrue( GVCommon::matches_operation( '4.1E+6', 4100000, 'is' ) );

		/**
		 * Handle JSON-encoded values
		 * @since 1.22.1
		 */
		$this->assertTrue( GVCommon::matches_operation( '["BMW", "Audi"]', 'Audi', 'in' ), 'JSON vs String' );
		$this->assertTrue( GVCommon::matches_operation( 'Audi', '["BMW", "Audi"]', 'in' ), 'String vs JSON' );
		$this->assertTrue( GVCommon::matches_operation( '["BMW", "Audi"]', '["Audi"]', 'in' ), 'JSON vs JSON' );
		$this->assertTrue( GVCommon::matches_operation( '["BMW", "Audi"]', '["BMW", "Audi"]', 'in' ), 'JSON vs JSON exact match' );
		$this->assertTrue( GVCommon::matches_operation( '["BMW", "Audi"]', '["BMW not exact match", "Audi"]', 'in' ), 'Should match one' );
		$this->assertTrue( GVCommon::matches_operation( '["BMW not exact match", "Audi"]', '["BMW", "Audi"]', 'in' ), 'Should match one' );
		$this->assertFalse( GVCommon::matches_operation( '["BMW", "Audi"]', '["BMW not exact match", "Audi not exact match"]', 'in' ), 'Should not match, even though strings match' );
		$this->assertTrue( GVCommon::matches_operation( '["BMW", "Audi"]', '["Volkswagen"]', 'not_in' ) );
		$this->assertTrue( GVCommon::matches_operation( '["BMW", "Audi"]', '["BM"]', 'not_in' ) );

		$this->assertTrue( GVCommon::matches_operation( array( '1' ), array( '1' ),  'in' ) );
		$this->assertTrue( GVCommon::matches_operation( array( '1', '2', '3', '4' ), array( 4 ),  'in' ) ); // Number strings
		$this->assertTrue( GVCommon::matches_operation( array( '1.12345', '2', '3', '4' ), array( 1.12345 ),  'in' ) ); // Number strings
		$this->assertTrue( GVCommon::matches_operation( array( '1.12345', '2', '3', '4' ), 1.12345,  'in' ), 'Should allow for one to be an array and one not array' );
		$this->assertTrue( GVCommon::matches_operation( '1.12345', 1.12345,  'in' ), 'Should allow for both to not be an array' );
		$this->assertTrue( GVCommon::matches_operation( array(), array( '1' ),  'not_in' ) );
	}

	/**
	 * @since 1.16
	 * @covers GravityView_Field_Date_Created::replace_merge_tag
	 * @covers GVCommon::format_date
	 * @group date_created
	 */
	function test_format_date() {

		$form = $this->factory->form->create_and_get();
		$current_time = current_time( 'mysql' );
		$two_days_from_now  = date( 'Y-m-d H:i:s', strtotime( $current_time . ' +2 day' ) );
		$two_hours_from_now = date( 'Y-m-d H:i:s', strtotime( $current_time . ' +2 hour' ) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'10' 	  => $two_days_from_now,
			'11'	  => $two_hours_from_now
		) );

		$date_created = \GV\Utils::get( $entry, 'date_created' );
		$datepicker = \GV\Utils::get( $entry, '10' );
		$time = \GV\Utils::get( $entry, '11' );

		/**
		 * adjusting date to local configured Time Zone
		 * @see GFCommon::format_date()
		 */
		$entry_gmt_time   = mysql2date( 'G', $date_created );
		$entry_local_time = GFCommon::get_local_timestamp( $entry_gmt_time );

		$datepicker_gmt_time   = mysql2date( 'G', $datepicker );
		$time_gmt_time   = mysql2date( 'G', $time );

		$tests = array(
			GVCommon::format_date( $date_created, 'raw=1') => $date_created,
			GVCommon::format_date( $date_created, array('raw' => true ) ) => $date_created,
			GVCommon::format_date( $date_created, 'raw=1&timestamp=1') => $date_created,
			GVCommon::format_date( $date_created, array('raw' => true, 'timestamp' => 1 ) ) => $date_created,
			GVCommon::format_date( $date_created, 'raw=1&time=1') => $date_created,
			GVCommon::format_date( $date_created, 'raw=1&human=1') => $date_created,
			GVCommon::format_date( $date_created, 'raw=1&format=example') => $date_created,

			GVCommon::format_date( $date_created, 'timestamp=1&raw=1') => $date_created, // Raw logic is first, it wins
			GVCommon::format_date( $date_created, 'timestamp=1') => $entry_local_time,
			GVCommon::format_date( $date_created, 'timestamp=1&time=1') => $entry_local_time,
			GVCommon::format_date( $date_created, 'timestamp=1&human=1') => $entry_local_time,
			GVCommon::format_date( $date_created, 'timestamp=1&format=example') => $entry_local_time,

			// Blog date format
			GVCommon::format_date( $date_created ) => GFCommon::format_date( $date_created, false, '', false ),

			// Blog date format
			GVCommon::format_date( $date_created, 'human=1' ) => GFCommon::format_date( $date_created, true, '', false ),
			GVCommon::format_date( $date_created, array('human' => true) ) => GFCommon::format_date( $date_created, true, '', false ),

			// Blog "date at time" format ("%s at %s")
			GVCommon::format_date( $date_created, 'time=1' ) => GFCommon::format_date( $date_created, false, '', true ),
			GVCommon::format_date( $date_created, array('time' => true) )=> GFCommon::format_date( $date_created, false, '', true ),

			// 1 second ago
			GVCommon::format_date( $date_created, 'diff=1' ) => sprintf( '%s ago', human_time_diff( $entry_gmt_time ) ),
			GVCommon::format_date( $date_created, array('diff' => true ) ) => sprintf( '%s ago', human_time_diff( $entry_gmt_time ) ),
			GVCommon::format_date( $date_created, 'diff=1&format=%s is so long ago' ) => sprintf( '%s is so long ago', human_time_diff( $entry_gmt_time ) ),
			GVCommon::format_date( $date_created, array('diff' => 1, 'format' => '%s is so long ago' ) ) => sprintf( '%s is so long ago', human_time_diff( $entry_gmt_time ) ),

			// 2 days and 2 hours from now
			GVCommon::format_date( $datepicker, 'diff=1&human=1') => sprintf( '%s from now', human_time_diff( $datepicker_gmt_time ) ),
			GVCommon::format_date( $time, 'diff=1&human=1&time=1') => sprintf( '%s from now', human_time_diff( $time_gmt_time ) ),

			// Relative should NOT process other modifiers
			GVCommon::format_date( $date_created, 'diff=1&time=1' ) => sprintf( '%s ago', human_time_diff( $entry_gmt_time ) ),
			GVCommon::format_date( $date_created, 'diff=1&human=1' ) => sprintf( '%s ago', human_time_diff( $entry_gmt_time ) ),
			GVCommon::format_date( $date_created, 'human=1&diff=1' ) => sprintf( '%s ago', human_time_diff( $entry_gmt_time ) ),

			GVCommon::format_date( $date_created, 'format=mdy' ) => GFCommon::format_date( $date_created, false, 'mdy', false ),
			GVCommon::format_date( $date_created, 'human=1&format=m/d/Y' ) => GFCommon::format_date( $date_created, true, 'm/d/Y', false ),

			GVCommon::format_date( $date_created, 'time=1&format=d' ) => GFCommon::format_date( $date_created, false, 'd', true ),
			GVCommon::format_date( $date_created, 'human=1&time=1&format=mdy' ) => GFCommon::format_date( $date_created, true, 'mdy', true ),

			GVCommon::format_date( $date_created, array('format' => 'm/d/Y' ) ) => date_i18n( 'm/d/Y', $entry_local_time, true ),
			GVCommon::format_date( $date_created, array('format' => 'm/d/Y\ \w\i\t\h\ \t\i\m\e\ h\:i\:s' ) ) => date_i18n( 'm/d/Y\ \w\i\t\h\ \t\i\m\e\ h:i:s', $entry_local_time, true ),
		);

		foreach ( $tests as $formatted_date => $expected ) {
			$this->assertEquals( $expected, $formatted_date );
		}
	}

	/**
	 * @group get_forms
	 * @covers GVCommon::get_forms()
	 * @covers ::gravityview_get_forms()
	 */
	function test_get_forms() {

		$this->factory->form->create_many( 10 );

		$i = 0;
		while ( $i < 20 ) {
			GFAPI::add_form( [ 'title' => rand_long_str( 3 ), 'fields' => array() ] );
			$i++;
		}

		$forms = GFAPI::get_forms();

		$gv_forms = GVCommon::get_forms();

		// Make sure same # of forms are fetched
		$this->assertEquals( sizeof( $forms ), sizeof( $gv_forms ) );

		// The GVCommon method should return array with `id` and `title` fields
		$last_form = array_pop( $forms );
		$last_gv_form = array_pop( $gv_forms );

		$this->assertEquals( $last_form, $last_gv_form );

		$gv_forms_asc = GVCommon::get_forms( true, false, 'title', 'ASC' );
		$gv_forms_desc = GVCommon::get_forms( true, false, 'title', 'DESC' );

		$asc_titles = wp_list_pluck( $gv_forms_asc, 'title' );
		$desc_titles = wp_list_pluck( $gv_forms_desc, 'title' );

		$this->assertSame( array_reverse( $asc_titles, true ), $desc_titles );
	}

	/**
	 * @covers GVCommon::get_connected_views
	 * @covers ::gravityview_get_connected_views()
	 */
	function test_get_connected_views() {

		$form_id = $this->factory->form->create();

		$this->factory->view->create_many( 20, array( 'form_id' => $form_id ) );

		$views = GVCommon::get_connected_views( $form_id );

		$this->assertEquals( 20, sizeof( $views ) );
	}

	/**
	 * @covers GVCommon::get_meta_form_id
	 * @covers ::gravityview_get_form_id()
	 */
	function test_get_meta_form_id() {

		$form_id = '1234';
		$view_id = $this->factory->view->create( array( 'form_id' => $form_id ) );

		$this->assertEquals( $form_id, GVCommon::get_meta_form_id( $view_id ) );
	}

	/**
	 * @covers GVCommon::get_meta_template_id
	 * @covers ::gravityview_get_template_id()
	 */
	function test_get_meta_template_id() {

		$form_id = '1234';
		$template_id = 'example_template_id';
		$view_id = $this->factory->view->create( array(
			'form_id' => $form_id,
			'template_id' => $template_id,
		) );

		$this->assertEquals( $template_id, GVCommon::get_meta_template_id( $view_id ) );
	}

	/**
	 * @group link_html
	 * @covers ::gravityview_get_link()
	 * @covers GVCommon::get_link_html
	 */
	function test_get_link_html() {

		$this->assertEquals( '<a href="#">Basic</a>', GVCommon::get_link_html( '#', 'Basic' ) );
		$this->assertEquals( '<a href="tel:1-123-555-1212">1-123-555-1212</a>', GVCommon::get_link_html( 'tel:1-123-555-1212', '1-123-555-1212' ) );
		$this->assertEquals( '<a href="#" title="New Title">Basic</a>', GVCommon::get_link_html( '#', 'Basic', array( 'title' => 'New Title' ) ) );
		$this->assertEquals( '<a href="#" title="New Title">Basic</a>', GVCommon::get_link_html( '#', 'Basic', array( 'title' => 'New Title' ) ) );
		$this->assertEquals( '<a href="#" onclick="alert(&quot;Javascript!&quot;);">Basic</a>', GVCommon::get_link_html( '#', 'Basic', array( 'onclick' => 'alert("Javascript!");' ) ) );

		// Make sure running esc_url_raw
		$href = '//?dangerous=alert("example");&quot;%20;';
		$this->assertEquals( '<a href="'.esc_url_raw( $href ).'">Basic</a>', GVCommon::get_link_html( $href, 'Basic' ) );

		// Test gravityview/get_link/allowed_atts filter
		add_filter( 'gravityview/get_link/allowed_atts', array( $this, '_filter_test_get_link_html' ) );
		$this->assertEquals( '<a href="#">Basic</a>', GVCommon::get_link_html( '#', 'Basic', array( 'onclick' => 'alert("Javascript!");' ) ) );
		remove_filter( '', array( $this, '_filter_test_get_link_html' ) );
	}

	public function _filter_test_get_link_html( $allowed_atts ) {
		unset( $allowed_atts['onclick'] );
		return $allowed_atts;
	}

	/**
	 * @since 1.20
	 * @covers GVCommon::entry_has_transaction_data()
	 */
	public function test_entry_has_transaction_data() {

		$this->assertTrue( GVCommon::entry_has_transaction_data( array( 'transaction_id' => 1 ) ) );
		$this->assertTrue( GVCommon::entry_has_transaction_data( array( 'transaction_id' => NULL, 'payment_status' => 'completed' ) ) );
		$this->assertFalse( GVCommon::entry_has_transaction_data( array( 'transaction_id' => NULL, 'payment_status' => NULL ) ) );
		$this->assertFalse( GVCommon::entry_has_transaction_data( array() ) );
		$this->assertFalse( GVCommon::entry_has_transaction_data('') );

	}

	/**
	 * Test basic filter functionality
	 *
	 * @since 1.20
	 * @covers GVCommon::get_product_field_types
	 */
	public function test_get_product_field_types() {

		remove_all_filters( 'gform_product_field_types' );

		$product_field_types = GVCommon::get_product_field_types();

		$this->assertTrue( is_array( $product_field_types ) );
		$this->assertTrue( in_array( 'product', $product_field_types ) );

		add_filter( 'gform_product_field_types', '__return_empty_array' );

		$empty_product_field_types = GVCommon::get_product_field_types();

		$this->assertEquals( array(), $empty_product_field_types, 'The gform_product_field_types filter did not work' );
	}

	/**
	 * @since 1.21
	 * @covers GVCommon::check_entry_display
	 * @group check_entry_display
	 */
	public function test_check_entry_display() {

		$form = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'] ) );
		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$view_cpt = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'settings' => $settings,
		) );

		$form2 = $this->factory->form->create_and_get();
		$view = \GV\View::from_post( $view_cpt );
		$entry2 = $this->factory->entry->create_and_get( array( 'form_id' => $form2['id'] ) );


		// Entry is empty
		/** @var WP_Error $empty_array */
		$empty_array = GVCommon::check_entry_display( array(), $view );
		$this->assertWPError( $empty_array );
		$this->assertEquals( 'entry_not_found', $empty_array->get_error_code() );

		// Entry is WP_Error
		$wp_error_entry = GVCommon::check_entry_display( new WP_Error('example', 'example!'), $view );
		$this->assertWPError( $wp_error_entry );
		$this->assertEquals( 'entry_not_found', $wp_error_entry->get_error_code() );

		// Empty $entry['form_id'] should return false
		$form_id_not_set = GVCommon::check_entry_display( array( 'not_empty' => true, 'doesnt_have_form_id' => true ), $view );
		$this->assertWPError( $form_id_not_set );
		$this->assertEquals( 'form_id_not_set', $form_id_not_set->get_error_code() );

		// Test empty( $criteria['search_criteria'] )
		add_filter( 'gravityview_search_criteria', function() { return array( 'search_criteria' => null ); } );
		$this->assertEquals( $entry, GVCommon::check_entry_display( $entry, $view ), 'Empty search criteria should have returned original entry' );
		remove_all_filters( 'gravityview_search_criteria' );

		// Test ! is_array( $criteria['search_criteria'] )
		add_filter( 'gravityview_search_criteria', function() { return array( 'search_criteria' => 1 ); } );
		$this->assertEquals( $entry, GVCommon::check_entry_display( $entry, $view ), 'Search criteria not being an array should have returned original entry' );
		remove_all_filters( 'gravityview_search_criteria' );

		// Test whether the current View is connected to the same form as the Entry
		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array( 'example' => 'not_empty'),
			);
		} );
		$view_id_not_match = GVCommon::check_entry_display( $entry2, $view );
		$this->assertWPError( $view_id_not_match );
		$this->assertEquals( 'view_id_not_match', $view_id_not_match->get_error_code() );
		remove_all_filters( 'gravityview_search_criteria' );

		// Test field_filters not array
		add_filter( 'gravityview_search_criteria', function() use ( $view, $entry ) {
			return array(
				'search_criteria' => array(
					'status' => $entry['status'],
				    'field_filters' => 1,
				),
				'context_view_id' => $view->ID,
			);
		} );
		$this->assertEquals( $entry, GVCommon::check_entry_display( $entry, $view ) );
		remove_all_filters( 'gravityview_search_criteria' );


		// Test empty field_filters
		add_filter( 'gravityview_search_criteria', function() use ( $view, $entry ) {
			return array(
				'search_criteria' => array(
					'status' => $entry['status'],
					'field_filters' => array(),
				),
				'context_view_id' => $view->ID,
			);
		} );
		$this->assertEquals( $entry, GVCommon::check_entry_display( $entry, $view ) );
		remove_all_filters( 'gravityview_search_criteria' );

		// Test PASS ALL
		add_filter( 'gravityview_search_criteria', function() use ( $view, $entry ) {
			return array(
				'search_criteria' => array(
					'status' => $entry['status'],
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key' => 'user_agent',
							'value' => $entry['user_agent'],
						),
						array(
							'key' => 'transaction_id',
							'value' => $entry['transaction_id'],
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );
		$this->assertEquals( $entry, GVCommon::check_entry_display( $entry, $view ), 'Should have passed ALL' );
		remove_all_filters( 'gravityview_search_criteria' );


		// Test PASS any
		add_filter( 'gravityview_search_criteria', function() use ( $view, $entry ) {
			return array(
				'search_criteria' => array(
					'status' => $entry['status'],
					'field_filters' => array(
						'mode' => 'any',
						array(
							'key' => 'user_agent',
							'value' => $entry['user_agent'],
						),
						array(
							'key' => 'transaction_id',
							'value' => $entry['transaction_id'] . 'asdasdasdas', // DON'T PASS
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );
		$this->assertEquals( $entry, GVCommon::check_entry_display( $entry, $view ), 'Should have passed ANY' );
		remove_all_filters( 'gravityview_search_criteria' );


		// Test FAIL ALL
		add_filter( 'gravityview_search_criteria', function() use ( $view, $entry ) {
			return array(
				'search_criteria' => array(
					'status' => $entry['status'],
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key' => 'user_agent',
							'value' => $entry['user_agent'],
						),
						array(
							'key' => 'transaction_id',
							'value' => $entry['transaction_id'] . 'asdasdasdas', // DON'T PASS
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );
		$failed_criteria = GVCommon::check_entry_display( $entry, $view );
		$this->assertWPError( $failed_criteria );
		$this->assertEquals( 'failed_criteria', $failed_criteria->get_error_code() );
		remove_all_filters( 'gravityview_search_criteria' );


		// Test FAIL ALL
		add_filter( 'gravityview_search_criteria', function() use ( $view, $entry ) {
			return array(
				'search_criteria' => array(
					'status' => $entry['status'],
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key' => 'user_agent',
							'value' => $entry['user_agent'],
						),
						array(
							'key' => 'transaction_id',
							'value' => $entry['transaction_id'] . 'asdasdasdas', // DON'T PASS
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );
		$failed_criteria = GVCommon::check_entry_display( $entry, $view );
		$this->assertWPError( $failed_criteria );
		$this->assertEquals( 'failed_criteria', $failed_criteria->get_error_code() );

		// View is not given
		/** @var WP_Error $empty_array */
		$no_view = GVCommon::check_entry_display( $entry );
		$this->assertWPError( $no_view );
		$this->assertEquals( 'view_not_supplied', $no_view->get_error_code() );

		remove_all_filters( 'gravityview_search_criteria' );
	}

	/**
	 * Check that entries aren't displayed when $_GET['gvid'] doesn't match current View
	 *
	 * Note: This fails intermittently in local testing with PHPUnit 7.5.20 for an unknown reason. I believe this to be
	 * purely a PHPUnit issue, because I don't understand why there would be a race condition related to $_GET['gvid'].
	 * I've spent far too long trying to understand why it would trigger "Entry failed search_criteria and field_filters"
	 * WP_Error. Let's hope remote unit testing doesn't have the same problems. {@see https://i.gravitykit.com/tdNq84+}
	 *
	 * @since 2.7.2
	 *
	 * @covers GVCommon::check_entry_display()
	 */
	public function test_check_entry_display_gvid() {

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$view = \GV\View::from_post( $view );

		$_GET = array(
			'gvid' => $view->ID,
		);

		// Set context_view_id via filter so search performs properly
		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(),
				'context_view_id' => $view->ID,
			);
		} );

		$gvid_no_view = GVCommon::check_entry_display( $entry );
		$this->assertWPError( $gvid_no_view, print_r( $gvid_no_view, true ) );

		$gvid = GVCommon::check_entry_display( $entry, $view );
		$this->assertEquals( $entry, $gvid, print_r( $gvid, true ) );

		$this->clean_up_global_scope();

		remove_all_filters( 'gravityview_search_criteria' );

		// View ID and $_GET['gvid'] mismatch
		$_GET = array(
			'gvid' => ( $view->ID + 1 ),
		);

		$gvid_mismatch_no_view = GVCommon::check_entry_display( $entry );
		$this->assertWPError( $gvid_mismatch_no_view );

		$gvid_mismatch = GVCommon::check_entry_display( $entry, $view );
		$this->assertWPError( $gvid_mismatch );
		$this->assertEquals( 'view_id_not_match_gvid', $gvid_mismatch->get_error_code() );

		$this->clean_up_global_scope();
	}

	/**
	 * https://github.com/gravityview/GravityView/issues/929
	 */
	public function test_check_entry_display_categories() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$view = \GV\View::from_post( $view );

		$cat_1 = wp_create_category( 'Category 1' );
		$cat_2 = wp_create_category( 'Category 2' );

		foreach ( $form['fields'] as &$field ) {
			if ( 'post_category' == $field->type ) {
				$field = GFCommon::add_categories_as_choices( $field, '' );
			}
		}

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'23.1' => "Category 1:$cat_1",
		) );

		add_filter( 'gravityview_search_criteria', function() use ( $view, $cat_1, $cat_2 ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key' => '23.1',
							'operator' => 'is',
							'value' => "Category 1:$cat_1", // just like with dates below converting to this format is done by AF or custom code.
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertEquals( $entry['id'], $result['id'] );

		remove_all_filters( 'gravityview_search_criteria' );

		add_filter( 'gravityview_search_criteria', function() use ( $view, $cat_1, $cat_2 ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key' => '23.1',
							'operator' => 'is',
							'value' => "Category 2:$cat_2",
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertWPError( $result );

		remove_all_filters( 'gravityview_search_criteria' );
	}

	/**
	 * https://github.com/gravityview/GravityView/issues/1185
	 */
	function test_check_entry_display_dates() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$view = \GV\View::from_post( $view );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'3' => '2011-05-10',
		) );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key' => '3',
							'operator' => 'is',
							'value' => '2011-05-10',
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertEquals( $entry['id'], $result['id'] );

		remove_all_filters( 'gravityview_search_criteria' );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key' => '3',
							'operator' => 'is not',
							'value' => '2011-05-10',
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertWPError( $result );

		remove_all_filters( 'gravityview_search_criteria' );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key' => '3',
							'operator' => '<',
							'value' => '2011-05-09',
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertWPError( $result );

		remove_all_filters( 'gravityview_search_criteria' );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key' => '3',
							'operator' => '>',
							'value' => '2011-05-08',
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertEquals( $entry['id'], $result['id'] );

		remove_all_filters( 'gravityview_search_criteria' );
	}

	/**
	 * https://github.com/gravityview/GravityView/issues/1185
	 */
	function test_check_entry_display_date_strings() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$view = \GV\View::from_post( $view );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'3' => date( 'Y-m-d', strtotime( 'today' ) ),
		) );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key' => '3',
							'operator' => 'is',
							'value' => date( 'Y-m-d', strtotime( 'today' ) ),
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertEquals( $entry['id'], $result['id'] );

		remove_all_filters( 'gravityview_search_criteria' );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key' => '3',
							'operator' => 'is not',
							'value' => date( 'Y-m-d', strtotime( 'today' ) ),
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertWPError( $result );

		remove_all_filters( 'gravityview_search_criteria' );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key' => '3',
							'operator' => '<',
							'value' => 'next month',
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertEquals( $entry['id'], $result['id'] );

		remove_all_filters( 'gravityview_search_criteria' );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'key' => '3',
							'operator' => '>',
							'value' => 'tomorrow',
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertWPError( $result );

		remove_all_filters( 'gravityview_search_criteria' );
	}

	/**
	 * https://github.com/gravityview/GravityView/issues/1189
	 */
	function test_check_entry_display_any_fields() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$view = \GV\View::from_post( $view );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'1' => '10',
			'2' => '1000',
		) );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'operator' => 'is',
							'value' => '100',
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertWPError( $result );

		remove_all_filters( 'gravityview_search_criteria' );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'operator' => 'is',
							'value' => '1000',
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertEquals( $entry['id'], $result['id'] );

		remove_all_filters( 'gravityview_search_criteria' );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'operator' => 'is',
							'value' => '10',
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertEquals( $entry['id'], $result['id'] );

		remove_all_filters( 'gravityview_search_criteria' );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'all',
						array(
							'operator' => 'contains',
							'value' => '1',
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertEquals( $entry['id'], $result['id'] );

		remove_all_filters( 'gravityview_search_criteria' );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'any',
						array(
							'operator' => 'is',
							'value' => '1',
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertWPError( $result );

		remove_all_filters( 'gravityview_search_criteria' );

		add_filter( 'gravityview_search_criteria', function() use ( $view ) {
			return array(
				'search_criteria' => array(
					'field_filters' => array(
						'mode' => 'any',
						array(
							'operator' => 'is',
							'value' => '10',
						),
					),
				),
				'context_view_id' => $view->ID,
			);
		} );

		$result = GVCommon::check_entry_display( $entry, $view );
		$this->assertEquals( $entry['id'], $result['id'] );

		remove_all_filters( 'gravityview_search_criteria' );
	}

	/**
	 * https://github.com/gravityview/GravityView/issues/929
	 */
	function test_check_entry_display_gf_query_filters() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'1' => '10',
			'2' => '1000',
		) );

		add_filter( 'gravityview/view/query', $query_callback = function( $query ) {
			$q = $query->_introspect();

			$condition = new GF_Query_Condition(
				new GF_Query_Column( 'status' ),
				GF_Query_Condition::EQ,
				new GF_Query_Literal( 'does not exist' )
			);

			$query->where( \GF_Query_Condition::_and( $q['where'], $condition ) );
		} );

		$result = GVCommon::check_entry_display( $entry, \GV\View::from_post( $view ) );
		$this->assertWPError( $result );

		remove_filter( 'gravityview/view/query', $query_callback );
	}

	/**
	 * @since 1.20
	 * @covers \GVCommon::calculate_get_entries_criteria()
	 * @covers \GravityView_frontend::set_context_view_id()
	 * @covers \GVCommon::check_entry_display()
	 *
	 * @group calculate_get_entries_criteria
	 */
	function test_calculate_get_entries_criteria() {
		GravityView_frontend::$instance = null;
		GravityView_View_Data::$instance = null;

		$default_values = array(
			'search_criteria' => null,
			'sorting' => null,
			'paging' => null,
			'cache' => true,
			'context_view_id' => null,
		);

		// When no View ID is set, everything should be null
		$this->assertEquals( $default_values, \GVCommon::calculate_get_entries_criteria() );

		$this->_calculate_get_entries_criteria_add_operator( $default_values );

		$this->_calculate_get_entries_criteria_dates( $default_values );

		$this->_calculate_get_entries_criteria_context_view_id( $default_values );

		// Unset [field_filters][mode] if it's the only key that exists in [field_filters]
		$_field_values_only_mode_expected = $_field_values_only_mode = $default_values;
		$_field_values_only_mode['search_criteria']['field_filters'] = array( 'mode' => 'all' );
		$_field_values_only_mode_expected['search_criteria']['field_filters'] = array();
		$this->assertEquals( $_field_values_only_mode_expected, GVCommon::calculate_get_entries_criteria( $_field_values_only_mode ) );

		// Test `gravityview_search_criteria` filter
		add_filter( 'gravityview_search_criteria', '__return_empty_array' );
		$this->assertEquals( array(), GVCommon::calculate_get_entries_criteria( $default_values ) );
		remove_filter( 'gravityview_search_criteria', '__return_empty_array' );

	}

	/**
	 * @since 1.20
	 */
	private function _calculate_get_entries_criteria_context_view_id( $default_values ) {

		$expected_get_context_view_id = $default_values;

		// Test when is single entry
		GravityView_frontend::getInstance()->setSingleEntry( 48 );
		GravityView_frontend::getInstance()->set_context_view_id( 123 );
		$expected_get_context_view_id['context_view_id'] = 123;
		$this->assertEquals( $expected_get_context_view_id, GVCommon::calculate_get_entries_criteria() );
		GravityView_frontend::getInstance()->setSingleEntry( false ); // Reset is single entry
		GravityView_frontend::getInstance()->setGvOutputData( GravityView_View_Data::getInstance() );
		GravityView_frontend::getInstance()->set_context_view_id( null );

		// If `context_view_id` is passed, then use it.
		unset( $_GET['view_id'] );
		$criteria = array( 'context_view_id' => 345 );
		$expected_get_context_view_id['context_view_id'] = 345;
		$this->assertEquals( $expected_get_context_view_id, GVCommon::calculate_get_entries_criteria( $criteria ) );


		// Test when action=delete is set but view ID isn't
		$_GET['action'] = 'delete';
		unset( $_GET['view_id'] );
		$expected_get_context_view_id['context_view_id'] = NULL;
		$this->assertEquals( $expected_get_context_view_id, GVCommon::calculate_get_entries_criteria() );

		// Test when action=delete is set AND view ID is too
		$_GET['action'] = 'delete';
		$_GET['view_id'] = 456;
		$expected_get_context_view_id['context_view_id'] = 456;
		$this->assertEquals( $expected_get_context_view_id, GVCommon::calculate_get_entries_criteria() );

		$views = $this->factory->view->create_many( 2 );
		GravityView_frontend::getInstance()->setGvOutputData( GravityView_View_Data::getInstance() );
		GravityView_frontend::getInstance()->getGvOutputData()->add_view( $views );
		GravityView_frontend::getInstance()->set_context_view_id( 234 );
		$expected_get_context_view_id['context_view_id'] = 234;
		$this->assertEquals( $expected_get_context_view_id, GVCommon::calculate_get_entries_criteria() );
		GravityView_frontend::getInstance()->set_context_view_id( null );

		/** Cleanup */
		GravityView_frontend::$instance = null;
		GravityView_View_Data::$instance = null;
		unset( $_GET['action'] );
		unset( $_GET['view_id'] );
	}

	/**
	 * Subset to test date handling
	 *
	 * @since 1.20
	 * @see test_calculate_get_entries_criteria
	 *
	 * @param array $default_values
	 */
	private function _calculate_get_entries_criteria_dates( $default_values ) {

		// Invalid dates get unset
		$_date_create_false = $_date_create_false_expected = $default_values;
		$_date_create_false['search_criteria']['start_date'] = 'asdsadsd';
		$_date_create_false['search_criteria']['end_date'] = 'asdsadsd';
		$_date_create_false_expected['search_criteria'] = array();
		$this->assertEquals( $_date_create_false_expected, GVCommon::calculate_get_entries_criteria( $_date_create_false ) );


		// Used multiple times below
		$timestamp = '2014-07-24';
		$datetime = date_create( $timestamp );
		$properly_formatted_date = $datetime->format( 'Y-m-d H:i:s' );
		$improperly_formatted_date = $datetime->format( 'Y-m-d' );

		// Format dates as GF wants them: Y-m-d H:i:s
		$_date_create_improper = $_date_create_improper_expected = $default_values;
		$_date_create_improper['search_criteria']['start_date'] = $improperly_formatted_date;
		$_date_create_improper_expected['search_criteria']['start_date'] = $properly_formatted_date;
		$this->assertEquals( $_date_create_improper_expected, GVCommon::calculate_get_entries_criteria( $_date_create_improper ) );

		// Start time valid, end time not valid
		$_date_create_end_invalid = $_date_create_end_invalid_expected = $default_values;
		$_date_create_end_invalid['search_criteria']['start_date'] = $improperly_formatted_date;
		$_date_create_end_invalid['search_criteria']['end_date'] = 'asdsadsd';
		$_date_create_end_invalid_expected['search_criteria'] = array(
			'start_date' => $properly_formatted_date,
		);

		$this->assertEquals( $_date_create_end_invalid_expected, GVCommon::calculate_get_entries_criteria( $_date_create_end_invalid ) );
	}

	/**
	 * Subset to test how operators are managed
	 *
	 * @since 1.20
	 *
	 * @see test_calculate_get_entries_criteria
	 *
	 * @param array $default_values
	 */
	private function _calculate_get_entries_criteria_add_operator( $default_values ) {

		$search_criteria_without_operator = array(
			'search_criteria' => array(
				'field_filters' => array(
					'mode' => 'all',
					0 => array(
						'key' => 'created_by',
						'value' => 'example',
					)
				),
			),
		);

		$search_criteria_without_operator_expected = $default_values;
		$search_criteria_without_operator_expected['search_criteria'] = array(
			'field_filters' => array(
				'mode' => 'all',
				0 => array(
					'key' => 'created_by',
					'value' => 'example',
					'operator' => 'contains',
				)
			),
		);

		$this->assertEquals( $search_criteria_without_operator_expected, GVCommon::calculate_get_entries_criteria( $search_criteria_without_operator ) );


		// Modify the search operator returned
		add_filter( 'gravityview_search_operator', function() {
			return 'is';
		});

		$search_criteria_without_operator_with_filter_expected = $default_values;

		$search_criteria_without_operator_with_filter_expected['search_criteria'] = array(
			'field_filters' => array(
				'mode' => 'all',
				0 => array(
					'key' => 'created_by',
					'value' => 'example',
					'operator' => 'is',
				),
			)
		);

		$this->assertEquals( $search_criteria_without_operator_with_filter_expected, GVCommon::calculate_get_entries_criteria( $search_criteria_without_operator ) );

		// Cleanup
		remove_all_filters( 'gravityview_search_operator' );
	}

	/**
	 * @covers GVCommon::has_shortcode_r
	 * @group has_shortcode
	 */
	function test_has_shortcode_r() {

		add_shortcode( 'shortcode_one', '__return_empty_string' );
		add_shortcode( 'shortcode_two', '__return_empty_string' );

		$shortcode_exists = array(
			'[gravity_view]' => false,
			'gravityview' => false,
			'[gravityview' => false,
			'[gravity view]' => false,
			'[gravityview]' => array( '[gravityview]' ),
			'[shortcode_one][shortcode_two][gravityview][/shortcode_two][/shortcode_one]' => array( '[gravityview]' ),
			'[shortcode_one] [shortcode_two] [gravityview /] [/shortcode_two] [/shortcode_one]' => array( '[gravityview /]' ),
			'[shortcode_one][gravityview] [gravityview id="12345" attributes="custom"][/shortcode_one]' => array( '[gravityview]', '[gravityview id="12345" attributes="custom"]' ),
			'[shortcode_one][shortcode_two][gravityview /][/shortcode_two][/shortcode_one]' => array( '[gravityview /]' ),
			'[embed_wrapper][embed_level_1][embed_level_2][gravityview id="3416"][/embed_level_2][/embed_level_1][embed_level_1][embed_level_2][gravityview id="3418"][/embed_level_2][/embed_level_1][/embed_wrapper]' => array( '[gravityview id="3416"]', '[gravityview id="3418"]' ),
		);

		foreach ( $shortcode_exists as $test => $expected ) {

			$result = GVCommon::has_shortcode_r( $test );

			// Expected to be false
			if ( false === $expected ) {
				$this->assertFalse( $result );
			} else {

				$this->assertEquals( sizeof( $expected ), sizeof( $result ), 'different # of results' );

				foreach ( $expected as $key => $item ) {
					// Compare expected value against full shortcode string
					$this->assertTrue( isset( $result[ $key ] ) );
					$this->assertTrue( isset( $result[ $key ][0] ) );
					$this->assertEquals( $expected[ $key ], $result[ $key ][0] );
				}
			}
		}

	}

}
