<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group frontend
 */
class GravityView_frontend_Test extends GV_UnitTestCase {


	/**
	 * @covers GravityView_frontend::process_search_dates()
	 */
	public function test_process_search_dates() {

		$date_range_2014 = array(
			'start_date' => '2014-01-01',
			'end_date' => '2014-12-31',
		);

		$date_range_june_2015 = array(
			'start_date' => '2015-06-01',
			'end_date' => '2015-06-30',
		);

		$date_range_2015 = array(
			'start_date' => '2015-01-01',
			'end_date' => '2015-12-31',
		);


		$search_dates = GravityView_frontend::process_search_dates( array(), $date_range_2015 );
		$this->assertEquals( $date_range_2015, $search_dates, 'No View settings to override; use the passed array' );


		$search_dates = GravityView_frontend::process_search_dates( $date_range_2014, $date_range_2015 );
		$this->assertEquals( array(
			'start_date' => $date_range_2015['start_date'],
			'end_date' => $date_range_2014['end_date'],
		), $search_dates, 'The start date is after the end date, which logs a GravityView error but doesn\'t throw any exceptions. This is expected behavior.' );


		$search_dates = GravityView_frontend::process_search_dates( $date_range_2015, $date_range_june_2015 );
		$this->assertEquals( $date_range_june_2015, $search_dates, 'The 2015 June passed values are all inside 2015 View settings. Use the passed values.' );

		$now = time();

		$yesterday = date( 'Y-m-d H:i:s', strtotime( 'yesterday', $now ) );
		$three_days_ago_ymd = date( 'Y-m-d', strtotime( '3 days ago', $now ) );
		$one_month_ago = date( 'Y-m-d H:i:s', strtotime( '-1 month', $now ) );

		$relative_dates = array(
			'start_date' => date( 'Y-m-d H:i:s', strtotime( '-1 month', $now ) ),
			'end_date' => date( 'Y-m-d H:i:s', strtotime( 'yesterday', $now ) )
		);

		$search_dates = GravityView_frontend::process_search_dates( $relative_dates );
		$this->assertEquals( array( 'start_date' => $one_month_ago, 'end_date' => $yesterday ), $search_dates, 'Make sure the relative dates are formatted in Y-m-d H:i:s format' );

		$search_dates = GravityView_frontend::process_search_dates( $relative_dates, array( 'end_date' => $three_days_ago_ymd ) );
		$this->assertEquals( array( 'start_date' => $one_month_ago, 'end_date' => $three_days_ago_ymd ), $search_dates, 'end_date overridden' );

	}

	/**
	 * @covers GravityView_frontend::get_search_criteria()
	 */
	public function test_get_search_criteria() {

		/** Just an empty test. */
		$this->assertEquals( array(
			'field_filters' => array(), 'status' => 'active'
		), GravityView_frontend::get_search_criteria( array(), 1 ) );

		/** Make sure searching is locked if implicit search_value is given. */
		$criteria = GravityView_frontend::get_search_criteria( array( 'search_value' => 'hello', 'search_field' => '1' ), 1 );

		$this->assertEquals( 'all', $criteria['field_filters']['mode'] );
	}

}
