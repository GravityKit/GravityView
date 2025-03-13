<?php

namespace GV\Search;

use GV\Search\Fields\Search_Field_All;
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
				'asdf' => [ 'id' => 'all' ],
			],
			'search_advanced' => [
				'asdf2' => [ 'id' => 'all' ],
			],
		] );

		self::assertSame( 2, $collection->count() );
		self::assertSame(
			[
				'search_default' => [
					'asdf' => [
						'type'     => 'all',
						'label'    => 'Unknown Field',
						'value'    => '',
						'position' => 'search_default',
						'UID'      => 'asdf',
					],
				],
				'search_advanced' => [
					'asdf2' => [
						'type'     => 'all',
						'label'    => 'Unknown Field',
						'value'    => '',
						'position' => 'search_advanced',
						'UID'      => 'asdf2',
					],
				],
			],
			$collection->to_configuration()
		);
	}
}
