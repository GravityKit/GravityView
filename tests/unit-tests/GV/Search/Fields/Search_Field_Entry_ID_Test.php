<?php

use GV\Search\Fields\Search_Field_Entry_ID;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field_Entry_ID}.
 *
 * @since 2.42
 */
final class Search_Field_Entry_ID_Test extends TestCase {
	/**
	 * The search field we're testing.
	 *
	 * @since 2.42
	 */
	private Search_Field_Entry_ID $search_field;

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->search_field = new Search_Field_Entry_ID();
	}

	/**
	 * Tests the basic getters specific to Search_Field_Entry_ID.
	 *
	 * @since 2.42
	 */
	public function test_basic_getters(): void {
		self::assertSame( 'entry_id', $this->search_field->get_type() );
		self::assertSame( 'Search on entry ID', $this->search_field->get_description() );
		self::assertTrue( $this->search_field->is_of_type( 'entry_id' ) );
		self::assertFalse( $this->search_field->is_of_type( 'other_type' ) );

		self::assertStringContainsString(
			'<i class="dashicons dashicons-tag"></i>',
			$this->search_field->icon_html()
		);
	}

	/**
	 * Tests the configuration includes default values when set.
	 *
	 * @since 2.42
	 */
	public function test_configuration_with_defaults(): void {
		$field = Search_Field_Entry_ID::from_configuration( [
			'custom_label' => 'Custom Entry ID Filter',
		] );

		$config = $field->to_configuration();
		self::assertSame( 'entry_id', $config['id'] );
		self::assertSame( 'entry_id', $config['type'] );
		self::assertSame( 'Entry ID', $config['label'] );
		self::assertSame( 'Custom Entry ID Filter', $config['custom_label'] );
	}

	/**
	 * Tests the template data includes the correct input type and structure.
	 *
	 * @since 2.42
	 */
	public function test_template_data_structure(): void {
		$field = Search_Field_Entry_ID::from_configuration( [
			'custom_class' => 'entry-id-filter-class',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'entry_id', $data['type'] );
		self::assertSame( 'gv_id', $data['name'] );
		self::assertSame( 'input_text', $data['input'] );
		self::assertSame( 'entry-id-filter-class', $data['custom_class'] );
		self::assertSame( 'gv_id', $data['name'] );
	}

	/**
	 * Tests the field returns the correct field type identifier.
	 *
	 * @since 2.42
	 */
	public function test_field_type_identifier(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'entry_id', $template_data['type'] );
		self::assertSame( 'input_text', $template_data['input'] );
	}

	/**
	 * Tests the field uses custom input name 'gv_id'.
	 *
	 * @since 2.42
	 */
	public function test_custom_input_name(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'gv_id', $template_data['name'] );
	}

	/**
	 * Tests the field returns the correct default label.
	 *
	 * @since 2.42
	 */
	public function test_default_label(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'Entry ID:', $template_data['label'] );
	}

	/**
	 * Tests custom label overrides default label in template data.
	 *
	 * @since 2.42
	 */
	public function test_custom_label_override(): void {
		$field = Search_Field_Entry_ID::from_configuration( [
			'custom_label' => 'Search by Entry ID',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'Search by Entry ID', $data['label'] );
	}

	/**
	 * Tests has_request_value returns true when gv_id is set in request.
	 *
	 * @since 2.42
	 */
	public function test_has_request_value(): void {
		// Test with no request values.
		unset( $_REQUEST['gv_id'] );
		self::assertFalse( $this->search_field->has_request_value() );

		// Test with value set.
		$_REQUEST['gv_id'] = '123';
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with zero value.
		$_REQUEST['gv_id'] = '0';
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with empty value.
		$_REQUEST['gv_id'] = '';
		self::assertFalse( $this->search_field->has_request_value() );

		// Clean up.
		unset( $_REQUEST['gv_id'] );
	}

	/**
	 * Tests configuration export and import maintains structure.
	 *
	 * @since 2.42
	 */
	public function test_configuration_maintains_structure(): void {
		$original_config = [
			'id' => 'entry_id',
			'custom_label' => 'Entry ID Search',
			'custom_class' => 'my-entry-id-field',
			'show_label' => false,
		];

		$field = Search_Field_Entry_ID::from_configuration( $original_config );
		$exported_config = $field->to_configuration();

		self::assertSame( 'entry_id', $exported_config['id'] );
		self::assertSame( 'entry_id', $exported_config['type'] );
		self::assertSame( 'Entry ID Search', $exported_config['custom_label'] );
		self::assertSame( 'my-entry-id-field', $exported_config['custom_class'] );
		self::assertFalse( $exported_config['show_label'] );
	}
}
