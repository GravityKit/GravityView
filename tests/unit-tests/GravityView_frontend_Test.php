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
	 * @covers GravityView_frontend::process_search_dates()
	 */
	public function test_process_search_dates_with_timezone_offset() {
		# Test relative dates using WP timezone offset
		if ( ! function_exists( 'runkit7_function_copy' ) || !function_exists( 'runkit7_function_redefine' ) ) {
			$this->markTestSkipped('Relative dates test with WP timezone offset requires runkit7_function_redefine(), which requires PHP 7.');
		}

		$server_date = 1603292400; // October 21, 2020 3:00:00 PM GMT

		# Copy original functions
		runkit7_function_copy( 'time', 'time_original' );
		runkit7_function_copy( 'strtotime', 'strtotime_original' );

		# Redefine time() to return static server time
		runkit7_function_redefine( 'time', '', "return {$server_date};" );

		# Redefine strtotime to use server time by default unless a timestamp is specified
		runkit7_function_redefine( 'strtotime', '', '$args = func_get_args(); return !empty($args[1]) ? strtotime_original($args[0], $args[1]) : strtotime_original($args[0], ' . $server_date . ');' );

		$relative_date_strings = array(
			'plus_two_hours' => '+2 hours',
			'yesterday'      => 'yesterday',
			'tomorrow'       => 'tomorrow',
			'today'          => 'today',
			'three_days_ago' => '3 days ago',
			'one_month_ago'  => '-1 month',
		);

		$server_date_relative = array(
			'plus_two_hours' => '2020-10-21 17:00:00',
			'yesterday'      => '2020-10-20 00:00:00',
			'tomorrow'       => '2020-10-22 00:00:00',
			'today'          => '2020-10-21 00:00:00',
			'three_days_ago' => '2020-10-18 15:00:00',
			'one_month_ago'  => '2020-09-21 15:00:00',
		);

		foreach ( $relative_date_strings as $key => $string ) {
			$result = GravityView_frontend::process_search_dates( array( 'start_date' => $string ) );
			$this->assertEquals( array( 'start_date' => $server_date_relative[ $key ] ), $result );
		}

		// Let's set the GMT offset to +10 hours (Australia/Brisbane where our "today" is their "tomorrow")
		update_option( 'gmt_offset', '10' );

		$wp_date_relative = array(
			'plus_two_hours' => '2020-10-22 03:00:00',
			'today'          => '2020-10-22 00:00:00',
			'tomorrow'       => '2020-10-23 00:00:00',
			'yesterday'      => '2020-10-21 00:00:00',
			'three_days_ago' => '2020-10-19 01:00:00',
			'one_month_ago'  => '2020-09-22 01:00:00',
		);

		foreach ( $relative_date_strings as $key => $string ) {
			$result = GravityView_frontend::process_search_dates( array( 'start_date' => $string ) );
			$this->assertEquals( array( 'start_date' => $wp_date_relative[ $key ] ), $result );
		}

		# Revert back to original function definitions/timezone
		runkit7_function_remove( 'time' );
		runkit7_function_remove( 'strtotime' );
		runkit7_function_copy( 'time_original', 'time' );
		runkit7_function_copy( 'strtotime_original', 'strtotime' );
		update_option( 'gmt_offset', '0' );
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

	/**
	 * @covers GravityView_frontend::single_entry_title()
	 */
	public function test_single_entry_title() {

		// We test check_entry_display elsewhere
		add_filter( 'gravityview/single/title/check_entry_display', '__return_false' );

		$form = $this->factory->form->create_and_get();
		$_entry = $this->factory->entry->create_and_get( array( 'form_id' => $form['id'] ) );
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );

		$view = \GV\View::from_post( $_view );
		$entry = \GV\GF_Entry::by_id( $_entry['id'] );

		global $post;

		$post = $_view;

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;
		gravityview()->request->returns['is_entry'] = $entry;

		$view->settings->set( 'single_title', '{:1} is the title' );

		$outside_loop = GravityView_frontend::getInstance()->single_entry_title( 'Original Title' );
		$this->assertEquals( 'Original Title', $outside_loop, 'we are outside the loop; this should return the original' );

		add_filter( 'gravityview/single/title/out_loop', '__return_true' );

		$no_post_id = GravityView_frontend::getInstance()->single_entry_title( 'Original Title' );
		$this->assertEquals( 'Original Title', $no_post_id, 'We did not pass a $post ID; this should return the original' );

		$different_ids = GravityView_frontend::getInstance()->single_entry_title( 'Original Title', ( $_view->ID + 1 ) );
		$this->assertEquals( 'Original Title', $different_ids, 'The global $post ID and the passed post id are different; this should return the original' );

		$should_work = GravityView_frontend::getInstance()->single_entry_title( 'Original Title', $_view->ID );
		$this->assertEquals( sprintf( '%s is the title', $_entry['1'] ), $should_work );

		$single_entry_title = GravityView_frontend::getInstance()->single_entry_title( 'Original Title', $_view->ID );
		$this->assertEquals( sprintf( '%s is the title', $_entry['1'] ), $single_entry_title );

		$form2 = $this->factory->form->create_and_get();
		$_entry2 = $this->factory->entry->create_and_get( array( 'form_id' => $form2['id'] ) );
		$_view2 = $this->factory->view->create_and_get( array( 'form_id' => $form2['id'] ) );
		$view2 = \GV\View::from_post( $_view2 );
		$view2->settings->set( 'single_title', '{:1} is the title for two' );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = false;
		gravityview()->request->returns['is_entry'] = $entry;

		global $post;
		$post = $this->factory->post->create_and_get( array(
			'post_content' => '[gravityview id="' . $view->ID . '"][gravityview id="' . $view2->ID . '"]'
		) );

		$single_entry_title = GravityView_frontend::getInstance()->single_entry_title( 'Original Title No GVID', $post->ID );
		$this->assertEquals( 'Original Title No GVID', $single_entry_title, 'The post has two Views but no GVID; should return original' );

		$_GET = array(
			'gvid' => $view->ID
		);
		$entry_1_should_win = GravityView_frontend::getInstance()->single_entry_title( 'Original Title Entry 1', $post->ID );
		$this->assertEquals( sprintf( '%s is the title', $_entry['1'] ), $entry_1_should_win );

		$_GET = array(
			'gvid' => $view2->ID
		);
		$entry_2_should_win = GravityView_frontend::getInstance()->single_entry_title( 'Original Title Entry 2', $post->ID );
		$this->assertEquals( sprintf( '%s is the title for two', $_entry2['1'] ), $entry_2_should_win );

		$post_id = $post->ID;
		unset( $post );
		$_GET = array();
		$single_entry_title = GravityView_frontend::getInstance()->single_entry_title( 'Original Title', $post_id );
		$this->assertEquals( 'Original Title', $single_entry_title, 'There is no global $post and no GVID; should return original' );

		remove_filter( 'gravityview/single/title/out_loop', '__return_true' );
		remove_filter( 'gravityview/single/title/check_entry_display', '__return_false' );
		$_GET = array();
	}

	/**
	 * @covers GravityView_Field_Is_Read::maybe_mark_entry_as_read()
	 * @covers GravityView_Field_Is_Read::get_value()
	 */
	public function test_marking_single_entry_as_read() {
		add_filter( 'gk/gravityview/view/entries/cache', '__return_false' );
		add_filter( 'gravityview_use_cache', '__return_false' );

		$form = $this->factory->form->import_and_get( 'simple.json' );

		$custom_read_label = 'Custom Read Label';

		$post = $this->factory->view->create_and_get( [
			'form_id'     => $form['id'],
			'template_id' => 'table',
			'settings'    => [
				'show_only_approved' => false,
			],
			'fields'      => [
				'single_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id'            => 'is_read',
						'is_read_label' => $custom_read_label,
					],
				],
			],
		] );

		$view = \GV\View::from_post( $post );

		$entry = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status'  => 'active',
		] );

		$entry = \GV\GF_Entry::by_id( $entry['id'] );

		gravityview()->request                      = new \GV\Mock_Request();
		gravityview()->request->returns['is_view']  = $view;
		gravityview()->request->returns['is_entry'] = $entry;

		$renderer = new \GV\Entry_Renderer();

		// Entry is not marked as read - user does not have "gravityview_edit_entries" capability.
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'contributor' ] ) );

		$output = $renderer->render( $entry, $view );
		$this->assertStringNotContainsString( $custom_read_label, $output );

		// Entry is marked as read.
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$output = $renderer->render( $entry, $view );
		$entry  = \GV\GF_Entry::by_id( $entry['id'] );

		$this->assertStringContainsString( $custom_read_label, $output );
		$this->assertEquals( '1', $entry->as_entry()['is_read'] );

		// Custom read label can be set via filter.
		$filtered_read_label_filter = 'Filtered - Custom Read Label';

		add_filter( 'gk/gravityview/field/is-read/label', function ( $label ) use ( $custom_read_label, $filtered_read_label_filter ) {
			$this->assertEquals( $label, $custom_read_label );

			return $filtered_read_label_filter;
		}, 10, 2 );

		$output = $renderer->render( $entry, $view );
		$this->assertStringContainsString( $filtered_read_label_filter, $output );

		remove_all_filters( 'gk/gravityview/field/is-read/label' );

		// Entry is not marked as read - disabled in View settings.
		$entry = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status'  => 'active',
		] );

		$entry = \GV\GF_Entry::by_id( $entry['id'] );
		$view->settings->set( 'mark_entry_as_read', false );

		gravityview()->request->returns['is_view']  = $view;
		gravityview()->request->returns['is_entry']  = $entry;

		$renderer->render( $entry, $view );
		$entry  = \GV\GF_Entry::by_id( $entry['id'] );

		$this->assertEquals( '0', $entry->as_entry()['is_read'] );

		remove_all_filters( 'gk/gravityview/view/entries/cache' );
		remove_all_filters( 'gravityview_use_cache' );
	}

	public function test_marking_multiple_single_entries_as_read() {
		add_filter( 'gk/gravityview/view/entries/cache', '__return_false' );
		add_filter( 'gravityview_use_cache', '__return_false' );

		$form1 = $this->factory->form->import_and_get( 'simple.json' );
		$form2 = $this->factory->form->import_and_get( 'simple.json' );

		$custom_read_label = 'Custom Read Label';
		$custom_unread_label = 'Custom Unread Label';

		$post = $this->factory->view->create_and_get( [
			'form_id'     => $form1['id'],
			'template_id' => 'table',
			'settings'    => [
				'show_only_approved' => false,
			],
			'fields'      => [
				'single_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id'            => 'is_read',
						'form_id'       => $form1['id'],
						'is_read_label' => $custom_read_label,
						'is_unread_label' => $custom_unread_label,
					],
					wp_generate_password( 4, false ) => [
						'id'            => 'is_read',
						'form_id'       => $form2['id'],
						'is_read_label' => $custom_read_label,
						'is_unread_label' => $custom_unread_label,
					],
				],
			],
			'joins' => array(
				array( $form1['id'], '2', $form2['id'], '2' ),
			),
		] );

		$view = \GV\View::from_post( $post );

		$entry1 = $this->factory->entry->create_and_get( [
			'form_id' => $form1['id'],
			'status'  => 'active',
			'2' => 1,
		] );

		$entry1 = \GV\GF_Entry::by_id( $entry1['id'] );

		$entry2 = $this->factory->entry->create_and_get( [
			'form_id' => $form2['id'],
			'status'  => 'active',
			'2' => 1,
		] );

		$entry2 = \GV\GF_Entry::by_id( $entry2['id'] );

		$entries = GV\Multi_Entry::from_entries( [ $entry1, $entry2 ] );

		gravityview()->request                      = new \GV\Mock_Request();
		gravityview()->request->returns['is_view']  = $view;
		gravityview()->request->returns['is_entry'] = $entries;

		$renderer = new \GV\Entry_Renderer();

		// Entry is not marked as read - user does not have "gravityview_edit_entries" capability.
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'contributor' ] ) );

		$output = $renderer->render( $entries, $view );

		preg_match_all('/' . preg_quote($custom_unread_label, '/') . '/', $output, $matches);
		$this->assertGreaterThanOrEqual(2, count($matches[0]));

		// Entry is marked as read.
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );

		$output = $renderer->render( $entries, $view );
		$entry1  = \GV\GF_Entry::by_id( $entry1['id'] );
		$entry2  = \GV\GF_Entry::by_id( $entry2['id'] );

		preg_match_all('/' . preg_quote($custom_read_label, '/') . '/', $output, $matches);
		$this->assertGreaterThanOrEqual(2, count($matches[0]));

		$this->assertEquals( '1', $entry1->as_entry()['is_read'] );
		$this->assertEquals( '1', $entry2->as_entry()['is_read'] );

		// Custom read label can be set via filter.
		$filtered_read_label_filter = 'Filtered - Custom Read Label';

		add_filter( 'gk/gravityview/field/is-read/label', function ( $label ) use ( $custom_read_label, $filtered_read_label_filter ) {
			$this->assertEquals( $label, $custom_read_label );

			return $filtered_read_label_filter;
		}, 10, 2 );

		$output = $renderer->render( $entries, $view );
		preg_match_all('/' . preg_quote($filtered_read_label_filter, '/') . '/', $output, $matches);
		$this->assertGreaterThanOrEqual(2, count($matches[0]));

		remove_all_filters( 'gk/gravityview/field/is-read/label' );

		// Entry is not marked as read - disabled in View settings.
		$entry1 = $this->factory->entry->create_and_get( [
			'form_id' => $form1['id'],
			'status'  => 'active',
		] );

		$entry1 = \GV\GF_Entry::by_id( $entry1['id'] );

		$entry2 = $this->factory->entry->create_and_get( [
			'form_id' => $form2['id'],
			'status'  => 'active',
		] );

		$entry2 = \GV\GF_Entry::by_id( $entry2['id'] );

		$view->settings->set( 'mark_entry_as_read', false );

		$entries = GV\Multi_Entry::from_entries( [ $entry1, $entry2 ] );

		gravityview()->request                      = new \GV\Mock_Request();
		gravityview()->request->returns['is_view']  = $view;
		gravityview()->request->returns['is_entry'] = $entries;

		$output = $renderer->render( $entries, $view );

		$entry1  = \GV\GF_Entry::by_id( $entry1['id'] );
		$entry2  = \GV\GF_Entry::by_id( $entry1['id'] );

		$this->assertEquals( '0', $entry1->as_entry()['is_read'] );
		$this->assertEquals( '0', $entry2->as_entry()['is_read'] );

		preg_match_all('/' . preg_quote($custom_unread_label, '/') . '/', $output, $matches);
		$this->assertGreaterThanOrEqual(2, count($matches[0]));

		remove_all_filters( 'gk/gravityview/view/entries/cache' );
		remove_all_filters( 'gravityview_use_cache' );
	}
}
