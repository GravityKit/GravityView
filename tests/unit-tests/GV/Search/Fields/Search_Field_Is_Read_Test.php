<?php

use GV\Search\Fields\Search_Field_Is_Read;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field_Is_Read}.
 *
 * @since 2.42
 */
final class Search_Field_Is_Read_Test extends TestCase {
	/**
	 * The search field we're testing.
	 *
	 * @since 2.42
	 */
	private Search_Field_Is_Read $search_field;

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->search_field = new Search_Field_Is_Read();
	}

	/**
	 * Tests the basic getters specific to Search_Field_Is_Read.
	 *
	 * @since 2.42
	 */
	public function test_basic_getters(): void {
		self::assertSame( 'is_read', $this->search_field->get_type() );
		self::assertSame( 'Filter on read entries', $this->search_field->get_description() );
		self::assertTrue( $this->search_field->is_of_type( 'is_read' ) );
		self::assertFalse( $this->search_field->is_of_type( 'other_type' ) );

		self::assertStringContainsString(
			'<i class="dashicons dashicons-visibility"></i>',
			$this->search_field->icon_html()
		);
	}

	/**
	 * Tests the configuration includes default values when set.
	 *
	 * @since 2.42
	 */
	public function test_configuration_with_defaults(): void {
		$field = Search_Field_Is_Read::from_configuration( [
			'custom_label' => 'Custom Read Filter',
		] );

		$config = $field->to_configuration();
		self::assertSame( 'is_read', $config['id'] );
		self::assertSame( 'is_read', $config['type'] );
		self::assertSame( 'Is Read', $config['label'] );
		self::assertSame( 'Custom Read Filter', $config['custom_label'] );
	}

	/**
	 * Tests the template data includes the correct input type and structure.
	 *
	 * @since 2.42
	 */
	public function test_template_data_structure(): void {
		$field = Search_Field_Is_Read::from_configuration( [
			'custom_class' => 'read-status-filter',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'is_read', $data['type'] );
		self::assertSame( 'select', $data['input'] );
		self::assertSame( 'read-status-filter', $data['custom_class'] );
		self::assertArrayHasKey( 'choices', $data );
		self::assertCount( 2, $data['choices'] );
	}

	/**
	 * Tests that the choices array has the correct structure and values.
	 *
	 * @since 2.42
	 */
	public function test_choices_structure(): void {
		$data = $this->search_field->to_template_data();

		// Check that we have exactly two choices (Read and Unread)
		self::assertCount( 2, $data['choices'] );

		// First choice should be 'Read' with value '1'
		self::assertSame( 'Read', $data['choices'][0]['text'] );
		self::assertSame( '1', $data['choices'][0]['value'] );

		// Second choice should be 'Unread' with value '0'
		self::assertSame( 'Unread', $data['choices'][1]['text'] );
		self::assertSame( '0', $data['choices'][1]['value'] );
	}

	/**
	 * Tests the field returns the correct field type identifier.
	 *
	 * @since 2.42
	 */
	public function test_field_type_identifier(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'is_read', $template_data['type'] );
		self::assertSame( 'select', $template_data['input'] );
	}

	/**
	 * Tests custom label overrides default label in template data.
	 *
	 * @since 2.42
	 */
	public function test_custom_label_override(): void {
		$field = Search_Field_Is_Read::from_configuration( [
			'custom_label' => 'Filter by Read Status',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'Filter by Read Status', $data['label'] );
	}

	/**
	 * Tests has_request_value returns true when filter_is_read is set in request.
	 *
	 * @since 2.42
	 */
	public function test_has_request_value(): void {
		// Test with no request values.
		unset( $_REQUEST['filter_is_read'] );
		self::assertFalse( $this->search_field->has_request_value() );

		// Test with value set.
		$_REQUEST['filter_is_read'] = '1';
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with zero value.
		$_REQUEST['filter_is_read'] = '0';
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with empty value.
		$_REQUEST['filter_is_read'] = '';
		self::assertFalse( $this->search_field->has_request_value() );

		// Clean up.
		unset( $_REQUEST['filter_is_read'] );
	}

	/**
	 * Tests configuration export and import maintains structure.
	 *
	 * @since 2.42
	 */
	public function test_configuration_maintains_structure(): void {
		$original_config = [
			'id'           => 'is_read',
			'custom_label' => 'Read Status',
			'custom_class' => 'my-read-field',
			'show_label'   => false,
		];

		$field           = Search_Field_Is_Read::from_configuration( $original_config );
		$exported_config = $field->to_configuration();

		self::assertSame( 'is_read', $exported_config['id'] );
		self::assertSame( 'is_read', $exported_config['type'] );
		self::assertSame( 'Read Status', $exported_config['custom_label'] );
		self::assertSame( 'my-read-field', $exported_config['custom_class'] );
		self::assertFalse( $exported_config['show_label'] );
	}
}
