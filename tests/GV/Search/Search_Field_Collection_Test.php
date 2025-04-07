<?php

namespace GV\Search;

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
	 * Test case for {@see Search_Field_Collection::from_configuration()} and
	 * {@see Search_Field_Collection::to_configuration()}.
	 *
	 * @since $ver$
	 */
	public function test_from_configuration(): void {
		$collection = Search_Field_Collection::from_configuration( [
			'search_default' => [
				'asdf' => [ 'id' => 'search_all' ],
			],
			'search_advanced' => [
				'asdf2' => [ 'id' => 'search_all' ],
			],
		] );

		self::assertSame( 2, $collection->count() );
		self::assertSame(
			[
				'search_default' => [
					'asdf' => [
						'UID'      => 'asdf',
						'type'     => 'search_all',
						'label'    => 'Search Everything',
						'position' => 'search_default',
					],
				],
				'search_advanced' => [
					'asdf2' => [
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
