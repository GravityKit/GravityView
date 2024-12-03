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
	 * Test case for {@see Search_Field_Collection::from_configuration()} and
	 * {@see Search_Field_Collection::to_configuration()}.
	 *
	 * @since $ver$
	 */
	public function test_from_configuration(): void {
		$collection = Search_Field_Collection::from_configuration( [
			( new Search_Field_All() )->to_array(),
			[ 'ignore me' ],
		] );

		self::assertSame( 1, $collection->count() );
		self::assertSame( [ ( new Search_Field_All() )->to_array() ], $collection->to_configuration() );
	}
}
