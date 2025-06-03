<?php

use GV\Search\Fields\Search_Field_Submit;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field_Submit}.
 *
 * @since $ver$
 */
final class Search_Field_Submit_Test extends TestCase {
	/**
	 * The search field we're testing.
	 *
	 * @since $ver$
	 */
	private Search_Field_Submit $search_field;

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->search_field = new Search_Field_Submit();
	}

	/**
	 * Tests the basic getters specific to Search_Field_Submit.
	 *
	 * @since $ver$
	 */
	public function test_basic_getters(): void {
		self::assertSame( 'submit', $this->search_field->get_type() );
		self::assertSame( 'Button to submit the search', $this->search_field->get_description() );
		self::assertTrue( $this->search_field->is_of_type( 'submit' ) );
		self::assertFalse( $this->search_field->is_of_type( 'other_type' ) );

		self::assertFalse( $this->search_field->is_searchable_field() );

		self::assertStringContainsString(
			'<i class="dashicons dashicons-button"></i>',
			$this->search_field->icon_html()
		);
	}

	/**
	 * Tests the field returns the correct default label.
	 *
	 * @since $ver$
	 */
	public function test_default_label(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'Search', $template_data['label'] );
	}

	/**
	 * Tests the field type and input type are correct.
	 *
	 * @since $ver$
	 */
	public function test_field_and_input_types(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'submit', $template_data['type'] );
		self::assertSame( 'submit', $template_data['input'] );
	}

	/**
	 * Tests the submit field specific options.
	 *
	 * @since $ver$
	 */
	public function test_submit_specific_options(): void {
		$field = Search_Field_Submit::from_configuration( [
			'search_clear' => false,
			'tag'          => 'button',
		] );

		$config = $field->to_configuration();
		self::assertSame( 'submit', $config['id'] );
		self::assertSame( 'submit', $config['type'] );
		self::assertFalse( $config['search_clear'] );
		self::assertSame( 'button', $config['tag'] );
	}

	/**
	 * Tests template data includes submit-specific settings.
	 *
	 * @since $ver$
	 */
	public function test_template_data_with_submit_settings(): void {
		$field = Search_Field_Submit::from_configuration( [
			'search_clear' => true,
			'tag'          => 'button',
			'custom_label' => 'Find Results',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'submit', $data['type'] );
		self::assertSame( 'submit', $data['input'] );
		self::assertSame( 'Find Results', $data['label'] );
		self::assertTrue( $data['search_clear'] );
		self::assertSame( 'button', $data['tag'] );
	}

	/**
	 * Tests that show_label is always true for submit fields.
	 *
	 * @since $ver$
	 */
	public function test_show_label_forced_true(): void {
		$field = Search_Field_Submit::from_configuration( [
			'show_label' => false, // This should be ignored
		] );

		$config = $field->to_configuration();
		// The show_label should be forced to true in options
		self::assertTrue( $config['show_label'] );
	}

	/**
	 * Tests tag option default value and choices.
	 *
	 * @since $ver$
	 */
	public function test_tag_option_defaults(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'input', $template_data['tag'] );

		$field_with_button = Search_Field_Submit::from_configuration( [
			'tag' => 'button',
		] );

		$button_data = $field_with_button->to_template_data();
		self::assertSame( 'button', $button_data['tag'] );
	}

	/**
	 * Tests search_clear option default value.
	 *
	 * @since $ver$
	 */
	public function test_search_clear_option_default(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertTrue( $template_data['search_clear'] );

		$field_no_clear = Search_Field_Submit::from_configuration( [
			'search_clear' => false,
		] );

		$no_clear_data = $field_no_clear->to_template_data();
		self::assertFalse( $no_clear_data['search_clear'] );
	}

	/**
	 * Tests configuration export and import maintains submit-specific structure.
	 *
	 * @since $ver$
	 */
	public function test_configuration_maintains_submit_structure(): void {
		$original_config = [
			'id'           => 'submit',
			'custom_label' => 'Submit Search',
			'search_clear' => false,
			'tag'          => 'button',
			'custom_class' => 'my-submit-button',
		];

		$field           = Search_Field_Submit::from_configuration( $original_config );
		$exported_config = $field->to_configuration();

		self::assertSame( 'submit', $exported_config['id'] );
		self::assertSame( 'submit', $exported_config['type'] );
		self::assertSame( 'Submit Search', $exported_config['custom_label'] );
		self::assertFalse( $exported_config['search_clear'] );
		self::assertSame( 'button', $exported_config['tag'] );
		self::assertSame( 'my-submit-button', $exported_config['custom_class'] );
	}
}
