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
	 * https://docs.gravityview.co/article/115-changing-the-format-of-the-search-widgets-date-picker
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
			'template_id' => 'default_table',
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
		);
	}

	public function test_search_is_approved_gf_query() {
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'default_table',
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

			'4'  => 'support@gravityview.co',
			'16' => 'Contact us if you have any questions.',
		) );
		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		/** Approved sentinel. */
		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',

			'4'  => 'gravityview.co',
			'16' => 'Our website.',
		) );
		gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, \GravityView_Entry_Approval_Status::APPROVED );

		/** Unapproved entry. */
		$this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'status' => 'active',

			'4'  => 'support@gravityview.co',
			'16' => 'Contact us if you have any questions.',
		) );

		$_GET = array(
			'filter_4'  => 'support',
			'filter_16' => 'support', // In mode "any" this should be ignored
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
			'template_id' => 'default_table',
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

				gform_update_meta( $entry['id'], \GravityView_Entry_Approval::meta_key, $status === 'approved' ? \GravityView_Entry_Approval_Status::APPROVED : \GravityView_Entry_Approval_Status::DISAPPROVED );
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

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'default_table',
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
		if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
			$this->markTestSkipped( 'Requires \GF_Query from Gravity Forms 2.3' );
		}

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

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'default_table',
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

		update_user_meta( $gamma, 'custom_meta', 'custom' );
		add_filter( 'gravityview/widgets/search/created_by/user_meta_fields', function() {
			return array( 'custom_meta' );
		} );
		$this->assertEquals( 1, $view->get_entries()->count() );

		remove_all_filters( 'gravityview/widgets/search/created_by/user_meta_fields' );

		$_GET = array();
	}

	/**
	 * https://gist.github.com/zackkatz/66e9fb2147a9eb1a2f2e
	 */
	public function test_override_search_operator() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'default_table',
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

		$_GET = array( 'filter_16' => 'hello' );
		$this->assertEquals( 2, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'world' );
		$this->assertEquals( 2, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'hello world, goodbye moon' );
		$this->assertEquals( 0, $view->get_entries()->fetch()->count() );

		$_GET = array( 'filter_16' => 'hello world' );
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

		remove_filter( 'gravityview_fe_search_criteria', $callback );

		add_filter( 'gravityview_search_operator', $callback = function( $operator, $field ) {
			if ( $field['key'] == '16' ) {
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

		remove_filter( 'gravityview_search_operator', $callback );

		$_GET = array();
	}

	/**
	 * https://github.com/gravityview/GravityView/issues/1233
	 */
	public function test_search_date_created() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'default_table',
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

	public function test_operator_url_overrides() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'default_table',
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

		$_GET['filter_16'] = 'hello';
		$_GET['filter_16|op'] = '!='; // Override doesn't work, as '!=' is not in whitelist
		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 2, $entries );
		$this->assertEquals( $hello['id'], $entries[0]['id'] );
		$this->assertEquals( $hello_world['id'], $entries[1]['id'] );

		add_filter( 'gravityview/search/operator_whitelist', $callback = function() {
			return array( '!=' );
		} );

		$entries = $view->get_entries()->fetch()->all();
		$this->assertCount( 2, $entries );
		$this->assertEquals( $world['id'], $entries[0]['id'] );
		$this->assertEquals( $hello_world['id'], $entries[1]['id'] );

		remove_filter( 'gravityview/search/operator_whitelist', $callback );

		$_GET = array();
	}

	public function test_search_all_basic() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'default_table',
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

		$_GET = array();
	}

	public function test_search_all_basic_choices() {
		$form = $this->factory->form->import_and_get( 'complete.json' );
		$post = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'template_id' => 'default_table',
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

		add_filter( $filter = 'gravityview/search/searchable_fields/whitelist', $callback = function( $fields, $view, $with_full ) {
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

			return $fields;
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
}
