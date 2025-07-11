<?php

use GV\Search\Fields\Search_Field_Created_By;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field_Created_By}.
 *
 * @since 2.42
 */
final class Search_Field_Created_By_Test extends TestCase {
	/**
	 * The search field we're testing.
	 *
	 * @since 2.42
	 */
	private Search_Field_Created_By $search_field;

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->search_field = new Search_Field_Created_By();
	}

	/**
	 * Tests the basic getters specific to Search_Field_Created_By.
	 *
	 * @since 2.42
	 */
	public function test_basic_getters(): void {
		self::assertSame( 'created_by', $this->search_field->get_type() );
		self::assertSame( 'Search on entry creator', $this->search_field->get_description() );
		self::assertTrue( $this->search_field->is_of_type( 'created_by' ) );
		self::assertFalse( $this->search_field->is_of_type( 'other_type' ) );

		self::assertStringContainsString(
			'<i class="dashicons dashicons-admin-users"></i>',
			$this->search_field->icon_html()
		);
	}

	/**
	 * Tests the configuration includes default values when set.
	 *
	 * @since 2.42
	 */
	public function test_configuration_with_defaults(): void {
		$field = Search_Field_Created_By::from_configuration( [
			'custom_label' => 'Custom Creator Filter',
		] );

		$config = $field->to_configuration();
		self::assertSame( 'created_by', $config['id'] );
		self::assertSame( 'created_by', $config['type'] );
		self::assertSame( 'Entry Creator', $config['label'] );
		self::assertSame( 'Custom Creator Filter', $config['custom_label'] );
	}

	/**
	 * Tests the template data includes the correct input type and structure.
	 *
	 * @since 2.42
	 */
	public function test_template_data_structure(): void {
		$field = Search_Field_Created_By::from_configuration( [
			'custom_class' => 'creator-filter-class',
			'input_type'   => 'multiselect',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'created_by', $data['type'] );
		self::assertSame( 'multiselect', $data['input'] );
		self::assertSame( 'creator-filter-class', $data['custom_class'] );
		self::assertSame( 'gv_by', $data['name'] );
		self::assertArrayHasKey( 'choices', $data );
	}

	/**
	 * Tests the field returns the correct field type identifier.
	 *
	 * @since 2.42
	 */
	public function test_field_type_identifier(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'created_by', $template_data['type'] );
		self::assertSame( 'input_text', $template_data['input'] );
	}

	/**
	 * Tests the field uses custom input name 'gv_by'.
	 *
	 * @since 2.42
	 */
	public function test_custom_input_name(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'gv_by', $template_data['name'] );
	}

	/**
	 * Tests the field returns the correct default label.
	 *
	 * @since 2.42
	 */
	public function test_default_label(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'Submitted by:', $template_data['label'] );
	}

	/**
	 * Tests custom label overrides default label in template data.
	 *
	 * @since 2.42
	 */
	public function test_custom_label_override(): void {
		$field = Search_Field_Created_By::from_configuration( [
			'custom_label' => 'Filter by Creator',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'Filter by Creator', $data['label'] );
	}

	/**
	 * Tests that choices array structure is correct.
	 *
	 * @since 2.42
	 */
	public function test_choices_structure(): void {
		$data = $this->search_field->to_template_data();

		// Should have choices array.
		self::assertArrayHasKey( 'choices', $data );
		self::assertIsArray( $data['choices'] );
		self::assertNotEmpty( $data['choices'] );

		// Each choice should have text and value keys.
		foreach ( $data['choices'] as $choice ) {
			self::assertArrayHasKey( 'text', $choice );
			self::assertArrayHasKey( 'value', $choice );
			self::assertIsString( $choice['text'] );
			self::assertIsNumeric( $choice['value'] );
		}
	}

	/**
	 * Tests has_request_value returns true when gv_by is set in request.
	 *
	 * @since 2.42
	 */
	public function test_has_request_value(): void {
		// Test with no request values.
		unset( $_REQUEST['gv_by'] );
		self::assertFalse( $this->search_field->has_request_value() );

		// Test with value set.
		$_REQUEST['gv_by'] = '123';
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with array value.
		$_REQUEST['gv_by'] = [ '123', '456' ];
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with zero value.
		$_REQUEST['gv_by'] = '0';
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with empty value.
		$_REQUEST['gv_by'] = '';
		self::assertFalse( $this->search_field->has_request_value() );

		// Clean up.
		unset( $_REQUEST['gv_by'] );
	}

	/**
	 * Tests configuration export and import maintains structure.
	 *
	 * @since 2.42
	 */
	public function test_configuration_maintains_structure(): void {
		$original_config = [
			'id' => 'created_by',
			'custom_label' => 'Creator Filter',
			'custom_class' => 'my-creator-field',
			'show_label' => false,
		];

		$field = Search_Field_Created_By::from_configuration( $original_config );
		$exported_config = $field->to_configuration();

		self::assertSame( 'created_by', $exported_config['id'] );
		self::assertSame( 'created_by', $exported_config['type'] );
		self::assertSame( 'Creator Filter', $exported_config['custom_label'] );
		self::assertSame( 'my-creator-field', $exported_config['custom_class'] );
		self::assertFalse( $exported_config['show_label'] );
	}
}
