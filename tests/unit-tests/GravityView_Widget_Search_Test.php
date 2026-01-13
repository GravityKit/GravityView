<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group widgets
 * @since 1.21
 */
class GravityView_Widget_Search_Test extends GV_UnitTestCase {

	/**
	 * @var \GravityView_Widget_Search $widget
	 */
	public $widget;

	function setUp() : void {
		parent::setUp();
		$this->widget = new GravityView_Widget_Search;
	}

	/**
	 * @covers GravityView_Widget_Search::filter_entries()
	 * @group GravityView_Widget_Search
	 * @since 1.21
	 */
	function test_filter_entries() {
		$this->_test_word_splitting();

		// TODO: Cover prepare_field_filter() - Allow for all supported comparison types
	}

	private function _test_word_splitting() {
		$_GET = array();

		$this->assertEquals( array('original value'), $this->widget->filter_entries( array('original value'), null, array(), true ), 'when $_GET is empty, $search_criteria should be returned' );

		$view = $this->factory->view->create_and_get( array(
			'widgets' => array( '_' => array(
				array(
					'id' => 'search_bar',
					'search_fields' => json_encode( array(
						array( 'field' => 'search_all' ),
						array( 'field' => 'entry_id' ),
						array( 'field' => 'entry_date' ),
						array( 'field' => 'created_by' ),
					) ),
				),
			) ),
		) );
		$args = array( 'id' => $view->ID );

		$search_criteria_single = array(
			'field_filters' => array(
				'mode' => 'any',
				array(
					'key' => null,
					'value' => 'with spaces',
					'operator' => 'contains',
				),
			)
		);

		$_GET = array(
			'gv_search' => ' with  spaces'
		);
		add_filter( 'gravityview/search-all-split-words', '__return_false' );
		$this->assertEquals( $search_criteria_single, $this->widget->filter_entries( array(), null, $args, true ) );
		remove_filter( 'gravityview/search-all-split-words', '__return_false' );

		$search_criteria_split = array(
			'field_filters' => array(
				'mode' => 'any',
				array(
					'key' => null,
					'value' => 'with',
					'operator' => 'contains',
				),
				array(
					'key' => null,
					'value' => 'spaces',
					'operator' => 'contains',
				),
			)
		);

		$_GET = array(
			'gv_search' => ' with  spaces'
		);
		$this->assertEquals( $search_criteria_split, $this->widget->filter_entries( array(), null, $args, true ) );

		// Exact search.
		$search_criteria_single_exact = $search_criteria_single;
		$search_criteria_single_exact['field_filters'][0]['value'] = ' with  spaces';

		$_GET = [ 'gv_search' => '" with  spaces"' ];
		$this->assertEquals( $search_criteria_single_exact, $this->widget->filter_entries( array(), null, $args, true ) );

		$_GET = [ 'gv_search' => '" space " "another one" and two' ];

		$this->assertEquals(
			[
				'field_filters' => [
					'mode' => 'any',
					[ 'key' => null, 'value' => ' space ', 'operator' => 'contains' ],
					[ 'key' => null, 'value' => 'another one', 'operator' => 'contains' ],
					[ 'key' => null, 'value' => 'and', 'operator' => 'contains' ],
					[ 'key' => null, 'value' => 'two', 'operator' => 'contains' ],
				]
			],
			$this->widget->filter_entries( array(), null, $args, true )
		);

		$_GET = [ 'gv_search' => '-"excluded spaces" -another' ];

		$this->assertEquals(
			[
				'field_filters' => [
					'mode' => 'any',
					[ 'key' => null, 'value' => 'excluded spaces', 'operator' => 'not contains' ],
					[ 'key' => null, 'value' => 'another', 'operator' => 'not contains' ],
				]
			],
			$this->widget->filter_entries( array(), null, $args, true )
		);

		// Additive search
		$_GET = [ 'gv_search' => 'world +"included spaces" +another hello' ];
		$this->assertEquals(
			[
				'field_filters' => [
					'mode' => 'any',
					[ 'key' => null, 'value' => 'included spaces', 'operator' => 'contains', 'required' => true ],
					[ 'key' => null, 'value' => 'world', 'operator' => 'contains' ],
					[ 'key' => null, 'value' => 'another', 'operator' => 'contains', 'required' => true ],
					[ 'key' => null, 'value' => 'hello', 'operator' => 'contains' ],
				]
			],
			$this->widget->filter_entries( array(), null, $args, true )
		);

		// Combined search
		$_GET = [ 'gv_search' => 'regular words +with -without' ];
		$this->assertEquals(
			[
				'field_filters' => [
					'mode' => 'any',
					[ 'key' => null, 'value' => 'regular', 'operator' => 'contains'],
					[ 'key' => null, 'value' => 'words', 'operator' => 'contains'],
					[ 'key' => null, 'value' => 'with', 'operator' => 'contains', 'required' => true ],
					[ 'key' => null, 'value' => 'without', 'operator' => 'not contains'],
				]
			],
			$this->widget->filter_entries( array(), null, $args, true )
		);

		$_GET = array(
			'gv_search' => '%20with%20%20spaces'
		);
		$this->assertEquals( $search_criteria_split, $this->widget->filter_entries( array(), null, $args, true ) );

		$_GET = array(
			'gv_search' => '%20with%20%20spaces'
		);

		$search_criteria_split_mode = $search_criteria_split;
		$search_criteria_split_mode['field_filters']['mode'] = 'all';

		add_filter( 'gravityview/search/mode', function() { return 'all'; } );
		$this->assertEquals( $search_criteria_split_mode, $this->widget->filter_entries( array(), null, $args, true ) );
		remove_all_filters( 'gravityview/search/mode' );

		$_GET = array(
			'gv_search' => 'with%20%20spaces',
			'mode' => 'all',
		);
		$this->assertEquals( $search_criteria_split_mode, $this->widget->filter_entries( array(), null, $args, true ) );


		// Test ?gv_id param
		$_GET = array(
			'gv_search' => 'with%20spaces',
			'gv_id' => 12,
			'gv_by' => 547,
		);
		$search_criteria_with_more_params = $search_criteria_split;
		$search_criteria_with_more_params['field_filters'][] = array(
			'key' => 'id',
			'value' => 12,
			'operator' => '=',
		);
		$search_criteria_with_more_params['field_filters'][] = array(
			'key' => 'created_by',
			'value' => 547,
			'operator' => '=',
		);

		$this->assertEquals( $search_criteria_with_more_params, $this->widget->filter_entries( array(), null, $args, true ) );

		$start = '1997-03-28';
		$end = '2017-10-03';

		add_filter( 'gravityview/widgets/search/datepicker/format', function() { return 'ymd_dash'; } );

		// Test dates
		$_GET = array(
			'gv_start' => $start,
			'gv_end' => $end,
		);

		$search_criteria_dates = array(
			'start_date' => get_gmt_from_date( $start ),
			'end_date' => get_gmt_from_date( '2017-10-03 23:59:59' /* + 1 day */ ),
			'field_filters' => array(
				'mode' => 'any',
			),
		);
		$this->assertEquals( $search_criteria_dates, $this->widget->filter_entries( array(), null, $args, true ) );

		$_GET = array();

		remove_all_filters( 'gravityview/widgets/search/datepicker/format' );
	}

