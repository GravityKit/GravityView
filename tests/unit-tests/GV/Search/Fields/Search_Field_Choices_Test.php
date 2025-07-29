<?php

use GV\GF_Form;
use GV\Search\Fields\Search_Field_Choices;
use GV\Search\Search_Field_Collection;
use GV\View;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field_Choices}.
 *
 * @since 2.42
 */
final class Search_Field_Choices_Test extends TestCase {
	/**
	 * The search field we're testing.
	 *
	 * @since 2.42
	 */
	private Search_Field_Choices $search_field;

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->search_field = new class extends Search_Field_Choices {
			protected static string $type       = 'choices_test';

			protected static string $field_type = 'select';

			protected string        $icon       = 'dashicons-test-icon';

			protected function get_name(): string {
				return 'Choices Test Search Field';
			}

			public function get_description(): string {
				return 'Unit test description for choices.';
			}

			protected function get_default_label(): string {
				return 'Choices Test';
			}

			protected function get_choices(): array {
				return [
					[ 'text' => 'Choice 1', 'value' => '1' ],
					[ 'text' => 'Choice 2', 'value' => '2' ],
					[ 'text' => 'Choice 3', 'value' => '3' ],
				];
			}

			protected function get_sieved_values(): array {
				return [ '1', '3' ]; // Only choices 1 and 3 exist in entries.
			}

			protected function is_sievable(): bool {
				return true;
			}
		};
	}

	/**
	 * Tests the basic getters.
	 *
	 * @since 2.42
	 */
	public function test_basic_getters(): void {
		self::assertSame( 'choices_test', $this->search_field->get_type() );
		self::assertSame( 'Unit test description for choices.', $this->search_field->get_description() );
		self::assertSame( 'Choices Test', $this->search_field->get_frontend_label() );

		self::assertTrue( $this->search_field->is_of_type( 'choices_test' ) );
		self::assertFalse( $this->search_field->is_of_type( 'other_type' ) );

		self::assertStringContainsString(
			'<i class="dashicons dashicons-test-icon"></i>',
			$this->search_field->icon_html()
		);
	}

	/**
	 * Tests choices-related functionality through template data.
	 *
	 * @since 2.42
	 */
	public function test_choices_functionality(): void {
		$data = $this->search_field->to_template_data();

		// Test that choices are included in template data.
		self::assertArrayHasKey( 'choices', $data );
		self::assertCount( 3, $data['choices'] );
		self::assertSame( 'Choice 1', $data['choices'][0]['text'] );
		self::assertSame( '1', $data['choices'][0]['value'] );
	}

	/**
	 * Tests sieving functionality through template data.
	 *
	 * @since 2.42
	 */
	public function test_sieving(): void {
		// Sieving requires an active View and Form.
		$view       = new View();
		$view->ID   = 123;
		$view->form = GF_Form::by_id( 1 );

		// Test with sieving disabled.
		$field = $this->search_field::from_configuration( [
			'sieve_choices' => '0',
		], $view );

		// Sieving is done by hooking into the `gravityview_widget_search_filters` filter,
		// which is called on the entire collection during template rendering.
		$collection = Search_Field_Collection::from_configuration( [] );
		$collection->add( $field );
		$data = $collection->to_template_data()[0];

		self::assertSame( 'choices_test', $data['type'] );
		self::assertArrayHasKey( 'choices', $data );
		self::assertCount( 3, $data['choices'] );

		// Test with sieving enabled.
		$field = $this->search_field::from_configuration( [
			'sieve_choices' => '1',
		], $view );

		$collection = Search_Field_Collection::from_configuration( [] );
		$collection->add( $field );
		$data = $collection->to_template_data()[0];

		self::assertCount( 2, $data['choices'] );
		self::assertSame( 'Choice 1', $data['choices'][0]['text'] );
		self::assertSame( 'Choice 3', $data['choices'][1]['text'] );
	}

	/**
	 * Test case for missing {@see Search_Field_Choices::get_sieved_values()} implementation.
	 *
	 * @since 2.42
	 */
	public function test_missing_sieve_choices_implementation(): void {
		$this->expectException( BadMethodCallException::class );
		$this->expectDeprecationMessageMatches( '/Make sure to implement ".+?:get_sieved_values" or disable sieving./' );

		$field = new class extends Search_Field_Choices {

			protected function get_choices(): array {
				return [
					[ 'text' => 'Choice 1', 'value' => '1' ],
					[ 'text' => 'Choice 2', 'value' => '2' ],
					[ 'text' => 'Choice 3', 'value' => '3' ],
				];
			}

			protected function is_sievable(): bool {
				return true;
			}
		};
		// Sieving requires an active View and Form.
		$view       = new View();
		$view->ID   = 123;
		$view->form = GF_Form::by_id( 1 );

		$instance = $field::from_configuration( [
			'sieve_choices' => '1',
		], $view );

		$collection = Search_Field_Collection::from_configuration( [] );
		$collection->add( $instance );
		$collection->to_template_data();
	}

	/**
	 * Tests configuration handling.
	 *
	 * @since 2.42
	 */
	public function test_configuration(): void {
		$config = $this->search_field->to_configuration();
		self::assertSame( 'choices_test', $config['id'] );
		self::assertSame( 'choices_test', $config['type'] );
		self::assertSame( 'Choices Test Search Field', $config['label'] );

		$with_settings = $this->search_field::from_configuration(
			[
				'sieve_choices' => '1',
			]
		)->to_configuration();

		self::assertSame( 'choices_test', $with_settings['id'] );
		self::assertSame( 'choices_test', $with_settings['type'] );
		self::assertSame( '1', $with_settings['sieve_choices'] );
	}
}
