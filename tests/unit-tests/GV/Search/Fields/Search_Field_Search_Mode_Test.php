<?php

use GV\Search\Fields\Search_Field_Search_Mode;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field_Search_Mode}.
 *
 * @since $ver$
 */
final class Search_Field_Search_Mode_Test extends TestCase {
	/**
	 * The search field we're testing.
	 *
	 * @since $ver$
	 */
	private Search_Field_Search_Mode $search_field;

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->search_field = new Search_Field_Search_Mode();
	}

	/**
	 * Tests the basic getters specific to Search_Field_Search_Mode.
	 *
	 * @since $ver$
	 */
	public function test_basic_getters(): void {
		self::assertSame( 'search_mode', $this->search_field->get_type() );
		self::assertSame( 'Should search results match all search fields, or any?',
			$this->search_field->get_description() );
		self::assertTrue( $this->search_field->is_of_type( 'search_mode' ) );
		self::assertFalse( $this->search_field->is_of_type( 'other_type' ) );

		self::assertFalse( $this->search_field->is_searchable_field() );

		self::assertStringContainsString(
			'<i class="dashicons dashicons-filter"></i>',
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
		self::assertSame( 'Search Mode', $template_data['label'] );
	}

	/**
	 * Tests the field type and input type are correct.
	 *
	 * @since $ver$
	 */
	public function test_field_and_input_types(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'search_mode', $template_data['type'] );
		self::assertSame( 'hidden', $template_data['input'] );
	}

	/**
	 * Tests the search mode field has the correct choices.
	 *
	 * @since $ver$
	 */
	public function test_choices(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertArrayNotHasKey( 'choices', $template_data );

		$radio_mode    = Search_Field_Search_Mode::from_configuration( [
			'input_type' => 'radio',
		] );
		$template_data = $radio_mode->to_template_data();

		self::assertCount( 2, $template_data['choices'] );

		$choices = $template_data['choices'];
		self::assertSame( 'Match Any Fields', $choices[0]['text'] );
		self::assertSame( 'any', $choices[0]['value'] );
		self::assertSame( 'Match All Fields', $choices[1]['text'] );
		self::assertSame( 'all', $choices[1]['value'] );
	}

	/**
	 * Tests the search mode field with custom mode configuration.
	 *
	 * @since $ver$
	 */
	public function test_configuration_with_mode(): void {
		$field = Search_Field_Search_Mode::from_configuration( [
			'mode' => 'all',
		] );

		$config = $field->to_configuration();
		self::assertSame( 'search_mode', $config['id'] );
		self::assertSame( 'search_mode', $config['type'] );
		self::assertSame( 'Search mode', $config['label'] );
		self::assertSame( 'all', $config['mode'] );
	}

	/**
	 * Tests the template data includes the correct mode value.
	 *
	 * @since $ver$
	 */
	public function test_template_data_with_mode(): void {
		$field = Search_Field_Search_Mode::from_configuration( [
			'mode' => 'all',
		] );

		$data = $field->to_template_data();
		self::assertSame( 'search_mode', $data['type'] );
		self::assertSame( 'all', $data['mode'] );
	}

	/**
	 * Tests the default mode value when not specified.
	 *
	 * @since $ver$
	 */
	public function test_default_mode(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'any', $template_data['mode'] );
	}

	/**
	 * Tests the input value with hidden input type returns stored value.
	 *
	 * @since $ver$
	 */
	public function test_hidden_input_returns_stored_value(): void {
		$field = Search_Field_Search_Mode::from_configuration( [
			'mode'       => 'all',
			'input_type' => 'hidden',
		] );

		$template_data = $field->to_template_data();
		self::assertSame( 'all', $template_data['value'] );
	}

	/**
	 * Tests the input value with radio input type uses request value when available.
	 *
	 * @since $ver$
	 */
	public function test_radio_input_uses_request_value(): void {
		$field = Search_Field_Search_Mode::from_configuration( [
			'mode'       => 'any',
			'input_type' => 'radio',
		] );

		// No request value - should use stored value
		unset( $_REQUEST['mode'] );
		$template_data = $field->to_template_data();
		self::assertSame( 'any', $template_data['value'] );

		// With request value - should use request value
		$_REQUEST['mode'] = 'all';
		$template_data    = $field->to_template_data();
		self::assertSame( 'all', $template_data['value'] );

		// Clean up
		unset( $_REQUEST['mode'] );
	}

	/**
	 * Tests has_request_value returns true when mode is set in request.
	 *
	 * @since $ver$
	 */
	public function test_has_request_value(): void {
		$hidden  = Search_Field_Search_Mode::from_configuration( [
			'input_type' => 'hidden',
		] );

		$visible = Search_Field_Search_Mode::from_configuration( [
			'input_type' => 'radio',
			'mode' => 'all',
		] );

		unset( $_REQUEST['mode'] );
		self::assertFalse( $hidden->has_request_value() );
		self::assertFalse( $visible->has_request_value() );

		$_REQUEST['mode'] = 'all';
		self::assertFalse( $hidden->has_request_value() );
		self::assertFalse( $visible->has_request_value() ); // Same as default mode.

		$_REQUEST['mode'] = '';
		self::assertFalse( $hidden->has_request_value() );
		self::assertFalse( $visible->has_request_value() );

		$_REQUEST['mode'] = 'any';
		self::assertFalse( $hidden->has_request_value() );
		self::assertTrue( $visible->has_request_value() );

		unset( $_REQUEST['mode'] );
	}

	/**
	 * Tests the search mode field extends Search_Field_Choices.
	 *
	 * @since $ver$
	 */
	public function test_extends_choices_field(): void {
		self::assertInstanceOf( \GV\Search\Fields\Search_Field_Choices::class, $this->search_field );
	}

	/**
	 * Tests the input name is correct.
	 *
	 * @since $ver$
	 */
	public function test_input_name(): void {
		$template_data = $this->search_field->to_template_data();
		self::assertSame( 'mode', $template_data['name'] );
	}

	/**
	 * Tests the search mode field settings include mode.
	 *
	 * @since $ver$
	 */
	public function test_setting_keys_include_mode(): void {
		$reflection = new ReflectionClass( $this->search_field );
		$method     = $reflection->getMethod( 'setting_keys' );
		$method->setAccessible( true );
		$keys = $method->invoke( $this->search_field );

		self::assertContains( 'mode', $keys );
	}
}
