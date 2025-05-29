<?php

use GV\Search\Fields\Search_Field;
use GV\Search\Fields\Search_Field_Submit;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search_Field}.
 *
 * @since $ver$
 */
final class Search_Field_Test extends TestCase {
	/**
	 * The search field we're testing.
	 *
	 * @since $ver$
	 */
	private Search_Field $search_field;

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->search_field = new class extends Search_Field {
			protected static string $type       = 'unit_test';

			protected static string $field_type = 'boolean';

			protected string        $icon       = 'dashicons-test-icon';

			protected function get_name(): string {
				return 'Unit Test Search Field';
			}

			/**
			 * @inheritDoc
			 * @since $ver$
			 */
			public function get_description(): string {
				return 'Unit test description.';
			}

			/**
			 * @inheritDoc
			 * @since $ver$
			 */
			protected function get_default_label(): string {
				return 'Unit Test';
			}

			protected function get_options(): array {
				return [
					'custom_option' => [
						'type'  => 'text',
						'label' => 'Custom option',
						'value' => 'Custom value',
					],
				];
			}
		};
	}

	/**
	 * Tests the basic getters.
	 *
	 * @since $ver$
	 */
	public function test_basic_getters(): void {
		self::assertSame( 'unit_test', $this->search_field->get_type() );
		self::assertSame( 'Unit test description.', $this->search_field->get_description() );
		self::assertSame( 'Unit Test', $this->search_field->get_frontend_label() );

		self::assertTrue( $this->search_field->is_of_type( 'unit_test' ) );
		self::assertFalse( $this->search_field->is_of_type( 'other_type' ) );

		self::assertStringContainsString(
			'<i class="dashicons dashicons-test-icon"></i>',
			$this->search_field->icon_html()
		);
	}

	/**
	 * Tests merge_options returns the expected merged array.
	 *
	 * @since $ver$
	 */
	public function test_merge_options(): void {
		$options = [
			'custom_label' => [ 'type' => 'text' ],
		];

		$merged = $this->search_field->merge_options( $options );

		self::assertArrayHasKey( 'custom_label', $merged );
		self::assertArrayHasKey( 'custom_option', $merged );
		self::assertSame( 'Custom option', $merged['custom_option']['label'] );
	}

	/**
	 * Tests to_configuration returns expected keys.
	 *
	 * @since $ver$
	 */
	public function test_to_configuration(): void {
		$config = $this->search_field->to_configuration();
		self::assertSame( 'unit_test', $config['id'] );
		self::assertSame( 'unit_test', $config['type'] );
		self::assertSame( 'Unit Test Search Field', $config['label'] );

		self::assertArrayNotHasKey( 'custom_option', $config );

		$with_settings = $this->search_field::from_configuration(
			[
				'custom_option' => 'Some value',
			]
		)->to_configuration();

		self::assertSame( 'unit_test', $with_settings['id'] );
		self::assertSame( 'unit_test', $with_settings['type'] );
		self::assertSame( 'Some value', $with_settings['custom_option'] );
	}

	/**
	 * Tests to_template_data returns an array with expected keys.
	 *
	 * @since $ver$
	 */
	public function test_to_template_data(): void {
		$data          = $this->search_field->to_template_data();
		$with_settings = $this->search_field::from_configuration(
			[
				'custom_option' => 'Some value',
				'custom_label'  => 'A custom label',
				'type'          => 'ignore me',
				'custom_class'  => 'custom-class',
			]
		)->to_template_data();

		self::assertSame(
			[
				'key'           => 'unit_test',
				'name'          => 'filter_unit_test',
				'label'         => 'Unit Test',
				'value'         => '',
				'type'          => 'unit_test',
				'input'         => 'single_checkbox',
				'custom_class'  => '',
				'custom_option' => null,
			],
			$data
		);

		self::assertSame(
			[
				'key'           => 'unit_test',
				'name'          => 'filter_unit_test',
				'label'         => 'A custom label',
				'value'         => '',
				'type'          => 'unit_test',
				'input'         => 'single_checkbox',
				'custom_class'  => 'custom-class',
				'custom_option' => 'Some value',
			],
			$with_settings,
		);
	}

	/**
	 * Tests to_legacy_format returns the expected structure.
	 *
	 * @since $ver$
	 */
	public function test_to_legacy_format(): void {
		self::assertSame(
			[
				'field' => 'unit_test',
				'input' => 'single_checkbox',
				'title' => 'Unit Test Search Field',
			],
			$this->search_field->to_legacy_format()
		);
	}

	/**
	 * Tests is_visible returns a boolean.
	 *
	 * @since $ver$
	 */
	public function test_is_visible(): void {
		self::assertTrue( $this->search_field->is_visible() );
		add_filter( 'gk/gravityview/search/field/is_visible', '__return_false' );
		self::assertFalse( $this->search_field->is_visible() );
		remove_filter( 'gk/gravityview/search/field/is_visible', '__return_false' );
	}

	/**
	 * Test case for {@see Search_Field::has_request_value()}.
	 *
	 * @since $ver$
	 */
	public function test_has_request_value(): void {
		unset( $_REQUEST['filter_unit_test'] );
		self::assertFalse( $this->search_field->has_request_value() );

		$_REQUEST['filter_unit_test'] = 'Custom value';
		self::assertTrue( $this->search_field->has_request_value() );
	}

	/**
	 * Tests that {@see Search_Field::from_configuration()} works as a factory method.
	 *
	 * @since $ver$
	 */
	public function test_from_configuration_factory(): void {
		$config = [
			'id'  => 'submit',
			'tag' => 'button',
		];

		$instance = Search_Field::from_configuration( $config );

		// Verify it's the correct type.
		self::assertInstanceOf( Search_Field_Submit::class, $instance );
		self::assertSame( 'button', $instance->to_template_data()['tag'] );
	}
}
