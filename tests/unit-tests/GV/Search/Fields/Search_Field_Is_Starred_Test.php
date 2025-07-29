<?php

use GV\Search\Fields\Search_Field_Is_Starred;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field_Is_Starred}.
 *
 * @since 2.42
 */
final class Search_Field_Is_Starred_Test extends TestCase {
	/**
	 * The search field we're testing.
	 *
	 * @since 2.42
	 */
	private Search_Field_Is_Starred $search_field;

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->search_field = new Search_Field_Is_Starred();
	}

	/**
	 * Tests the basic getters specific to Search_Field_Is_Starred.
	 *
	 * @since 2.42
	 */
	public function test_basic_getters(): void {
		self::assertSame( 'is_starred', $this->search_field->get_type() );
		self::assertSame( 'Filter on starred entries', $this->search_field->get_description() );
		self::assertTrue( $this->search_field->is_of_type( 'is_starred' ) );
		self::assertFalse( $this->search_field->is_of_type( 'other_type' ) );

		self::assertStringContainsString(
			'<i class="dashicons dashicons-star-half"></i>',
			$this->search_field->icon_html()
		);
	}

	/**
	 * Tests the configuration includes default values when set.
	 *
	 * @since 2.42
	 */
	public function test_configuration_with_defaults(): void {
		$field = Search_Field_Is_Starred::from_configuration( [
			'custom_label' => 'Custom Star Filter',
		] );

		$config = $field->to_configuration();
		self::assertSame( 'is_starred', $config['id'] );
		self::assertSame( 'is_starred', $config['type'] );
		self::assertSame( 'Is Starred', $config['label'] );
		self::assertSame( 'Custom Star Filter', $config['custom_label'] );
	}

	/**
	 * Tests the template data includes the correct input type and structure.
	 *
	 * @since 2.42
	 */
	public function test_template_data_structure(): void {
		$field = Search_Field_Is_Starred::from_configuration( [
			'custom_class' => 'starred-filter-class',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'is_starred', $data['type'] );
		self::assertSame( 'single_checkbox', $data['input'] );
		self::assertSame( 'starred-filter-class', $data['custom_class'] );
	}

	/**
	 * Tests the field returns the correct field type identifier.
	 *
	 * @since 2.42
	 */
	public function test_field_type_identifier(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'is_starred', $template_data['type'] );
		self::assertSame( 'single_checkbox', $template_data['input'] );
	}

	/**
	 * Tests custom label overrides default label in template data.
	 *
	 * @since 2.42
	 */
	public function test_custom_label_override(): void {
		$field = Search_Field_Is_Starred::from_configuration( [
			'custom_label' => 'Filter by Star Status',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'Filter by Star Status', $data['label'] );
	}

	/**
	 * Tests has_request_value returns true when filter_is_starred is set in request.
	 *
	 * @since 2.42
	 */
	public function test_has_request_value(): void {
		// Test with no request values.
		unset( $_REQUEST['filter_is_starred'] );
		self::assertFalse( $this->search_field->has_request_value() );

		// Test with value set.
		$_REQUEST['filter_is_starred'] = '1';
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with zero value.
		$_REQUEST['filter_is_starred'] = '0';
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with empty value.
		$_REQUEST['filter_is_starred'] = '';
		self::assertFalse( $this->search_field->has_request_value() );

		// Clean up.
		unset( $_REQUEST['filter_is_starred'] );
	}

	/**
	 * Tests configuration export and import maintains structure.
	 *
	 * @since 2.42
	 */
	public function test_configuration_maintains_structure(): void {
		$original_config = [
			'id'           => 'is_starred',
			'custom_label' => 'Star Status',
			'custom_class' => 'my-star-field',
			'show_label'   => false,
		];

		$field           = Search_Field_Is_Starred::from_configuration( $original_config );
		$exported_config = $field->to_configuration();

		self::assertSame( 'is_starred', $exported_config['id'] );
		self::assertSame( 'is_starred', $exported_config['type'] );
		self::assertSame( 'Star Status', $exported_config['custom_label'] );
		self::assertSame( 'my-star-field', $exported_config['custom_class'] );
		self::assertFalse( $exported_config['show_label'] );
	}
}