	public function test_search_limited_fields() {
		/**
		 * gv_search query parameter.
		 *
		 * SHOULD NOT search if "search_all" setting is not set in Search Widget "Search Field" setting.
		 */
		$view = $this->factory->view->create_and_get( array(
			'fields' => array( '_' => array(
				array( 'id' => '1.1' ),
			) ),
			'widgets' => array( '_' => array(
				array(
					'id' => 'search_bar',
					'search_fields' => json_encode( array(
						array( 'field' => '4' ),
					) ),
				),
			) ),
		) );

		add_filter( 'gravityview/widgets/search/datepicker/format', function() { return 'ymd_dash'; } );

		$_GET = array( 'gv_search' => '_' );

		$search_criteria = array(
			'field_filters' => array(
				'mode' => 'any',
			),
		);

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		/**
		 * gv_search query paramter.
		 *
		 * SHOULD NOT search in non-visible fields.
		 *
		 * @todo impossible as it would require an "any" relationship
		 *  so return to this when new query implementation is ready.
		 */

		/**
		 * gv_start, gv_end query parameters.
		 *
		 * SHOULD NOT search unless "entry_date" is set in Search Widget "Search Field" settings.
		 */
		$view = $this->factory->view->create_and_get( array(
			'fields' => array( '_' => array(
				array( 'id' => '1.1' ),
			) ),
			'widgets' => array( '_' => array(
				array(
					'id' => 'search_bar',
					'search_fields' => json_encode( array(
						array( 'field' => '1.1' ),
					) ),
				),
			) ),
		) );

		$_GET = array( 'gv_start' => '2017-01-01', 'gv_end' => '2017-12-31' );

		$search_criteria = array(
			'field_filters' => array(
				'mode' => 'any',
			),
		);

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		/**
		 * gv_start, gv_end query parameters.
		 *
		 * SHOULD NOT search outside of the View settings Start Date or End Dates, if set.
		 */
		$view = $this->factory->view->create_and_get( array(
			'fields' => array( '_' => array(
				array( 'id' => '1.1' ),
			) ),
			'settings' => array(
				'start_date' => '2017-05-01',
			),
			'widgets' => array( '_' => array(
				array(
					'id' => 'search_bar',
					'search_fields' => json_encode( array(
						array( 'field' => 'entry_date' ),
					) ),
				),
			) ),
		) );

		$_GET = array( 'gv_start' => '2017-01-01', 'gv_end' => '2017-12-31' );

		$search_criteria = array(
			'field_filters' => array(
				'mode' => 'any',
			),
			'start_date' => '2017-05-01 00:00:00',
			'end_date' => '2017-12-31 23:59:59',
		);

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		/**
		 * gv_id query parameter.
		 *
		 * SHOULD NOT search unless "entry_id" is set in Search Widget "Search Field" settings.
		 */
		$view = $this->factory->view->create_and_get( array(
			'fields' => array( '_' => array(
				array( 'id' => '1.1' ),
			) ),
			'widgets' => array( '_' => array(
				array(
					'id' => 'search_bar',
					'search_fields' => json_encode( array(
						array( 'field' => 'entry_date' ),
					) ),
				),
			) ),
		) );

		$_GET = array( 'gv_id' => '_' );

		$search_criteria = array(
			'field_filters' => array(
				'mode' => 'any',
			),
		);

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		/**
		 * gv_by query parameter.
		 *
		 * SHOULD NOT search unless "created_by" is set in Search Widget "Search Field" settings.
		 */
		$view = $this->factory->view->create_and_get( array(
			'fields' => array( '_' => array(
				array( 'id' => '1.1' ),
			) ),
			'widgets' => array( '_' => array(
				array(
					'id' => 'search_bar',
					'search_fields' => json_encode( array(
						array( 'field' => 'entry_id' ),
					) ),
				),
			) ),
		) );

		$_GET = array( 'gv_by' => '1', 'gv_id' => '3' );

		$search_criteria = array(
			'field_filters' => array(
				array(
					'key' => 'id',
					'value' => '3',
					'operator' => '=',
				),
				'mode' => 'any',
			),
		);

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		/**
		 * gv_by query parameter.
		 *
		 * SHOULD NOT search unless "created_by" is set in Search Widget "Search Field" settings.
		 */
		$view = $this->factory->view->create_and_get( array(
			'fields' => array( '_' => array(
				array( 'id' => '1.1' ),
			) ),
			'widgets' => array( '_' => array(
				array(
					'id' => 'search_bar',
					'search_fields' => json_encode( array(
						array( 'field' => 'entry_id' ),
						array( 'field' => 'created_by' ),
					) ),
				),
			) ),
		) );

		$_GET = array( 'gv_by' => '1', 'gv_id' => '3' );

		$search_criteria = array(
			'field_filters' => array(
				array(
					'key' => 'id',
					'value' => '3',
					'operator' => '=',
				),
				array(
					'key' => 'created_by',
					'value' => '1',
					'operator' => '=',
				),
				'mode' => 'any',
			),
		);

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		/**
		 * filter_* query parameters.
		 *
		 * SHOULD NOT search if field is absent from Search Widget "Search Field" settings.
		 */
		$view = $this->factory->view->create_and_get( array(
			'fields' => array( '_' => array(
				array( 'id' => '1.1' ),
			) ),
			'widgets' => array( '_' => array(
				array(
					'id' => 'search_bar',
					'search_fields' => json_encode( array(
						array( 'field' => '1.1' ),
					) ),
				),
			) ),
		) );

		$_GET = array( 'filter_1_2' => '_' );

		$search_criteria = array(
			'field_filters' => array(
				'mode' => 'any',
			),
		);

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		$_GET = array( 'input_1_2' => '_' );

		$search_criteria = array(
			'field_filters' => array(
				'mode' => 'any',
			),
		);

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		$_GET = array();

		remove_all_filters( 'gravityview/widgets/search/datepicker/format' );
	}

