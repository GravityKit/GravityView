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
	 * Test case for {@see Search_Field::collect_template_data()}.
	 *
	 * @since $ver$
	 */
	public function test_to_template_data(): void {
		$field                 = Search_Field_All::from_configuration( [] );
		$_REQUEST['gv_search'] = 'Search Value';
		$data                  = $field->collect_template_data();

		self::assertSame( $data['value'], 'Search Value' );
	}
}
