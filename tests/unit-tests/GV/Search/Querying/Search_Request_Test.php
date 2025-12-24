<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

use GV\CLI_Request;
use GV\Frontend_Request;
use GV\Mock_Request;
use GV\Search\Querying\Search_Request;

/**
 * Tests for the {@see Search_Request} class.
 *
 * @group  search
 */
final class Search_Request_Test extends GV_UnitTestCase {
	/**
	 * Data provider for {@see self::test_is_search_request}.
	 *
	 * @since $ver$
	 *
	 * @return array[] The test cases.
	 */
	public function data_provider_for_test_is_search_request(): array {
		return [
			'empty arguments returns false' => [
				[],
				false,
			],
			'gv_search key returns true'    => [
				[ 'gv_search' => 'test' ],
				true,
			],
			'gv_start key returns true'     => [
				[ 'gv_start' => '2025-01-01' ],
				true,
			],
			'gv_end key returns true'       => [
				[ 'gv_end' => '2025-12-31' ],
				true,
			],
			'gv_by key returns true'        => [
				[ 'gv_by' => '1' ],
				true,
			],
			'gv_id key returns true'        => [
				[ 'gv_id' => '123' ],
				true,
			],
			'filter_1 key returns true'     => [
				[ 'filter_1' => 'value' ],
				true,
			],
			'input_1 key returns true'      => [
				[ 'input_1' => 'value' ],
				true,
			],
			'filter_1_2 key returns true'   => [
				[ 'filter_1_2' => 'value' ],
				true,
			],
			'unrelated key returns false'   => [
				[ 'some_other_key' => 'value' ],
				false,
			],
			'mode alone returns false'      => [
				[ 'mode' => 'all' ],
				false,
			],
		];
	}

	/**
	 * Test case for {@see Search_Request::is_search_request()}.
	 *
	 * @dataProvider data_provider_for_test_is_search_request
	 *
	 * @since        $ver$
	 *
	 * @param array $arguments The request argument
	 * @param bool  $expected  The expected result.
	 */
	public function test_is_search_request( array $arguments, bool $expected ): void {
		$request                           = new Mock_Request();
		$request->returns['get_arguments'] = $arguments;

		self::assertSame( $expected, Search_Request::is_search_request( $request ) );
	}

	/**
	 * Test case for {@see Search_Request::is_search_request()} with a CLI request.
	 *
	 * @since  $ver$
	 */
	public function test_is_search_request_with_cli_request(): void {
		global $argv;

		$original_argv = $argv;
		$argv          = [ 'script.php', '--gv_search=test' ];

		$request = new CLI_Request();
		self::assertTrue( Search_Request::is_search_request( $request ) );

		$argv = $original_argv;
		self::assertFalse( Search_Request::is_search_request( $request ) );
	}

	/**
	 * Test case for {@see Search_Request::is_search_request()} with a Frontend request.
	 *
	 * @since  $ver$
	 */
	public function test_is_search_request_with_frontend_request_uses_get(): void {
		$_GET['gv_search'] = 'test';

		$request = new Frontend_Request();
		self::assertTrue( Search_Request::is_search_request( $request ) );

		unset( $_GET['gv_search'] );
		self::assertFalse( Search_Request::is_search_request( $request ) );
	}

	/**
	 * Test case for {@see Search_Request::from_request()}.
	 *
	 * @since  $ver$
	 */
	public function test_from_request_creates_search_request(): void {
		$request                           = new Mock_Request();
		$request->returns['get_arguments'] = [
			'gv_search' => 'test',
			'mode'      => 'all',
		];

		$search_request = Search_Request::from_request( $request );

		self::assertInstanceOf( Search_Request::class, $search_request );

		$result = $search_request->to_array();
		self::assertSame( 'all', $result['mode'] );
	}

	/**
	 * Data provider for {@see self::test_from_arguments_returns_null_for_non_search}.
	 *
	 * @since $ver$
	 *
	 * @return array[] The test cases.
	 */
	public function data_provider_for_test_from_arguments_returns_null_for_non_search(): array {
		return [
			'empty arguments'    => [ [] ],
			'unrelated keys'     => [ [ 'foo' => 'bar', 'baz' => 'qux' ] ],
			'only mode'          => [ [ 'mode' => 'all' ] ],
			'invalid filter key' => [ [ 'filter_invalid' => 'value' ] ],
		];
	}

	/**
	 * Test case for {@see Search_Request::from_arguments()} without search arguments.
	 *
	 * @dataProvider data_provider_for_test_from_arguments_returns_null_for_non_search
	 *
	 * @since        $ver$
	 *
	 * @param array $arguments The arguments to test.
	 */
	public function test_from_arguments_returns_null_for_non_search( array $arguments ): void {
		self::assertNull( Search_Request::from_arguments( $arguments ) );
	}