	public function test_filter_entries_gv_start_end_time() {
		$_GET = array(
			'gv_start' => '2018-04-07',
			'gv_end' => '2018-04-07',
		);

		add_filter( 'gravityview/widgets/search/datepicker/format', function() { return 'ymd_dash'; } );

		$view = $this->factory->view->create_and_get( array(
			'fields' => array( '_' => array(
				array( 'id' => '1.1' ),
			) ),
			'widgets' => array( '_' => array(
				array(
					'id' => 'search_bar',
					'search_fields' => json_encode( array(
						array( 'field' => 'entry_date' ),
					) ),
				),
			) ),
		) );

		add_filter( 'pre_option_timezone_string', $callback = function() {
			return 'Etc/GMT+0';
		} );

		$search_criteria_dates = array(
			'start_date' => '2018-04-07 00:00:00',
			'end_date' => '2018-04-07 23:59:59',
			'field_filters' => array(
				'mode' => 'any',
			),
		);
		$this->assertEquals( $search_criteria_dates, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		remove_filter( 'pre_option_timezone_string', $callback );

		add_filter('gravityview_date_created_adjust_timezone', '__return_true' );
		add_filter( 'pre_option_timezone_string', $callback = function() {
			return 'Etc/GMT+5';
		} );

		$search_criteria_dates = array(
			'start_date' => '2018-04-07 05:00:00',
			'end_date' => '2018-04-08 05:00:00',
			'field_filters' => array(
				'mode' => 'any',
			),
		);
		$this->assertEquals( $search_criteria_dates, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		add_filter('gravityview_date_created_adjust_timezone', '__return_true' );
		remove_filter( 'pre_option_timezone_string', $callback );

		$_GET = array();

		remove_all_filters( 'gravityview/widgets/search/datepicker/format' );
	}

	/**
	 * @dataProvider get_gv_start_end_formats
	 */
	public function test_filter_entries_gv_start_end_formats( $format, $dates, $name ) {
		$view = $this->factory->view->create_and_get( array(
			'fields' => array( '_' => array(
				array( 'id' => '1.1' ),
			) ),
			'widgets' => array( '_' => array(
				array(
					'id' => 'search_bar',
					'search_fields' => json_encode( array(
						array( 'field' => 'entry_date' ),
					) ),
				),
			) ),
		) );

		$search_criteria_dates = array(
			'start_date' => '2018-02-01 00:00:00',
			'end_date' => '2018-04-03 23:59:59',
			'field_filters' => array(
				'mode' => 'any',
			),
		);

		$_GET = $dates;

		add_filter( 'gravityview/widgets/search/datepicker/format', function() use ( $name ) { return $name; } );

		$this->assertEquals( $search_criteria_dates, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		remove_all_filters( 'gravityview/widgets/search/datepicker/format' );

		$_GET = array();
	}

	/**
	 * https://docs.gravitykit.com/article/115-changing-the-format-of-the-search-widgets-date-picker
	 */
	public function get_gv_start_end_formats() {
		return array(
			array( 'mm/dd/yyyy', array( 'gv_start' => '02/01/2018', 'gv_end' => '04/03/2018' ), 'mdy' ),
			array( 'mm/dd/yyyy', array( 'gv_start' => '02/01/2018', 'gv_end' => '04/03/2018' ), 'invalid! This should result in mdy.' ),

			array( 'yyyy-mm-dd', array( 'gv_start' => '2018-02-01', 'gv_end' => '2018-04-03' ), 'ymd_dash' ),
			array( 'yyyy/mm/dd', array( 'gv_start' => '2018/02/01', 'gv_end' => '2018/04/03' ), 'ymd_slash' ),
			array( 'yyyy.mm.dd', array( 'gv_start' => '2018.02.01', 'gv_end' => '2018.04.03' ), 'ymd_dot' ),

			array( 'dd/mm/yyyy', array( 'gv_start' => '01/02/2018', 'gv_end' => '03/04/2018' ), 'dmy' ),
			array( 'dd-mm-yyyy', array( 'gv_start' => '01-02-2018', 'gv_end' => '03-04-2018' ), 'dmy_dash' ),
			array( 'dd.mm.yyyy', array( 'gv_start' => '01.02.2018', 'gv_end' => '03.04.2018' ), 'dmy_dot' ),
		);
	}

	/**
	 * @dataProvider get_date_filter_formats
	 */
	public function test_date_filter_formats( $format, $dates, $name ) {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		global $post;

		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '3',
						'label' => 'Date',
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

		$search_criteria_dates = array(
			'field_filters' => array(
				'mode' => 'any',
				array(
					'key' => '3',
					'value' => '2018-02-01',
					'form_id' => $form['id'],
					'operator' => 'is'
				),
			),
		);

		$_GET = $dates;

		add_filter( 'gravityview/widgets/search/datepicker/format', function() use ( $name ) { return $name; } );

		$this->assertEquals( $search_criteria_dates, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		remove_all_filters( 'gravityview/widgets/search/datepicker/format' );

		$_GET = array();
	}

	public function get_date_filter_formats() {
		return array(
			array( 'mm/dd/yyyy', array( 'filter_3' => '02/01/2018' ), 'mdy' ),
			array( 'mm/dd/yyyy', array( 'filter_3' => '02/01/2018' ), 'invalid! This should result in mdy.' ),

			array( 'yyyy-mm-dd', array( 'filter_3' => '2018-02-01' ), 'ymd_dash' ),
			array( 'yyyy/mm/dd', array( 'filter_3' => '2018/02/01' ), 'ymd_slash' ),
			array( 'yyyy.mm.dd', array( 'filter_3' => '2018.02.01' ), 'ymd_dot' ),

			array( 'dd/mm/yyyy', array( 'filter_3' => '01/02/2018' ), 'dmy' ),
			array( 'dd-mm-yyyy', array( 'filter_3' => '01-02-2018' ), 'dmy_dash' ),
			array( 'dd.mm.yyyy', array( 'filter_3' => '01.02.2018' ), 'dmy_dot' ),

			array( 'mm/dd/yyyy', array( 'input_3' => '02/01/2018' ), 'mdy' ),
			array( 'mm/dd/yyyy', array( 'input_3' => '02/01/2018' ), 'invalid! This should result in mdy.' ),

			array( 'yyyy-mm-dd', array( 'input_3' => '2018-02-01' ), 'ymd_dash' ),
			array( 'yyyy/mm/dd', array( 'input_3' => '2018/02/01' ), 'ymd_slash' ),
			array( 'yyyy.mm.dd', array( 'input_3' => '2018.02.01' ), 'ymd_dot' ),

			array( 'dd/mm/yyyy', array( 'input_3' => '01/02/2018' ), 'dmy' ),
			array( 'dd-mm-yyyy', array( 'input_3' => '01-02-2018' ), 'dmy_dash' ),
			array( 'dd.mm.yyyy', array( 'input_3' => '01.02.2018' ), 'dmy_dot' ),
		);
	}

	public function test_search_is_approved_gf_query() {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => array(
				'show_only_approved' => true,
			),
			'fields' => array(
				'directory_table-columns' => array( wp_generate_password( 4, false ) => array(
						'id' => '4',
						'label' => 'Email',
					),
					wp_generate_password( 16, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
				),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"4","input":"input_text"},{"field":"16","input":"input_text"}]',
						'search_mode' => 'any',
					),
				),
			),
		) );
		$view = \GV\View::from_post( $post );

		/** Approved entry. */
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',

