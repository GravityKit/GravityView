<?php

namespace GV\Search\Fields;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field}.
 *
 * @since $ver$
 */
final class Search_Field_Test extends TestCase {
	/**
	 * Test case for {@see Search_Field::to_array()}.
	 *
	 * @since $ver$
	 */
	public function test_to_array(): void {
		$field = new class extends Search_Field {
			protected string $type = 'private';
		};

		self::assertSame( [ 'type' => 'private', 'label' => 'Unknown Field', 'value' => null ], $field->to_array() );

		$from_array = $field::from_array( [ 'type' => 'ignore me', 'label' => 'Custom Label', 'value' => 'is set' ] );

		self::assertSame(
			[ 'type' => 'private', 'label' => 'Custom Label', 'value' => 'is set' ],
			$from_array->to_array()
		);
	}
}
