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
	 * @since 1.16
	 * @covers GravityView_Field_Date_Created::replace_merge_tag
	 * @covers GVCommon::format_date
	 * @group date_created
	 */
	function test_format_date() {

		$form = $this->factory->form->create_and_get();

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
		) );

		$date_created = rgar( $entry, 'date_created' );

		/**
		 * adjusting date to local configured Time Zone
		 * @see GFCommon::format_date()
		 */
		$entry_gmt_time   = mysql2date( 'G', $date_created );
		$entry_local_time = GFCommon::get_local_timestamp( $entry_gmt_time );

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

		$this->factory->form->create_many( 5 );

		$forms = GFAPI::get_forms();

		$gv_forms = GVCommon::get_forms();

		// Make sure same # of forms are fetched
		$this->assertEquals( sizeof( $forms ), sizeof( $gv_forms ) );

		// The GVCommon method should return array with `id` and `title` fields
		$last_form = array_pop( $forms );
		$last_gv_form = array_pop( $gv_forms );

		$this->assertEquals( $last_form, $last_gv_form );
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

	public function test_check_entry_display() {

		$form = $this->factory->form->create();

		$entry = $this->factory->entry->create( array( 'form_id' => $form['id'] ) );

		GVCommon::check_entry_display( $entry );

		// If `context_view_id`

		//Make sure the current View is connected to the same form as the Entry
	}

	/**
	 * @since 1.20
	 * @covers GVCommon::calculate_get_entries_criteria()
	 * @covers GravityView_frontend::set_context_view_id()
	 * @group calculate_get_entries_criteria
	 */
	function test_calculate_get_entries_criteria() {

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
