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
					'gv_search' => 'test',
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
					'start_date'    => '2025-05-01',
					'end_date'      => '2025-12-31',
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
							'required' => true,
						],
						[
							'operator' => 'contains',
							'value'    => 'required',
							'required' => true,
						],
						[
							'operator' => 'not contains',
							'value'    => 'without',
						],
						[
							'operator' => 'contains',
							'value'    => 'optional',
						],
						[
							'operator' => 'contains',
							'value'    => 'with \\\\\\\\spaces',
							'required' => true,
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
					'end_date'      => '2025-09-30',
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
					'start_date'    => '2025-05-01',
					// 'end_date'      => '2026-09-30', // This is missing because it is out of the bounds set by the View.
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
