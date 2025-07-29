<?php

use GV\Search\Fields\Search_Field_Is_Approved;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field_Is_Approved}.
 *
 * @since 2.42
 */
final class Search_Field_Is_Approved_Test extends TestCase {
	/**
	 * The search field we're testing.
	 *
	 * @since 2.42
	 */
	private Search_Field_Is_Approved $search_field;

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->search_field = new Search_Field_Is_Approved();
	}

	/**
	 * Tests the basic getters specific to Search_Field_Is_Approved.
	 *
	 * @since 2.42
	 */
	public function test_basic_getters(): void {
		self::assertSame( 'is_approved', $this->search_field->get_type() );
		self::assertSame( 'Filter on approval status', $this->search_field->get_description() );
		self::assertTrue( $this->search_field->is_of_type( 'is_approved' ) );
		self::assertFalse( $this->search_field->is_of_type( 'other_type' ) );

		self::assertStringContainsString(
			'<i class="dashicons dashicons-yes-alt"></i>',
			$this->search_field->icon_html()
		);
	}

	/**
	 * Tests the configuration includes default values when set.
	 *
	 * @since 2.42
	 */
	public function test_configuration_with_defaults(): void {
		$field = Search_Field_Is_Approved::from_configuration( [
			'custom_label' => 'Custom Approval Filter',
		] );

		$config = $field->to_configuration();
		self::assertSame( 'is_approved', $config['id'] );
		self::assertSame( 'is_approved', $config['type'] );
		self::assertSame( 'Approval Status', $config['label'] );
		self::assertSame( 'Custom Approval Filter', $config['custom_label'] );
	}

	/**
	 * Tests the template data includes the correct input type and structure.
	 *
	 * @since 2.42
	 */
	public function test_template_data_structure(): void {
		$field = Search_Field_Is_Approved::from_configuration( [
			'custom_class' => 'approval-filter-class',
			'input_type'   => 'multiselect',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'filter_is_approved', $data['name'] );
		self::assertSame( 'is_approved', $data['type'] );
		self::assertSame( 'multiselect', $data['input'] );
		self::assertSame( 'approval-filter-class', $data['custom_class'] );
		self::assertArrayHasKey( 'choices', $data );
	}

	/**
	 * Tests that the choices array is populated from GravityView_Entry_Approval_Status.
	 *
	 * @since 2.42
	 */
	public function test_choices_from_approval_status(): void {
		$data = $this->search_field->to_template_data();

		// Should have choices array.
		self::assertArrayHasKey( 'choices', $data );
		self::assertIsArray( $data['choices'] );

		// Each choice should have text and value keys.
		foreach ( $data['choices'] as $choice ) {
			self::assertArrayHasKey( 'text', $choice );
			self::assertArrayHasKey( 'value', $choice );
			self::assertIsString( $choice['text'] );
			self::assertIsString( $choice['value'] );
		}
	}

	/**
	 * Tests the field returns the correct field type identifier.
	 *
	 * @since 2.42
	 */
	public function test_field_type_identifier(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'is_approved', $template_data['type'] );
		self::assertSame( 'select', $template_data['input'] );
	}

	/**
	 * Tests the field returns the correct default label.
	 *
	 * @since 2.42
	 */
	public function test_default_label(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'Approval:', $template_data['label'] );
	}

	/**
	 * Tests custom label overrides default label in template data.
	 *
	 * @since 2.42
	 */
	public function test_custom_label_override(): void {
		$field = Search_Field_Is_Approved::from_configuration( [
			'custom_label' => 'Filter by Approval Status',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'Filter by Approval Status', $data['label'] );
	}

	/**
	 * Tests has_request_value returns true when filter_is_approved is set in request.
	 *
	 * @since 2.42
	 */
	public function test_has_request_value(): void {
		// Test with no request values.
		unset( $_REQUEST['filter_is_approved'] );
		self::assertFalse( $this->search_field->has_request_value() );

		// Test with value set.
		$_REQUEST['filter_is_approved'] = '1';
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with array value.
		$_REQUEST['filter_is_approved'] = [ '1', '2' ];
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with empty value.
		$_REQUEST['filter_is_approved'] = '';
		self::assertFalse( $this->search_field->has_request_value() );

		// Clean up.
		unset( $_REQUEST['filter_is_approved'] );
	}

	/**
	 * Tests configuration export and import maintains structure.
	 *
	 * @since 2.42
	 */
	public function test_configuration_maintains_structure(): void {
		$original_config = [
			'id'           => 'is_approved',
			'custom_label' => 'Approval Status',
			'custom_class' => 'my-approval-field',
			'show_label'   => false,
		];

		$field           = Search_Field_Is_Approved::from_configuration( $original_config );
		$exported_config = $field->to_configuration();

		self::assertSame( 'is_approved', $exported_config['id'] );
		self::assertSame( 'is_approved', $exported_config['type'] );
		self::assertSame( 'Approval Status', $exported_config['custom_label'] );
		self::assertSame( 'my-approval-field', $exported_config['custom_class'] );
		self::assertFalse( $exported_config['show_label'] );
	}
}
