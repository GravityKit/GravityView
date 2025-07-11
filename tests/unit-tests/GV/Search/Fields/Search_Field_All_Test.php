<?php

use GV\Search\Fields\Search_Field_All;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field_All}.
 *
 * @since 2.42
 */
final class Search_Field_All_Test extends TestCase {
	/**
	 * The search field we're testing.
	 *
	 * @since 2.42
	 */
	private Search_Field_All $search_field;

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->search_field = new Search_Field_All();
	}

	/**
	 * Tests the basic getters specific to Search_Field_All.
	 *
	 * @since 2.42
	 */
	public function test_basic_getters(): void {
		self::assertSame( 'search_all', $this->search_field->get_type() );
		self::assertSame( 'Search across all entry fields', $this->search_field->get_description() );
		self::assertTrue( $this->search_field->is_of_type( 'search_all' ) );
		self::assertFalse( $this->search_field->is_of_type( 'other_type' ) );

		self::assertStringContainsString(
			'<i class="dashicons dashicons-admin-site-alt3"></i>',
			$this->search_field->icon_html()
		);
	}

	/**
	 * Tests the configuration includes the placeholder value when set.
	 *
	 * @since 2.42
	 */
	public function test_configuration_with_placeholder(): void {
		$field = Search_Field_All::from_configuration( [
			'placeholder' => 'Search entries...',
		] );

		$config = $field->to_configuration();
		self::assertSame( 'search_all', $config['id'] );
		self::assertSame( 'search_all', $config['type'] );
		self::assertSame( 'Search Everything', $config['label'] );
		self::assertSame( 'Search entries...', $config['placeholder'] );
	}

	/**
	 * Tests the template data includes the placeholder value when set.
	 *
	 * @since 2.42
	 */
	public function test_template_data_with_placeholder(): void {
		$field = Search_Field_All::from_configuration( [
			'placeholder' => 'Search entries...',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'search_all', $data['type'] );
		self::assertSame( 'Search entries...', $data['placeholder'] );
	}

	/**
	 * Tests has_request_value returns true when gv_search is set in request.
	 *
	 * @since 2.42
	 */
	public function test_has_request_value(): void {
		unset( $_REQUEST['gv_search'] );
		self::assertFalse( $this->search_field->has_request_value() );

		$_REQUEST['gv_search'] = 'test search';
		self::assertTrue( $this->search_field->has_request_value() );

		$_REQUEST['gv_search'] = '';
		self::assertFalse( $this->search_field->has_request_value() );

		$_REQUEST['gv_search'] = '0';
		self::assertTrue( $this->search_field->has_request_value() );

		unset( $_REQUEST['gv_search'] );
	}
}
