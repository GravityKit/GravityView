<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

use GV\Search\Querying\Search_Filter_Builder;
use GV\Search\Querying\Search_Request;
use GV\View;

/**
 * Tests for the {@see Search_Filter_Builder} class.
 *
 * @group  search
 */
final class Search_Filter_Builder_Test extends GV_UnitTestCase {
	/**
	 * The View used for the tests.
	 *
	 * @since $ver$
	 *
	 * @var View
	 */
	private View $view;

	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public function setUp(): void {
		parent::setUp();
		$form       = $this->factory->form->create_and_get( [
			'title'  => 'Test Form',
			'fields' => [
				[
					'type'        => 'multiselect',
					'label'       => 'Multiselect',
					'visibility'  => 'visible',
					'storageType' => 'json',
					'inputs'      => null,
					'choices'     => [
						[
							'text'       => 'First Choice',
							'value'      => 'First Choice',
							'isSelected' => false,
						],
						[
							'text'       => 'Second Choice',
							'value'      => 'Second Choice',
							'isSelected' => false,
						],
					],
				],
			],
		] );
		$view       = $this->factory->view->create_and_get( [
			'form_id'  => $form['id'],
			'widgets'  => [
				'_' => [
					[
						'id'            => 'search_bar',
						'search_fields' => json_encode( [
							[ 'field' => 'search_all' ],
							[ 'field' => 'entry_id' ],
							[ 'field' => 'entry_date' ],
							[ 'field' => 'created_by' ],
						], JSON_THROW_ON_ERROR ),
					],
				],
			],
			'settings' => [
				'start_date' => '2025-04-01',
				'end_date'   => '2025-12-31',
			],
		] );
		$this->view = View::from_post( $view );
	}

	/**
	 * Data provider for {@see self::test_to_search_criteria()}.
	 *
	 * @since $ver$
	 *
	 * @return array
	 */
	public function data_provider_form_test_to_search_criteria(): array {
		return [
			'basic'                    => [
				[
					'gv_search' => ' test ', // Spaces intentional to test trimming.
					'gv_id'     => 123,
					'gv_id|op'  => '!=',
					'gv_by'     => '2',
					'gv_start'  => '05/01/2025',
					'gv_end'    => '12/31/2025',
				],
				[
					'field_filters' => [
						'mode' => 'any',
						[
							'operator' => 'contains',
							'value'    => 'test',
							'key'      => null,
						],
						[
							'key'      => 'id',
							'value'    => 123,
							'operator' => '=', //  != is not allowed, so should become `=`
						],
						[
							'key'      => 'created_by',
							'value'    => '2',
							'operator' => '=',
						],
					],
					'start_date'    => '2025-05-01 00:00:00',
					'end_date'      => '2025-12-31 23:59:59',
				],
			],
			'split words'              => [
				[
					'gv_search' => '+required -without +"with \spaces" optional',
				],
				[
					'field_filters' => [
						'mode' => 'any',
						[
							'operator' => 'contains',
							'value'    => 'with \spaces',
							// 'required' => true, // This isn't true, because there is a JSON field.
							'key'      => null,
						],
						[
							'operator' => 'contains',
							'value'    => 'required',
							'required' => true,
							'key'      => null,
						],
						[
							'operator' => 'not contains',
							'value'    => 'without',
							'key'      => null,
						],
						[
							'operator' => 'contains',
							'value'    => 'optional',
							'key'      => null,
						],
						[
							'operator' => 'contains',
							'value'    => 'with \\\\\\\\spaces',
							// 'required' => true, // This isn't true, because there is a JSON field.
							'key'      => null,
						],
					],
				],
			],
			'out of bounds start date' => [
				[
					'gv_start' => '01/01/2025',
					'gv_end'   => '09/30/2025',
				],
				[
					'field_filters' => [
						'mode' => 'any',
					],
					// 'start_date' => '2025-04-01', // This is missing because it is out of the bounds set by the View.
					'end_date'      => '2025-09-30 23:59:59',
				],
			],
			'out of bounds end date'   => [
				[
					'gv_start' => '05/01/2025',
					'gv_end'   => '09/30/2026',
				],
				[
					'field_filters' => [
						'mode' => 'any',
					],
					'start_date'    => '2025-05-01 00:00:00',
					// 'end_date'      => '2026-09-30 23:59:59', // This is missing because it is out of the bounds set by the View.
				],
			],
			'single day search'        => [
				[
					'gv_start' => '05/01/2025',
				],
				[
					'field_filters' => [
						'mode' => 'any',
					],
					'start_date'    => '2025-05-01 00:00:00',
					'end_date'      => '2025-05-01 23:59:59',
				],
			],
		];
	}

	/**
	 * Test case for {@see Search_Filter_Builder::to_search_criteria()}.
	 *
	 * @dataProvider data_provider_form_test_to_search_criteria
	 *
	 * @since        $ver$
	 */
	public function test_to_search_criteria( array $request_arguments, array $expected_search_criteria ): void {
		$request = Search_Request::from_arguments( $request_arguments );
		$result  = Search_Filter_Builder::get_instance()->to_search_criteria( $request, $this->view );

		self::assertSame( $expected_search_criteria, $result );
	}

	/**
	 * Test case for {@see Search_Filter_Builder::to_search_criteria()} without trimmed input.
	 *
	 * @since $ver$
	 */
	public function test_no_search_input_trim(): void {
		add_filter( 'gravityview/search-trim-input', '__return_false' );
		add_filter( 'gravityview/search-all-split-words', '__return_false' );
		$request = Search_Request::from_arguments( [
			'gv_search' => '  test  ',
		] );

		$result = Search_Filter_Builder::get_instance()->to_search_criteria( $request, $this->view );

		remove_filter( 'gravityview/search-trim-input', '__return_false' );
		remove_filter( 'gravityview/search-all-split-words', '__return_false' );

		// Multiple spaces are replaced with a single space.
		// Todo: This is because that is the current behavior, maube we need to remove this.
		self::assertSame( ' test ', $result['field_filters'][0]['value'] );
	}

	/**
	 * Test case for {@see Search_Filter_Builder::to_search_criteria()} that adjusts the search parameters based on the
	 * timezone.
	 *
	 * @since $ver$
	 */
	public function test_search_adjust_timezone(): void {
		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'Europe/Amsterdam' );

		add_filter( 'gravityview_date_created_adjust_timezone', '__return_true' );

		$request = Search_Request::from_arguments( [
			'gv_start' => '2025-06-16 13:00:01',
		] );

		$result = Search_Filter_Builder::get_instance()->to_search_criteria( $request, $this->view );

		remove_filter( 'gravityview_date_created_adjust_timezone', '__return_true' );
		update_option( 'timezone_string', $original_timezone );

		self::assertSame( '2025-06-16 11:00:01', $result['start_date'] );
		self::assertSame( '2025-06-16 21:59:59', $result['end_date'] );
	}
}
