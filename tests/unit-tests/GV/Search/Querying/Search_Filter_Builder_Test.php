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
		$form       = $this->factory->form->create_and_get();
		$view       = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'widgets' => [
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
			]
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
			'basic' => [
				[
					'gv_search' => 'test',
					'gv_id'     => 123,
					'gv_id|op'  => '!=',
					'gv_by'     => '2',
					'gv_start'  => '01/01/2025',
					'gv_end'  => '12/31/2025',
				],
				[
					'field_filters' => [
						[
							'operator' => 'contains',
							'value'    => 'test',
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
						'mode' => 'any',
					],
//					'start_date' => '2025-04-01', // This is missing because it is out of the bounds set by the View.
					'end_date'   => '2025-12-31',
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
}
