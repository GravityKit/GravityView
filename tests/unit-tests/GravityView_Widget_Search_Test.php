<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group widgets
 * @since 1.21
 */
class GravityView_Widget_Search_Test extends GV_UnitTestCase {

	/** @var GravityView_Widget_Search */
	public $widget;

	function setUp() {
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

		$this->assertEquals( array('original value'), $this->widget->filter_entries( array('original value') ), 'when $_GET is empty, $search_criteria should be returned' );

		$view = $this->factory->view->create_and_get( array(
			'widgets' => array( '_' => array(
				array(
					'id' => 'search_bar',
					'search_fields' => json_encode( array(
						array( 'field' => 'search_all' ),
						array( 'field' => 'entry_id' ),
						array( 'field' => 'entry_date' ),
						array( 'field' => 'entry_creator' ),
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
		$this->assertEquals( $search_criteria_single, $this->widget->filter_entries( array(), null, $args ) );
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
		$this->assertEquals( $search_criteria_split, $this->widget->filter_entries( array(), null, $args ) );

		$_GET = array(
			'gv_search' => '%20with%20%20spaces'
		);
		$this->assertEquals( $search_criteria_split, $this->widget->filter_entries( array(), null, $args ) );

		$_GET = array(
			'gv_search' => '%20with%20%20spaces'
		);

		$search_criteria_split_mode = $search_criteria_split;
		$search_criteria_split_mode['field_filters']['mode'] = 'all';

		add_filter( 'gravityview/search/mode', function() { return 'all'; } );
		$this->assertEquals( $search_criteria_split_mode, $this->widget->filter_entries( array(), null, $args ) );
		remove_all_filters( 'gravityview/search/mode' );

		$_GET = array(
			'gv_search' => 'with%20%20spaces',
			'mode' => 'all',
		);
		$this->assertEquals( $search_criteria_split_mode, $this->widget->filter_entries( array(), null, $args ) );


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

		$this->assertEquals( $search_criteria_with_more_params, $this->widget->filter_entries( array(), null, $args ) );


		$start = '1997-03-28';
		$end = '2017-10-03';

		// Test dates
		$_GET = array(
			'gv_start' => $start,
			'gv_end' => $end,
		);

		$search_criteria_dates = array(
			'start_date' => get_gmt_from_date( $start ),
			'end_date' => get_gmt_from_date( '2017-10-04' /* + 1 day */ ),
			'field_filters' => array(
				'mode' => 'any',
			),
		);
		$this->assertEquals( $search_criteria_dates, $this->widget->filter_entries( array(), null, $args ) );

		$_GET = array();
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

		$_GET = array( 'gv_search' => '_' );

		$search_criteria = array(
			'field_filters' => array(
				'mode' => 'any',
			),
		);

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ) ) );

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

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ) ) );

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
			'end_date' => '2018-01-01 00:00:00',
		);

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ) ) );

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

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ) ) );

		/**
		 * gv_by query parameter.
		 *
		 * SHOULD NOT search unless "entry_creator" is set in Search Widget "Search Field" settings.
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

		$_GET = array( 'gv_by' => '_', 'gv_id' => '3' );

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

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ) ) );

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

		$this->assertEquals( $search_criteria, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ) ) );

		$_GET = array();
	}

	public function test_filter_entries_gv_start_end_time() {
		$_GET = array(
			'gv_start' => '2018-04-07',
			'gv_end' => '2018-04-07',
		);

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
			'end_date' => '2018-04-08 00:00:00',
			'field_filters' => array(
				'mode' => 'any',
			),
		);
		$this->assertEquals( $search_criteria_dates, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ) ) );

		remove_filter( 'pre_option_timezone_string', $callback );

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
		$this->assertEquals( $search_criteria_dates, $this->widget->filter_entries( array(), null, array( 'id' => $view->ID ) ) );

		remove_filter( 'pre_option_timezone_string', $callback );
	}
}
