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
		$this->assertEquals( $search_criteria_single, $this->widget->filter_entries( array() ) );
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
		$this->assertEquals( $search_criteria_split, $this->widget->filter_entries( array() ) );

		$_GET = array(
			'gv_search' => '%20with%20%20spaces'
		);
		$this->assertEquals( $search_criteria_split, $this->widget->filter_entries( array() ) );

		$_GET = array(
			'gv_search' => '%20with%20%20spaces'
		);

		$search_criteria_split_mode = $search_criteria_split;
		$search_criteria_split_mode['field_filters']['mode'] = 'all';

		add_filter( 'gravityview/search/mode', function() { return 'all'; } );
		$this->assertEquals( $search_criteria_split_mode, $this->widget->filter_entries( array() ) );
		remove_all_filters( 'gravityview/search/mode' );

		$_GET = array(
			'gv_search' => 'with%20%20spaces',
			'mode' => 'all',
		);
		$this->assertEquals( $search_criteria_split_mode, $this->widget->filter_entries( array() ) );


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

		$this->assertEquals( $search_criteria_with_more_params, $this->widget->filter_entries( array() ) );


		$start = '03-28-1997';
		$end = '10-03-2017'; // Test mm-dd consistency

		// Test dates
		$_GET = array(
			'gv_start' => $start,
		    'gv_end' => $end,
		);

		$search_criteria_dates = array(
			'start_date' => get_gmt_from_date( $start ),
			'end_date' => get_gmt_from_date( $end ),
			'field_filters' => array(
				'mode' => 'any',
			),
		);
		$this->assertEquals( $search_criteria_dates, $this->widget->filter_entries( array() ) );
	}

}
