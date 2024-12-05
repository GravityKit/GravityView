<?php

namespace GV\Search\Fields;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field_All}
 *
 * @since $ver$
 */
final class Search_Field_All_Test extends TestCase {
	/**
	 * Test case for {@see Search_Field_All::get_value()}.
	 *
	 * @since $ver$
	 */
	public function test_get_value(): void {
		$field = Search_Field_All::from_configuration( [ 'value' => 1234 ] );
		self::assertSame( [ 'type' => 'all', 'label' => 'Search Everything', 'value' => '1234' ], $field->to_configuration() );
	}
}