			'4'  => 'support@gravitykit.com',
			'16' => 'Contact us if you have any questions.',
		) );
		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		/** Approved sentinel. */
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',

			'4'  => 'gravitykit.com',
			'16' => 'Our website.',
		) );
		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		/** Unapproved entry. */
		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',

			'4'  => 'support@gravitykit.com',
			'16' => 'Contact us if you have any questions.',
		) );

		$_GET = array(
			'filter_4'  => 'support',
			'filter_16' => 'support', // In mode "any" this should be ignored
		);

		$this->assertEquals( 1, $view->get_entries()->count() );

		$_GET = array(
			'input_4'  => 'support',
			'input_16' => 'support', // In mode "any" this should be ignored
		);

		$this->assertEquals( 1, $view->get_entries()->count() );

		$_GET = array();
	}

	/**
	 * @dataProvider get_test_approval_status_search
	 */
	public function test_approval_status_search( $show_only_approved, $statuses, $counts ) {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array( wp_generate_password( 4, false ) => array(
						'id' => '4',
						'label' => 'Email',
					),
					wp_generate_password( 16, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
				),
			),
			'settings' => array(
				'show_only_approved' => $show_only_approved,
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"is_approved","input":"checkbox"}]',
					),
				),
			),
		) );

		$view = \GV\View::from_post( $post );

		$did_unapproved_meta = false;

		foreach ( array( 'approved', 'disapproved', 'unapproved' ) as $status ) {
			foreach ( range( 1, $statuses[ $status ] ) as $_ ) {
				$entry = $this->factory->entry->create_and_get( array(
					'form_id' => $form['id'],
					'status' => 'active',
					'16' => wp_generate_password( 16, false ),
				) );

				if ( 'unapproved' === $status ) {
					if ( ! $did_unapproved_meta ) { // Test both unapproved meta, and empty approval value meta
						gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::UNAPPROVED );
						$did_unapproved_meta = true;
					}
					continue;
				}

				gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, 'approved' === $status ? \GravityView_Entry_Approval_Status::APPROVED : \GravityView_Entry_Approval_Status::DISAPPROVED );
			}
		}

		/** Show all. */
		foreach ( $counts as $count ) {
			$_GET = array(
				'filter_is_approved' => $count['filter']
			);
			$this->assertEquals( $count['count'], $view->get_entries()->count() );
		}

		$_GET = array();
	}


	public function get_test_approval_status_search() {
		return array(
			array(
				'show_only_approved' => false,
				'statuses'           => array(
					'unapproved'  => 2,
					'approved'    => 5,
					'disapproved' => 8,
				),
				'counts'      => array(
					array( 'count' => 15, 'filter' => array() ),
					array( 'count' => 2, 'filter' => array( \GravityView_Entry_Approval_Status::UNAPPROVED ) ),
					array( 'count' => 5, 'filter' => array( \GravityView_Entry_Approval_Status::APPROVED ) ),
					array( 'count' => 8, 'filter' => array( \GravityView_Entry_Approval_Status::DISAPPROVED ) ),
					array( 'count' => 0, 'filter' => array( -1 ) ),
				)
			),
			array(
				'show_only_approved' => true,
				'statuses'           => array(
					'unapproved'  => 2,
					'approved'    => 5,
					'disapproved' => 8,
				),
				'counts'      => array(
					array( 'count' => 5, 'filter' => array() ),
					array( 'count' => 0, 'filter' => array( \GravityView_Entry_Approval_Status::UNAPPROVED ) ),
					array( 'count' => 5, 'filter' => array( \GravityView_Entry_Approval_Status::APPROVED ) ),
					array( 'count' => 0, 'filter' => array( \GravityView_Entry_Approval_Status::DISAPPROVED ) ),
					array( 'count' => 0, 'filter' => array( -1 ) ),
				)
			),
		);
	}

	public function test_created_by_multi_search() {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$alpha = $this->factory->user->create( array(
			'user_login' => 'alpha',
			'user_email' => md5( microtime() ) . '@gravityview.tests',
		) );

		$this->assertTrue( is_int( $alpha ) && ! empty( $alpha ) );

		$beta = $this->factory->user->create( array(
			'user_login' => 'beta',
			'user_email' => md5( microtime() ) . '@gravityview.tests',
		) );

		$this->assertTrue( is_int( $beta ) && ! empty( $beta ) );

		$gamma = $this->factory->user->create( array(
			'user_login' => 'gamma',
			'user_email' => md5( microtime() ) . '@gravityview.tests',
		) );

		$this->assertTrue( is_int( $gamma ) && ! empty( $gamma ) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 16, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
				),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"created_by","input":"checkbox"}]',
					),
				),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $alpha,
			'status' => 'active',
			'16' => wp_generate_password( 16, false ),
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $beta,
			'status' => 'active',
			'16' => wp_generate_password( 16, false ),
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $gamma,
			'status' => 'active',
			'16' => wp_generate_password( 16, false ),
		) );

		$_GET = array();
		$this->assertEquals( 3, $view->get_entries()->count() );

		$_GET = array( 'gv_by' => $alpha );
		$this->assertEquals( 1, $view->get_entries()->count() );

		$_GET = array( 'gv_by' => array( $alpha, $beta ) );
		$this->assertEquals( 2, $view->get_entries()->count() );

		$_GET = array( 'gv_by' => -1 );
		$this->assertEquals( 0, $view->get_entries()->count() );

		$_GET = array();
	}

	public function test_created_by_text_search() {
		$alpha = $this->factory->user->create( array(
			'user_login' => 'alpha',
			'user_email' => md5( microtime() ) . '@gravityview.tests',
		) );

		$beta = $this->factory->user->create( array(
			'user_login' => 'beta',
			'user_email' => md5( microtime() ) . '@gravityview.tests',
		) );

		$gamma = $this->factory->user->create( array(
			'user_login' => 'gamma',
			'user_email' => md5( microtime() ) . '@gravityview.tests',
		) );

		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 16, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
				),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"created_by","input":"input_text"}]',
					),
				),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $alpha,
			'status' => 'active',
			'16' => wp_generate_password( 16, false ),
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $beta,
			'status' => 'active',
			'16' => wp_generate_password( 16, false ),
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'created_by' => $gamma,
			'status' => 'active',
			'16' => wp_generate_password( 16, false ),
		) );

		$_GET = array();
		$this->assertEquals( 3, $view->get_entries()->count() );

		$_GET = array( 'gv_by' => 'a' );
		$this->assertEquals( 3, $view->get_entries()->count() );

		$_GET = array( 'gv_by' => 'mm' );
		$this->assertEquals( 1, $view->get_entries()->count() );

		$_GET = array( 'gv_by' => 'beta' );
		$this->assertEquals( 1, $view->get_entries()->count() );

		$_GET = array( 'gv_by' => 'gravityview.tests' );
		$this->assertEquals( 3, $view->get_entries()->count() );

		$_GET = array( 'gv_by' => 'custom' );
		$this->assertEquals( 0, $view->get_entries()->count() );

		add_filter( 'gk/gravityview/view/entries/cache', '__return_false' );
		add_filter( 'gravityview_use_cache', '__return_false' );

		update_user_meta( $gamma, 'custom_meta', 'custom' );
		add_filter( 'gravityview/widgets/search/created_by/user_meta_fields', function() {
			return array( 'custom_meta' );
		} );
		$this->assertEquals( 1, $view->get_entries()->count() );

		remove_all_filters( 'gravityview/widgets/search/created_by/user_meta_fields' );
		remove_all_filters( 'gk/gravityview/view/entries/cache' );
		remove_all_filters( 'gravityview_use_cache' );

		$_GET = array();
	}

	/**
	 * https://gist.github.com/zackkatz/66e9fb2147a9eb1a2f2e
	 */
	public function test_override_search_operator() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 16, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
				),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"16","input":"input_text"}]',
					),
				),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'hello world',
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'hello',
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'world',
		) );

		$_GET = array();
		$this->assertEquals( 3, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'hello' );
		$this->assertEquals( 2, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'hello' );
		$this->assertEquals( 2, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'hello' );
		$this->assertEquals( 2, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'world' );
		$this->assertEquals( 2, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'world' );
		$this->assertEquals( 2, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'hello world, goodbye moon' );
		$this->assertEquals( 0, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'hello world, goodbye moon' );
		$this->assertEquals( 0, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'hello world' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'hello world' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		add_filter( 'gravityview_fe_search_criteria', $callback = function( $search_criteria ) {
			if ( ! isset( $search_criteria['field_filters'] ) ) {
				return $search_criteria;
			}

			foreach ( $search_criteria['field_filters'] as $k => $filter ) {
				if ( ! empty( $filter['key'] ) && '16' == $filter['key'] ) {
					$search_criteria['field_filters'][ $k ]['operator'] = 'is';
					break;
				}
			}

			return $search_criteria;
		} );

		$_GET = array();
		$this->assertEquals( 3, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'hello' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'world' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'hello world, goodbye moon' );
		$this->assertEquals( 0, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'hello world' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'hello' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'world' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'hello world, goodbye moon' );
		$this->assertEquals( 0, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'hello world' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		remove_filter( 'gravityview_fe_search_criteria', $callback );

		add_filter( 'gravityview_search_operator', $callback = function( $operator, $field ) {
			if ( '16' == $field['key'] ) {
				return 'is';
			}
			return $operator;
		}, 10, 2 );

		$_GET = array();
		$this->assertEquals( 3, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'hello' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'world' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'hello world, goodbye moon' );
		$this->assertEquals( 0, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'hello world' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'hello' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'world' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'hello world, goodbye moon' );
		$this->assertEquals( 0, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'hello world' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET = array( 'input_16' => 'hello world' );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		remove_filter( 'gravityview_search_operator', $callback );

		$_GET = array();
	}

	/**
	 * https://github.com/gravityview/GravityView/issues/1233
	 */
	public function test_search_date_created() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 16, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
				),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"entry_date","input":"date_range"}]',
					),
				),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'date_created' => '2019-01-03 12:00:00',
			'16' => 'hello world',
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'date_created' => '2019-01-04 12:00:00',
			'16' => 'hello',
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'date_created' => '2019-01-05 12:00:00',
			'16' => 'world',
		) );

		$_GET = array();
		$this->assertEquals( 3, $view->get_entries()->fetch()->count() );

		$_GET['gv_start'] = '01/01/2019';
		$_GET['gv_end']   = '01/01/2019';
		$this->assertEquals( 0, $view->get_entries()->fetch()->count() );

		$_GET['gv_start'] = '01/04/2019';
		$_GET['gv_end']   = '01/04/2019';
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET['gv_start'] = '01/06/2019';
		$_GET['gv_end']   = '01/06/2019';
		$this->assertEquals( 0, $view->get_entries()->fetch()->count() );

		$_GET = array();
	}

	public function test_payment_date_search() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 16, false ) => array(
						'id' => 'payment_date',
						'label' => 'Payment Date',
					),
				),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"payment_date","input":"date"}]',
					),
				),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'payment_date' => '2020-11-20 12:00:00',
		) );

		$_GET = array();

		$_GET['filter_payment_date'] = '12/20/2020';
		$this->assertEquals( 0, $view->get_entries()->fetch()->count() );

		$_GET['filter_payment_date'] = '11/20/2020';
		$this->assertEquals( 1, $view->get_entries()->fetch()->count() );

		$_GET = array();
	}

	public function test_operator_url_overrides() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 16, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
				),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"16","input":"input_text"}]',
					),
				),
			),
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		$hello_world = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'hello world',
		) );

		$hello = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'hello',
		) );

		$world = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'world',
		) );

		$_GET = array();
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 3, $entries );

		$_GET['filter_16'] = 'hello';
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 2, $entries );

		$_GET['input_16'] = 'hello';
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 2, $entries );

		$_GET['filter_16'] = 'hello';
		$_GET['filter_16|op'] = '!='; // Override doesn't work, as '!=' is not in allowlist
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 2, $entries );
		$this->assertEquals( $hello['id'], $entries[0]['id'] );
		$this->assertEquals( $hello_world['id'], $entries[1]['id'] );

		$_GET['input_16'] = 'hello';
		$_GET['input_16|op'] = '!='; // Override doesn't work, as '!=' is not in allowlist
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 2, $entries );
		$this->assertEquals( $hello['id'], $entries[0]['id'] );
		$this->assertEquals( $hello_world['id'], $entries[1]['id'] );

		add_filter( 'gravityview/search/operator_allowlist', $callback = function() {
			return array( '!=' );
		} );

		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 2, $entries );
		$this->assertEquals( $world['id'], $entries[0]['id'] );
		$this->assertEquals( $hello_world['id'], $entries[1]['id'] );

		remove_filter( 'gravityview/search/operator_allowlist', $callback );

		$_GET = array();
	}

	public function test_search_all_basic() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 16, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
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
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		$hello_world = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'hello world',
		) );

		$hello = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'hello',
		) );

		$world = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'world',
		) );

		$_GET = array();
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 3, $entries );

		$_GET['gv_search'] = 'hello';
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 2, $entries );

		$_GET['gv_search'] = 'hello world';
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 3, $entries );

		$_GET['gv_search'] = '"hello world"';
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 1, $entries );

		$_GET['gv_search'] = 'hello -world';
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 1, $entries );

		$_GET['gv_search'] = '+world';
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 2, $entries );
		$this->assertSame(
			[ 'world', 'hello world' ],
			array_map(
				static function ( $entry ) {
					return $entry['16'];
				},
				$entries
			)
		);

		$_GET['gv_search'] = '-hello +world';
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 1, $entries );
		$this->assertSame( 'world', $entries[0]['16'] );

		$_GET = array();
	}

	public function test_search_all_basic_choices() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 16, false ) => array(
						'id' => '16',
						'label' => 'Textarea',
					),
					wp_generate_password( 16, false ) => array(
						'id' => '2',
						'label' => 'Checkbox',
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
			'settings' => $settings,
		) );
		$view = \GV\View::from_post( $post );

		$hello_world = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'wazzup',
			'2.2' => 'Somewhat Better'
		) );

		$hello = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'16' => 'hello',
		) );

		$_GET['gv_search'] = 'better';
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 1, $entries );

		$_GET = array();
	}

	public function test_searchable_field_restrictions_filter() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$settings = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'fields' => array( '_' => array(
				array( 'id' => '1.1' ),
			) ),
			'widgets' => array( '_' => array(
				array(
					'id' => 'search_bar',
					'search_fields' => json_encode( array(
						array( 'field' => '1.1', 'input' => 'text' ),
					) ),
				),
			) ),
			'settings' => $settings,
		) );

		$view = \GV\View::from_post( $post );

		$_GET = array(
			'gv_start' => '2017-01-01',
			'gv_end' => '2017-12-31',
			'filter_1_1' => 'hello',
			'filter_16' => 'world',
		);

		$search_criteria = array(
			'field_filters' => array(
				'mode' => 'any',
				array(
					'key' => '1.1',
					'value' => 'hello',
					'form_id' => $view->form->ID,
					'operator' => 'contains'
				),
			),
		);

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		$_GET = array(
			'gv_start' => '2017-01-01',
			'gv_end' => '2017-12-31',
			'input_1_1' => 'hello',
			'input_16' => 'world',
		);

		$search_criteria = array(
			'field_filters' => array(
				'mode' => 'any',
				array(
					'key' => '1.1',
					'value' => 'hello',
					'form_id' => $view->form->ID,
					'operator' => 'contains'
				),
			),
		);

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		add_filter( $filter = 'gravityview/search/searchable_fields/allowlist', $callback = function( $fields, $view, $with_full ) {
			if ( $with_full ) {
				return array(
					array(
						'field' => '16',
						'form_id' => $view->form->ID,
						'input' => 'text',
					),
				);
			} else {
				return array( '16' );
			}
		}, 10, 3 );

		$search_criteria = array(
			'field_filters' => array(
				'mode' => 'any',
				array(
					'key' => '16',
					'value' => 'world',
					'form_id' => $view->form->ID,
					'operator' => 'contains'
				),
			),
		);

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true ) );

		remove_filter( $filter, $callback );
	}

	public function test_search_value_trimming() {

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id'     => $form['id'],
			'template_id' => 'table',
			'settings'    => array(
				'show_only_approved' => false,
			),
			'fields'      => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id'    => '16',
						'label' => 'Textarea',
					),
				),
			),
			'widgets'     => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id'            => 'search_bar',
						'search_fields' => '[{"field":"search_all","input":"input_text"},{"field":"16","input":"input_text"}]',
						'search_mode'   => 'any',
					),
				),
			),
		) );

		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status'  => 'active',
			'16'      => 'Text ',
		) );

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status'  => 'active',
			'16'      => 'Text',
		) );

		// Whitespaces are trimmed by default
		$_GET = array( 'filter_16' => 'Text ' );
		$this->assertEquals( 2, $view->get_entries()->count() );
		$_GET = array( 'input_16' => 'Text ' );
		$this->assertEquals( 2, $view->get_entries()->count() );
		$_GET = array( 'gv_search' => 'Text ' );
		$this->assertEquals( 2, $view->get_entries()->count() );

		// Retain whitespaces via a filter
		add_filter( 'gravityview/search-trim-input', '__return_false' );
		add_filter( 'gravityview/search-all-split-words', '__return_false' ); // This is to ensure that "Text " is not split to ["Text", ""]
		$_GET = array( 'filter_16' => 'Text ' );
		$this->assertEquals( 1, $view->get_entries()->count() );
		$_GET = array( 'input_16' => 'Text ' );
		$this->assertEquals( 1, $view->get_entries()->count() );
		$_GET = array( 'gv_search' => 'Text ' );
		$this->assertEquals( 1, $view->get_entries()->count() );
		remove_filter( 'gravityview/search-trim-input', '__return_false' );
		remove_filter( 'gravityview/search-all-split-words', '__return_false' );

		$_GET = array();
	}

	public function test_search_with_strict_empty_value_matching() {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id'     => $form['id'],
			'template_id' => 'table',
			'fields'      => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false )  => array(
						'id'    => '8.3',
						'label' => 'First',
					),
					wp_generate_password( 16, false ) => array(
						'id'    => '8.6',
						'label' => 'Last',
					),
				),
			),
			'settings'    => array(
				'show_only_approved' => false,
			),
			'widgets'     => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id'            => 'search_bar',
						'search_fields' => json_encode( array(
								array(
									'field' => '8.3',
								),
								array(
									'field' => '8.6',
								)
							)
						),
					),
				)
			),
		) );

		$view = \GV\View::from_post( $post );

		$data = array(
			array( 'Alice', 'Alice' ),
			array( 'Alice', 'Bob' ),
			array( 'Alice', 'Alice' ),
		);

		foreach ( $data as $name ) {
			$this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status'  => 'active',
				'8.3'     => $name[0],
				'8.6'     => $name[1],
			) );
		}

		// Default "contains" operator
		$_GET = array( 'filter_8_3' => 'Alice', 'filter_8_6' => '', 'mode' => 'all' );

		$this->assertEquals( 3, $view->get_entries()->count() );

		// Default "contains" operator
		$_GET = array( 'input_8_3' => 'Alice', 'input_8_6' => '', 'mode' => 'all' );

		$this->assertEquals( 3, $view->get_entries()->count() );

		// "is" operator
		add_filter( 'gravityview_search_operator', function () {
			return 'is';
		} );

		// do not ignore empty values
		add_filter( 'gravityview/search/ignore-empty-values', '__return_false');

		$this->assertEquals( 0, $view->get_entries()->count() );

		$_GET = array( 'filter_8_3' => 'Alice', 'filter_8_6' => 'Alice', 'mode' => 'all' );

		$this->assertEquals( 2, $view->get_entries()->count() );

		$_GET = array( 'input_8_3' => 'Alice', 'input_8_6' => 'Alice', 'mode' => 'all' );

		$this->assertEquals( 2, $view->get_entries()->count() );

		remove_all_filters('gravityview_search_operator');

		$_GET = array();
	}

	public function test_search_with_number_field() {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id'     => $form['id'],
			'template_id' => 'table',
			'fields'      => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false )  => array(
						'id'    => '9',
						'label' => 'Number',
					),
				),
			),
			'settings'    => array(
				'show_only_approved' => false,
			),
			'widgets'     => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id'            => 'search_bar',
						'search_fields' => json_encode( array(
								array( 'field' => '9' ),
								array( 'field' => '26' ),
							)
						),
					),
				)
			),
		) );

		$view = \GV\View::from_post( $post );
		$currency_symbol = rgar( RGCurrency::get_currency( GFCommon::get_currency() ), 'symbol_left' );

		foreach ( array( 1, 5, 7, 10, '-20.23' ) as $number ) {
			$this->factory->entry->create_and_get( array(
				'form_id' => $form['id'],
				'status'  => 'active',
				'9'       => $number,
				'26'      => 'product name ' . $number . '|' . $currency_symbol . $number,
			) );
		}

		// "is" operator
		add_filter( 'gravityview_search_operator', function () {
			return 'is';
		} );

		// do not ignore empty values
		add_filter( 'gravityview/search/ignore-empty-values', '__return_false');

		$_GET = array( 'filter_9' => '5', 'mode' => 'all' );

		$this->assertEquals( 1, $view->get_entries()->count() );

		$_GET = array( 'filter_9' => '', 'mode' => 'all' );

		$this->assertEquals( 0, $view->get_entries()->count() );

		$_GET = array( 'input_9' => '5', 'mode' => 'all' );

		$this->assertEquals( 1, $view->get_entries()->count() );

		$_GET = array( 'input_9' => '', 'mode' => 'all' );

		$this->assertEquals( 0, $view->get_entries()->count() );

		remove_all_filters('gravityview_search_operator');

		// Number field
		$_GET = [ 'filter_9' => [ 'min' => -21, 'max' => 9 ], 'mode' => 'all' ];
		$this->assertEquals( 4, $view->get_entries()->count() );

		$_GET = [ 'filter_9' => [ 'min' => -20, 'max' => 9 ], 'mode' => 'all' ];
		$this->assertEquals( 3, $view->get_entries()->count() );

		$entries = $view->get_entries()->all();
		$this->assertSame( [ '7', '5' ], [ $entries[0]->as_entry()[9], $entries[1]->as_entry()[9] ] );

		// Product field.
		$_GET = [ 'filter_26' => [ 'min' => -21, 'max' => 6.50 ], 'mode' => 'all' ];
		$this->assertEquals( 3, $view->get_entries()->count() );

		$_GET = [ 'filter_26' => [ 'min' => -20, 'max' => 7 ], 'mode' => 'all' ];
		$this->assertEquals( 3, $view->get_entries()->count() );

		// Make sure searching on text still works.
		$_GET = ['filter_26' => 'product'];
		$this->assertEquals( 5, $view->get_entries()->count() );

		$_GET = ['filter_26' => 'name 7'];
		$this->assertEquals( 1, $view->get_entries()->count() );


		$_GET = array();
	}

	public function test_search_everything_with_json_storage_fields() {
		$form = $this->factory->form->import_and_get( 'complete.json' );

		$post = $this->factory->view->create_and_get( [
			'form_id'     => $form['id'],
			'template_id' => 'table',
			'fields'      => [
				'directory_table-columns' => [
					wp_generate_password( 16, false ) => [
						'id'    => '16',
						'label' => 'Textarea',
					],
				],
			],
			'widgets'     => [
				'header_top' => [
					wp_generate_password( 4, false ) => [
						'id'            => 'search_bar',
						'search_fields' => '[{"field":"search_all","input":"input_text"}]',
					],
				],
			],
			'settings'    => array_merge( \GV\View_Settings::defaults(), [
				'show_only_approved' => 0,
			] ),
		] );

		$view = \GV\View::from_post( $post );

		$first_choice = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status'  => 'active',
			'35'      => 'First Choice',
		] );

		$fourth_choice = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status'  => 'active',
			'35'      => json_encode( [
				'First Choice',
				'Quatrième Choix',
			] ),
		] );

		$fifth_choice = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status'  => 'active',
			'35'      => 'Fünfte Wahl',
		] );

		$_GET    = [];
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 3, $entries );

		$_GET['gv_search'] = 'first';
		$entries           = $view->get_entries()->fetch()->all();
		$this->assertCount( 2, $entries );

		$_GET['gv_search'] = 'quatrième';
		$entries           = $view->get_entries()->fetch()->all();
		$this->assertCount( 1, $entries );

		$_GET['gv_search'] = 'fünfte';
		$entries           = $view->get_entries()->fetch()->all();
		$this->assertCount( 1, $entries );
	}

	/**
	 * Test case that ensures hidden fields are not searchable through "Search Everything".
	 *
	 * @since 2.42
	 */
	public function test_search_everything_limited_to_visible_fields(): void {
		$form = $this->factory->form->import_and_get( 'standard.json' );

		$post = $this->factory->view->create_and_get( [
			'form_id'     => $form['id'],
			'template_id' => 'table',
			'fields'      => [
				'directory_table-columns' => [
					wp_generate_password( 16, false ) => [
						'id'                => '5',
						'label'             => 'Text field',
						'only_loggedin'     => '1',
						'only_loggedin_cap' => 'read',
					],
				],
				'single_table-columns'    => [
					wp_generate_password( 16, false ) => [
						'id'    => '7',
						'label' => 'Select field',
					],
				],
			],
			'widgets'     => [
				'header_top' => [
					wp_generate_password( 4, false ) => [
						'id'            => 'search_bar',
						'search_fields' => '[{"field":"search_all","input":"input_text"}]',
					],
				],
			],
			'settings'    => array_merge( \GV\View_Settings::defaults(), [
				'show_only_approved'    => 0,
				'search_visible_fields' => 1,
			] ),
		] );

		$view = \GV\View::from_post( $post );

		$this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status'  => 'active',
			'5'       => 'Visible value',
			'6'       => 'Hidden value',
			'7'       => 'Second Choice',
		] );

		$_GET    = [];
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 1, $entries );

		$_GET['gv_search'] = 'hidden';
		$entries           = $view->get_entries()->fetch()->all();
		$this->assertCount( 0, $entries );

		$_GET['gv_search'] = 'Visible';
		$entries           = $view->get_entries()->fetch()->all();
		$this->assertCount( 0, $entries ); // User is not logged in.

		$this->factory->user->create_and_set( [
			'user_login' => 'test_user',
			'user_email' => md5( microtime() ) . '@gravityview.tests',
			'role'       => 'editor',
		] );

		// Clear cache to retrieve the new visible fields for this user.
		GravityView_Search_Widget_Settings_Visible_Fields_Only::clear_cache();

		$_GET['gv_search'] = 'hidden';
		$entries           = $view->get_entries()->fetch()->all();
		$this->assertCount( 0, $entries );

		$_GET['gv_search'] = 'Visible';
		$entries           = $view->get_entries()->fetch()->all();
		$this->assertCount( 1, $entries ); // User is logged in.

		add_filter( 'gk/gravityview/widget/search/visible_fields_only', '__return_false' );

		$_GET['gv_search'] = 'hidden';
		$entries           = $view->get_entries()->fetch()->all();
		$this->assertCount( 1, $entries ); // Filter has disabled the search for visible fields only.

		// Clean up.
		remove_filter( 'gk/gravityview/widget/search/visible_fields_only', '__return_false' );
		unset( $_GET['gv_search'] );
	}

	/**
	 * Test that Date Range filter with single date (associative array) doesn't cause PHP warning.
	 * Regression test for fix in commit ac90184c1.
	 *
	 * @covers GravityView_Widget_Search::filter_entries()
	 * @group GravityView_Widget_Search
	 * @since 2.43.3
	 */
	public function test_date_range_filter_with_associative_array() {
		// Test that filter_entries() handles associative arrays properly
		// This addresses the fix where reset($value) is used instead of $value[0]
		// to avoid "Undefined array key 0" warning with associative arrays

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '3',
						'label' => 'Date',
					),
				),
			),
			'widgets' => array(
				'header_top' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'search_bar',
						'search_fields' => '[{"field":"3","input":"date_range"}]',
					),
				),
			),
		) );

		// Create test entries
		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'3' => '2025-08-01',
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'3' => '2025-08-05',
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',
			'3' => '2025-08-10',
		) );

		// Test with associative array containing only 'start' key
		// This simulates the DataTables date range filter with only start date
		$_GET = array(
			'filter_3' => array( 'start' => '2025-08-01' )
		);

		add_filter( 'gravityview/widgets/search/datepicker/format', function() { return 'ymd_dash'; } );

		// Before the fix, this would generate a PHP warning:
		// "Undefined array key 0 in class-search-widget.php on line 836"
		// After the fix, it should work without warnings and properly filter entries
		$search_criteria = $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true );

		// Verify the search criteria was built correctly
		$this->assertArrayHasKey( 'field_filters', $search_criteria );
		$this->assertIsArray( $search_criteria['field_filters'] );

		// Find the date filter in the field_filters array
		$date_filter_found = false;
		foreach ( $search_criteria['field_filters'] as $filter ) {
			if ( is_array( $filter ) && isset( $filter['key'] ) && $filter['key'] === '3' ) {
				$date_filter_found = true;
				// Verify the filter was created with the start date
				$this->assertEquals( '>=', $filter['operator'] );
				$this->assertEquals( '2025-08-01', $filter['value'] );
				break;
			}
		}
		$this->assertTrue( $date_filter_found, 'Date filter should be created from associative array with start key' );

		// Test with associative array containing only 'end' key
		$_GET = array(
			'filter_3' => array( 'end' => '2025-08-10' )
		);

		$search_criteria = $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true );

		// Find the date filter in the field_filters array
		$date_filter_found = false;
		foreach ( $search_criteria['field_filters'] as $filter ) {
			if ( is_array( $filter ) && isset( $filter['key'] ) && $filter['key'] === '3' ) {
				$date_filter_found = true;
				// Verify the filter was created with the end date
				$this->assertEquals( '<=', $filter['operator'] );
				$this->assertEquals( '2025-08-10', $filter['value'] );
				break;
			}
		}
		$this->assertTrue( $date_filter_found, 'Date filter should be created from associative array with end key' );

		// Test with both start and end dates (full date range)
		$_GET = array(
			'filter_3' => array( 'start' => '2025-08-01', 'end' => '2025-08-10' )
		);

		$search_criteria = $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ), true );

		// Count the date filters - should have two (one for start, one for end)
		$date_filter_count = 0;
		$has_start_filter = false;
		$has_end_filter = false;
		foreach ( $search_criteria['field_filters'] as $filter ) {
			if ( is_array( $filter ) && isset( $filter['key'] ) && $filter['key'] === '3' ) {
				$date_filter_count++;
				if ( $filter['operator'] === '>=' && $filter['value'] === '2025-08-01' ) {
					$has_start_filter = true;
				}
				if ( $filter['operator'] === '<=' && $filter['value'] === '2025-08-10' ) {
					$has_end_filter = true;
				}
			}
		}
		$this->assertEquals( 2, $date_filter_count, 'Should have two date filters for full date range' );
		$this->assertTrue( $has_start_filter, 'Should have start date filter' );
		$this->assertTrue( $has_end_filter, 'Should have end date filter' );

		// Clean up
		remove_all_filters( 'gravityview/widgets/search/datepicker/format' );
		$_GET = array();
	}

	/**
	 * Ensuring searching on a repeater field, searches inside its nested fields, recursively.
	 * @since $ver$
	 */
	public function test_searching_repeater_fields(): void {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$form = $this->factory->form->import_and_get( 'repeater-nested.json' );

		$view = $this->factory->view->create_and_get( [
			'form_id'     => $form['id'],
			'template_id' => 'table',
			'fields'      => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id'    => '1',
						'label' => 'Text Field',
					],
					wp_generate_password( 4, false ) => [
						'id'    => '2',
						'label' => 'Contact Repeater',
					],
				],
			],
			'widgets'     => [
				'header_top' => [
					wp_generate_password( 4, false ) => [
						'id'            => 'search_bar',
						'search_fields' => '[{"field":"2","input":"input_text"}]',
					],
				],
			],
			'settings'    => [
				'show_only_approved' => 0,
			],
		] );

		$view = \GV\View::from_post( $view );

		// Entry 1: John with phone numbers 1234567890 (mobile) and 9876543210 (work).
		$this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status'  => 'active',
			'1'       => 'Entry One',
			'2'       => [
				[
					'3' => 'John Doe',
					'4' => 'john@example.com',
					'5' => [
						[
							'6' => '1234567890',
							'7' => 'mobile',
						],
					],
				],
			],
			'8' => 100,
		] );

		// Entry 2: Jane with phone number 5555555555 (home).
		$this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status'  => 'active',
			'1'       => 'Entry Two',
			'2'       => [
				[
					'3' => 'Jane Smith',
					'4' => 'jane@example.org',
					'5' => [
						[
							'6' => '5555555555',
							'7' => 'home',
						],
					],
				],
			],
			'8'       => 200,
		] );

		// Entry 3: Bob with office phone and fax.
		$this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status'  => 'active',
			'1'       => 'Entry Three',
			'2'       => [
				[
					'3' => 'Bob Wilson',
					'4' => 'bob@test.com',
					'5' => [
						[
							'6' => '1112223333',
							'7' => 'fax',
						],
					],
				],
			],
			'8'       => 300,
		] );


		// Without any filter, should return all 3 entries.
		$_GET = [];
		$this->assertEquals( 3, $view->get_entries()->fetch()->count(), 'Should return all 3 entries when no filter is applied' );

		// Search for "John" - should find Entry 1 (nested field 3: Name).
		$_GET = [ 'filter_2' => 'John' ];
		$this->assertEquals( 1, $view->get_entries()->fetch()->count(), 'Should find 1 entry when searching for "John" in repeater' );

		// Search for "example.com" - should find Entry 1 (nested field 4: Email).
		$_GET = [ 'filter_2' => 'example.com' ];
		$this->assertEquals( 1, $view->get_entries()->fetch()->count(), 'Should find 1 entry when searching for "example.com" in repeater' );

		// Search for "example" - should find Entry 1 and Entry 2 (both have example in email).
		$_GET = [ 'filter_2' => 'example' ];
		$this->assertEquals( 2, $view->get_entries()->fetch()->count(), 'Should find 2 entries when searching for "example" in repeater' );

		// Search for "5555555555" - should find Entry 2 (nested nested field 6: Number).
		$_GET = [ 'filter_2' => '5555555555' ];
		$this->assertEquals( 1, $view->get_entries()->fetch()->count(), 'Should find 1 entry when searching for phone number in nested repeater' );

		// Search for "fax" - should find Entry 3 only (deeply nested field 7: Type).
		$_GET = [ 'filter_2' => 'fax' ];
		$this->assertEquals( 1, $view->get_entries()->fetch()->count(), 'Should find 1 entry when searching for "fax" in deeply nested repeater' );

		// Search for "nonexistent" - should find no entries.
		$_GET = [ 'filter_2' => 'nonexistent' ];
		$this->assertEquals( 0, $view->get_entries()->fetch()->count(), 'Should find 0 entries when searching for nonexistent value' );

		// Cleanup.
		$_GET = [];
	}

	/**
	 * Tests filtering of Views embedded in Single Entry layout with actual rendering.
	 *
	 * @covers GravityView_Widget_Search::gf_query_filter()
	 * @covers GravityView_Widget_Search::prepare_field_filter()
	 *
	 * @since 2.46.2
	 */
	public function test_embedded_view_filtering_in_single_entry() {
		$inner_form = $this->factory->form->create_and_get( array(
			'fields' => array(
				array(
					'id' => 1,
					'type' => 'text',
					'label' => 'Name',
				),
				array(
					'id' => 2,
					'type' => 'text',
					'label' => 'Category',
				),
			),
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $inner_form['id'],
			'status' => 'active',
			'1' => 'Product A',
			'2' => 'Electronics',
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $inner_form['id'],
			'status' => 'active',
			'1' => 'Product B',
			'2' => 'Electronics',
		) );

		$this->factory->entry->create_and_get( array(
			'form_id' => $inner_form['id'],
			'status' => 'active',
			'1' => 'Product C',
			'2' => 'Furniture',
		) );

		$inner_view_post = $this->factory->view->create_and_get( array(
			'form_id' => $inner_form['id'],
			'template_id' => 'table',
			'fields' => array(
				'directory_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => '1',
						'label' => 'Name',
					),
					wp_generate_password( 4, false ) => array(
						'id' => '2',
						'label' => 'Category',
					),
				),
			),
			'settings' => array(
				'show_only_approved' => 0,
			),
		) );

		$outer_form = $this->factory->form->create_and_get();
		$outer_entry = $this->factory->entry->create_and_get( array(
			'form_id' => $outer_form['id'],
			'status' => 'active',
		) );

		$outer_view_post = $this->factory->view->create_and_get( array(
			'form_id' => $outer_form['id'],
			'template_id' => 'table',
			'fields' => array(
				'single_table-columns' => array(
					wp_generate_password( 4, false ) => array(
						'id' => 'id',
						'label' => 'Entry ID',
					),
					wp_generate_password( 4, false ) => array(
						'id' => 'custom',
						'content' => sprintf(
							'<div class="embedded-view-wrapper">[gravityview id="%d"]</div>',
							$inner_view_post->ID
						),
					),
				),
			),
		) );

		$request = new \GV\Mock_Request();

		$outer_view                   = \GV\View::from_post( $outer_view_post );
		$request->returns['is_view']  = $outer_view;
		$request->returns['is_entry'] = \GV\GF_Entry::by_id( $outer_entry['id'] );

		gravityview()->request = $request;

		// Test 1: without filter, all entries should appear in embedded View.
		$_GET = array();
		\GV\View::reset_rendering_stack();

		// Verify entries exist before rendering.
		$inner_entries = GFAPI::get_entries( $inner_form['id'] );
		$this->assertNotEmpty( $inner_entries, 'Inner form should have entries' );
		$this->assertCount( 3, $inner_entries, 'Should have exactly 3 entries' );

		$renderer = new \GV\Entry_Renderer();
		$output = $renderer->render( $request->returns['is_entry'], $outer_view );

		// Debug: check what's in the output.
		if ( strpos( $output, 'No entries match' ) !== false ) {
			$this->fail( 'Embedded view shows "No entries match" when no filters applied.' );
		}

		// Verify single entry renders.
		$this->assertStringContainsString( 'Entry ID', $output, 'Single entry field label should render' );
		$this->assertStringContainsString( (string) $outer_entry['id'], $output, 'Single entry ID should render' );

		// Verify embedded view renders with all entries
		$this->assertStringContainsString( 'embedded-view-wrapper', $output, 'Embedded view wrapper should render' );
		$this->assertStringContainsString( 'Product A', $output, 'First product should be visible' );
		$this->assertStringContainsString( 'Product B', $output, 'Second product should be visible' );
		$this->assertStringContainsString( 'Product C', $output, 'Third product should be visible' );
		$this->assertStringContainsString( 'Electronics', $output, 'Electronics category should be visible' );
		$this->assertStringContainsString( 'Furniture', $output, 'Furniture category should be visible' );

		// Test 2: with filter on category field, only matching entries should show.
		$_GET = array( 'filter_2' => 'Electronics' );
		\GV\View::reset_rendering_stack();

		$output = $renderer->render( $request->returns['is_entry'], $outer_view );

		// Verify single entry still renders (not affected by filter)
		$this->assertStringContainsString( 'Entry ID', $output, 'Single entry should still render with filter' );
		$this->assertStringContainsString( (string) $outer_entry['id'], $output, 'Single entry ID should still render with filter' );

		// Verify embedded view is filtered
		$this->assertStringContainsString( 'Product A', $output, 'Electronics Product A should be visible' );
		$this->assertStringContainsString( 'Product B', $output, 'Electronics Product B should be visible' );
		$this->assertStringNotContainsString( 'Product C', $output, 'Furniture Product C should be filtered out' );
		$this->assertStringNotContainsString( 'Furniture', $output, 'Furniture category should be filtered out' );

		// Test 3: with filter that matches no entries.
		$_GET = array( 'filter_2' => 'NonExistentCategory' );
		\GV\View::reset_rendering_stack();

		$output = $renderer->render( $request->returns['is_entry'], $outer_view );

		// Verify single entry still renders
		$this->assertStringContainsString( 'Entry ID', $output, 'Single entry should render even with non-matching filter' );
		$this->assertStringContainsString( (string) $outer_entry['id'], $output, 'Single entry ID should render even with non-matching filter' );

		// Verify embedded view shows no entries
		$this->assertStringContainsString( 'embedded-view-wrapper', $output, 'Embedded view wrapper should still render' );
		$this->assertStringNotContainsString( 'Product A', $output, 'No products should match' );
		$this->assertStringNotContainsString( 'Product B', $output, 'No products should match' );
		$this->assertStringNotContainsString( 'Product C', $output, 'No products should match' );

		// The embedded View should show "No entries" message.
		$has_no_results = strpos( $output, 'No entries match' ) !== false ||
		                   strpos( $output, 'no-results' ) !== false;
		$this->assertTrue( $has_no_results, 'Embedded view should show no results message when filter matches nothing' );

		// Test 4: Verify main single entry is protected from filters that would affect it.
		$_GET = array( 'filter_id' => '999999' ); // Filter that would exclude the main entry if applied.
		\GV\View::reset_rendering_stack();

		$output = $renderer->render( $request->returns['is_entry'], $outer_view );

		// The single entry should STILL render despite filter_id not matching.
		$this->assertStringContainsString( 'Entry ID', $output, 'Single entry should be protected from filter_id' );
		$this->assertStringContainsString( (string) $outer_entry['id'], $output, 'Single entry should render regardless of filter_id value' );

		$_GET = array();
		\GV\View::reset_rendering_stack();

		gravityview()->request = new \GV\Frontend_Request();
	}

	/**
	 * Test Entry Date search behavior based on URL parameters:
	 * - If only gv_start is present (no gv_end parameter), return entries from only that specific date.
	 * - If both gv_start and gv_end are present (even if one is empty), it's a range search.
	 *
	 * @since 2.48.1
	 */
	public function test_single_date_search() {
		$form                           = $this->factory->form->import_and_get( 'complete.json' );

		$settings                       = \GV\View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		$post                           = $this->factory->view->create_and_get( [
			'form_id'     => $form['id'],
			'template_id' => 'table',
			'fields'      => [
				'directory_table-columns' => [
					wp_generate_password( 16, false ) => [
						'id'    => '16',
						'label' => 'Textarea',
					],
				],
			],
			'widgets'     => [
				'header_top' => [
					wp_generate_password( 4, false ) => [
						'id'            => 'search_bar',
						'search_fields' => '[{"field":"entry_date","input":"date_range"}]',
					],
				],
			],
			'settings'    => $settings,
		] );

		$view                           = \GV\View::from_post( $post );

		// Create entries on different dates.
		$this->factory->entry->create_and_get( [
			'form_id'      => $form['id'],
			'status'       => 'active',
			'date_created' => '2025-09-20 10:00:00',
			'16'           => 'entry before target date',
		] );

		$this->factory->entry->create_and_get( [
			'form_id'      => $form['id'],
			'status'       => 'active',
			'date_created' => '2025-09-21 08:30:00',
			'16'           => 'entry on target date - morning',
		] );

		$this->factory->entry->create_and_get( [
			'form_id'      => $form['id'],
			'status'       => 'active',
			'date_created' => '2025-09-21 15:45:00',
			'16'           => 'entry on target date - afternoon',
		] );

		$this->factory->entry->create_and_get( [
			'form_id'      => $form['id'],
			'status'       => 'active',
			'date_created' => '2025-09-22 09:00:00',
			'16'           => 'entry after target date',
		] );

		// Without any date filter, should return all 4 entries.
		$_GET = [];
		$this->assertEquals( 4, $view->get_entries()->fetch()->count(), 'Should return all 4 entries when no date filter is applied' );

		// Single date parameter: only gv_start, no gv_end parameter at all.
		// Should return ONLY entries from that specific date (2 entries: 09/21 morning, 09/21 afternoon).
		$_GET = [ 'gv_start' => '09/21/2025' ];
		unset( $_GET['gv_end'] );
		$this->assertEquals( 2, $view->get_entries()->fetch()->count(), 'Single date parameter (gv_start only) should return entries from only that specific date' );

		// Date range parameters: gv_start with empty gv_end.
		// Should return entries from that date onwards (3 entries: 09/21 morning, 09/21 afternoon, 09/22).
		$_GET = [ 'gv_start' => '09/21/2025', 'gv_end' => '' ];
		$this->assertEquals( 3, $view->get_entries()->fetch()->count(), 'Date range with only start filled should return entries from that date onwards' );

		// Single date parameter for a date with no entries.
		$_GET = [ 'gv_start' => '09/23/2025' ];
		unset( $_GET['gv_end'] );
		$this->assertEquals( 0, $view->get_entries()->fetch()->count(), 'Single date parameter for a date with no entries should return 0' );

		// Single date parameter for 09/20/2025.
		// Should return only 1 entry (the one from 09/20).
		$_GET = [ 'gv_start' => '09/20/2025' ];
		unset( $_GET['gv_end'] );
		$this->assertEquals( 1, $view->get_entries()->fetch()->count(), 'Single date parameter for 09/20/2025 should return 1 entry' );

		// Date range with only end filled: empty gv_start with gv_end.
		// Should return entries up to and including that date (3 entries: 09/20, 09/21 morning, 09/21 afternoon).
		$_GET = [ 'gv_start' => '', 'gv_end' => '09/21/2025' ];
		$this->assertEquals( 3, $view->get_entries()->fetch()->count(), 'Date range with only end filled should return entries up to and including that date' );

		$_GET = [];
	}
}
