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
	 * Test case for {@see Search_Field::to_configuration()}.
	 *
	 * @since $ver$
	 */
	public function test_to_array(): void {
		$field = new class extends Search_Field {
			protected static string $type = 'private';
		};

		self::assertSame(
			[
				'id'       => 'private',
				'UID'      => '',
				'type'     => 'private',
				'label'    => 'Unknown Field',
				'position' => '',
			],
			$field->to_configuration()
		);

		$from_array = $field::from_configuration(
			[
				'type'  => 'ignore me',
				'label' => 'Custom Label',
			]
		);

		self::assertSame(
			[
				'id'       => 'private',
				'UID'      => '',
				'type'     => 'private',
				'label'    => 'Custom Label',
				'position' => '',
			],
			$from_array->to_configuration()
		);
	}
}
