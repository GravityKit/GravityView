<?php

use GV\Search\Fields\Search_Field_Entry_Date;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field_Entry_Date}.
 *
 * @since $ver$
 */
final class Search_Field_Entry_Date_Test extends TestCase {
	/**
	 * The search field we're testing.
	 *
	 * @since $ver$
	 */
	private Search_Field_Entry_Date $search_field;

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->search_field = new Search_Field_Entry_Date();
	}

	/**
	 * Tests the basic getters specific to Search_Field_Entry_Date.
	 *
	 * @since $ver$
	 */
	public function test_basic_getters(): void {
		self::assertSame( 'entry_date', $this->search_field->get_type() );
		self::assertSame( 'Search on entry date within a range', $this->search_field->get_description() );
		self::assertTrue( $this->search_field->is_of_type( 'entry_date' ) );
		self::assertFalse( $this->search_field->is_of_type( 'other_type' ) );

		self::assertStringContainsString(
			'<i class="dashicons dashicons-calendar-alt"></i>',
			$this->search_field->icon_html()
		);
	}

	/**
	 * Tests the configuration includes default values when set.
	 *
	 * @since $ver$
	 */
	public function test_configuration_with_defaults(): void {
		$field = Search_Field_Entry_Date::from_configuration( [
			'custom_label' => 'Custom Date Filter',
		] );

		$config = $field->to_configuration();
		self::assertSame( 'entry_date', $config['id'] );
		self::assertSame( 'entry_date', $config['type'] );
		self::assertSame( 'Entry Date', $config['label'] );
		self::assertSame( 'Custom Date Filter', $config['custom_label'] );
	}

	/**
	 * Tests the template data includes the correct input type and structure.
	 *
	 * @since $ver$
	 */
	public function test_template_data_structure(): void {
		$field = Search_Field_Entry_Date::from_configuration( [
			'custom_class' => 'date-filter-class',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'entry_date', $data['type'] );
		self::assertSame( 'entry_date', $data['input'] );
		self::assertSame( 'date-filter-class', $data['custom_class'] );
		self::assertIsArray( $data['value'] );
		self::assertArrayHasKey( 'start', $data['value'] );
		self::assertArrayHasKey( 'end', $data['value'] );
	}

	/**
	 * Tests template data value structure for date range.
	 *
	 * @since $ver$
	 */
	public function test_template_data_date_range_value(): void {
		// Test with no request values.
		$data = $this->search_field->to_template_data();
		self::assertSame( '', $data['value']['start'] );
		self::assertSame( '', $data['value']['end'] );

		// Test with request values.
		$_REQUEST['gv_start'] = '2023-01-01';
		$_REQUEST['gv_end'] = '2023-12-31';

		$data = $this->search_field->to_template_data();
		self::assertSame( '2023-01-01', $data['value']['start'] );
		self::assertSame( '2023-12-31', $data['value']['end'] );

		// Clean up.
		unset( $_REQUEST['gv_start'], $_REQUEST['gv_end'] );
	}

	/**
	 * Tests has_request_value returns true when gv_start or gv_end is set in request.
	 *
	 * @since $ver$
	 */
	public function test_has_request_value(): void {
		// Test with no request values.
		unset( $_REQUEST['gv_start'], $_REQUEST['gv_end'] );
		self::assertFalse( $this->search_field->has_request_value() );

		// Test with only start date.
		$_REQUEST['gv_start'] = '2023-01-01';
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with empty start date.
		$_REQUEST['gv_start'] = '';
		self::assertFalse( $this->search_field->has_request_value() );

		// Test with only end date.
		unset( $_REQUEST['gv_start'] );
		$_REQUEST['gv_end'] = '2023-12-31';
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with empty end date.
		$_REQUEST['gv_end'] = '';
		self::assertFalse( $this->search_field->has_request_value() );

		// Test with both dates.
		$_REQUEST['gv_start'] = '2023-01-01';
		$_REQUEST['gv_end'] = '2023-12-31';
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with zero values.
		$_REQUEST['gv_start'] = '0';
		$_REQUEST['gv_end'] = '0';
		self::assertTrue( $this->search_field->has_request_value() );

		// Clean up.
		unset( $_REQUEST['gv_start'], $_REQUEST['gv_end'] );
	}

	/**
	 * Tests the field returns the correct field type identifier.
	 *
	 * @since $ver$
	 */
	public function test_field_type_identifier(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'entry_date', $template_data['type'] );
		self::assertSame( 'entry_date', $template_data['input'] );
	}

	/**
	 * Tests the field returns the correct default label.
	 *
	 * @since $ver$
	 */
	public function test_default_label(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'Filter by date:', $template_data['label'] );
	}

	/**
	 * Tests custom label overrides default label in template data.
	 *
	 * @since $ver$
	 */
	public function test_custom_label_override(): void {
		$field = Search_Field_Entry_Date::from_configuration( [
			'custom_label' => 'Choose Date Range',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'Choose Date Range', $data['label'] );
	}

	/**
	 * Tests the field handles partial date range values correctly.
	 *
	 * @since $ver$
	 */
	public function test_partial_date_range_handling(): void {
		// Test with only start date in request.
		$_REQUEST['gv_start'] = '2023-06-15';
		unset( $_REQUEST['gv_end'] );

		$data = $this->search_field->to_template_data();
		self::assertSame( '2023-06-15', $data['value']['start'] );
		self::assertSame( '', $data['value']['end'] );
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with only end date in request.
		unset( $_REQUEST['gv_start'] );
		$_REQUEST['gv_end'] = '2023-06-30';

		$data = $this->search_field->to_template_data();
		self::assertSame( '', $data['value']['start'] );
		self::assertSame( '2023-06-30', $data['value']['end'] );
		self::assertTrue( $this->search_field->has_request_value() );

		// Clean up.
		unset( $_REQUEST['gv_start'], $_REQUEST['gv_end'] );
	}

	/**
	 * Tests configuration export and import maintains date range structure.
	 *
	 * @since $ver$
	 */
	public function test_configuration_maintains_structure(): void {
		$original_config = [
			'id' => 'entry_date',
			'custom_label' => 'Date Selection',
			'custom_class' => 'my-date-field',
			'show_label' => false,
		];

		$field = Search_Field_Entry_Date::from_configuration( $original_config );
		$exported_config = $field->to_configuration();

		self::assertSame( 'entry_date', $exported_config['id'] );
		self::assertSame( 'entry_date', $exported_config['type'] );
		self::assertSame( 'Date Selection', $exported_config['custom_label'] );
		self::assertSame( 'my-date-field', $exported_config['custom_class'] );
		self::assertFalse( $exported_config['show_label'] );
	}
}
