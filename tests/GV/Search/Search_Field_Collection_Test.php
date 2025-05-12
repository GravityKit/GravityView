<?php

namespace GV\Search;

use GV\Collection_Position_Aware;
use GV\Grid;
use JsonException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field_Collection}
 *
 * @since $ver$
 */
final class Search_Field_Collection_Test extends TestCase {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function setUp(): void {
		include_once GRAVITYVIEW_DIR . 'includes/admin/class-gravityview-admin-view-item.php';
	}

	/**
	 * Makes sure the collection is position aware, as it is used in various places.
	 *
	 * @since $ver$
	 */
	public function test_collection_is_position_aware(): void {
		$collection = Search_Field_Collection::from_configuration( [] );
		self::assertInstanceOf( Collection_Position_Aware::class, $collection );
	}

	/**
	 * Test case for {@see Search_Field_Collection::from_legacy_configuration()}.
	 *
	 * @since $ver$
	 * @throws JsonException When JSON could not be generated.
	 */
	public function test_from_legacy_configuration(): void {
		$configuration = [
			'search_layout' => 'horizontal',
			'search_clear'  => '1',
			'search_fields' => json_encode(
				[
					[
						'field' => 'search_all',
						'input' => 'input_text',
						'label' => 'Custom label',
					],
					[
						'field' => 'entry_date',
						'input' => 'date_range',
						'label' => 'Entry Date',
					],
					[
						'field' => '1',
						'input' => 'radio',
						'label' => '',
					],
				],
				JSON_THROW_ON_ERROR
			),
			'search_mode'   => 'all',
			'sieve_choices' => '0',
			'id'            => 'search_bar',
			'label'         => 'Search Bar',
			'form_id'       => '1',
		];

		$collection = Search_Field_Collection::from_legacy_configuration( $configuration, null );
		$rows       = Grid::get_rows_from_collection( $collection, 'search-general' );
		self::assertCount( 2, $rows );
		self::assertCount( 5, $collection ); // 3 fields + Search Button and Search Mode

		// Todo: Add some basic assertions on fields.
	}

	/**
	 * Test case for {@see Search_Field_Collection::from_configuration()} and
	 * {@see Search_Field_Collection::to_configuration()}.
	 *
	 * @since $ver$
	 */
	public function test_from_configuration(): void {
		$collection = Search_Field_Collection::from_configuration( [
			'search_default'  => [
				'asdf' => [ 'id' => 'search_all' ],
			],
			'search_advanced' => [
				'asdf2' => [ 'id' => 'search_all' ],
			],
		] );

		self::assertSame( 2, $collection->count() );
		self::assertSame(
			[
				'search_default'  => [
					'asdf' => [
						'id'       => 'search_all',
						'UID'      => 'asdf',
						'type'     => 'search_all',
						'label'    => 'Search Everything',
						'position' => 'search_default',
					],
				],
				'search_advanced' => [
					'asdf2' => [
						'id'       => 'search_all',
						'UID'      => 'asdf2',
						'type'     => 'search_all',
						'label'    => 'Search Everything',
						'position' => 'search_advanced',
					],
				],
			],
			$collection->to_configuration()
		);
	}
}