	/**
	 * Data provider for {@see self::test_from_arguments_creates_search_request}
	 *
	 * @since $ver$
	 *
	 * @return array[] The test cases.
	 */
	public function data_provider_for_test_from_arguments_creates_search_request(): array {
		return [
			'gv_search'    => [ [ 'gv_search' => 'test' ] ],
			'gv_start'     => [ [ 'gv_start' => '2025-01-01' ] ],
			'gv_end'       => [ [ 'gv_end' => '2025-12-31' ] ],
			'gv_by'        => [ [ 'gv_by' => '1' ] ],
			'gv_id'        => [ [ 'gv_id' => '123' ] ],
			'filter_1'     => [ [ 'filter_1' => 'value' ] ],
			'input_1'      => [ [ 'input_1' => 'value' ] ],
			'filter_1_2'   => [ [ 'filter_1_2' => 'value' ] ],
			'filter_1_2:3' => [ [ 'filter_1_2:3' => 'value' ] ],
		];
	}

	/**
	 * Test case for {@see Search_Request::from_arguments()} with valid search arguments.
	 *
	 * @since        $ver$
	 *
	 * @dataProvider data_provider_for_test_from_arguments_creates_search_request
	 *
	 * @param array $arguments The arguments to test.
	 */
	public function test_from_arguments_creates_search_request( array $arguments ): void {
		self::assertInstanceOf( Search_Request::class, Search_Request::from_arguments( $arguments ) );
	}

	/**
	 * Data provider for {@see self::test_to_array}.
	 *
	 * @since $ver$
	 *
	 * @return array[] The test cases.
	 */
	public function data_provider_for_test_to_array(): array {
		return [
			'all gv_ fields with mode and custom operators' => [
				[
					'gv_search'         => 'query',
					'gv_id'             => '123',
					'gv_id|op'          => '!=',
					'gv_by'             => '42',
					'gv_start'          => '2025-01-01',
					'gv_end'            => '2025-12-31',
					'mode'              => 'all',
					'filter_is_starred' => '1',
					'filter_1'          => 'value1',
					'filter_1|op'       => '!=',
				],
				[
					'mode'    => 'all',
					'filters' => [
						[
							'key'         => 'search_all',
							'request_key' => 'gv_search',
							'operator'    => 'contains',
							'value'       => 'query',
						],
						[ 'key' => 'entry_id', 'request_key' => 'gv_id', 'operator' => '!=', 'value' => 123 ],
						[ 'key' => 'created_by', 'request_key' => 'gv_by', 'operator' => '=', 'value' => '42' ],
						[
							'key'         => 'is_starred',
							'request_key' => 'filter_is_starred',
							'operator'    => '=',
							'value'       => '1',
							'field_id'    => 'is_starred',
						],
						[
							'key'         => '1',
							'request_key' => 'filter_1',
							'operator'    => '!=',
							'value'       => 'value1',
							'field_id'    => '1',
						],
						[
							'key'        => 'entry_date',
							'start_date' => '2025-01-01',
							'end_date'   => '2025-12-31',
						],
					],
				],
			],
			'filter and input fields transformed'           => [
				[
					'filter_1'     => 'value1',
					'input_2_3'    => 'value2',
					'filter_4_5:6' => 'value3',
					'gv_search'    => 'test',
				],
				[
					'mode'    => 'any',
					'filters' => [
						[
							'key'         => '1',
							'request_key' => 'filter_1',
							'operator'    => '=',
							'value'       => 'value1',
							'field_id'    => '1',
						],
						[
							'key'         => '2.3',
							'request_key' => 'input_2_3',
							'operator'    => '=',
							'value'       => 'value2',
							'field_id'    => '2.3',
						],
						[
							'key'         => '4.5:6',
							'request_key' => 'filter_4_5:6',
							'operator'    => '=',
							'value'       => 'value3',
							'field_id'    => '4.5',
							'form_id'     => '6',
						],
						[
							'key'         => 'search_all',
							'request_key' => 'gv_search',
							'operator'    => 'contains',
							'value'       => 'test',
						],
					],
				],
			],
			'mode ALL uppercase normalizes to all'          => [
				[ 'gv_search' => 'test', 'mode' => 'ALL' ],
				[
					'mode'    => 'all',
					'filters' => [
						[
							'key'         => 'search_all',
							'request_key' => 'gv_search',
							'operator'    => 'contains',
							'value'       => 'test',
						],
					],
				],
			],
			'invalid mode defaults to any'                  => [
				[ 'gv_search' => 'test', 'mode' => 'invalid' ],
				[
					'mode'    => 'any',
					'filters' => [
						[
							'key'         => 'search_all',
							'request_key' => 'gv_search',
							'operator'    => 'contains',
							'value'       => 'test',
						],
					],
				],
			],
			'default mode is any'                           => [
				[ 'gv_search' => 'test' ],
				[
					'mode'    => 'any',
					'filters' => [
						[
							'key'         => 'search_all',
							'request_key' => 'gv_search',
							'operator'    => 'contains',
							'value'       => 'test',
						],
					],
				],
			],
		];
	}

	/**
	 * Test case for {@see Search_Request::to_array()}.
	 *
	 * @dataProvider data_provider_for_test_to_array
	 *
	 * @since        $ver$
	 *
	 * @param array $arguments The input arguments.
	 * @param array $expected  The expected output array.
	 */
	public function test_to_array( array $arguments, array $expected ): void {
		$search_request = Search_Request::from_arguments( $arguments );

		self::assertSame( $expected, $search_request->to_array() );
	}
}
