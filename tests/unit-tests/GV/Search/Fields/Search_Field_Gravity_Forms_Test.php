<?php

use GV\GF_Form;
use GV\Search\Fields\Search_Field;
use GV\Search\Fields\Search_Field_Created_By;
use GV\Search\Fields\Search_Field_Gravity_Forms;
use GV\Search\Search_Field_Collection;
use GV\View;

/**
 * Tests {@see Search_Field_Gravity_Forms} functionality and behaviors.
 *
 * @since 2.42
 */
final class Search_Field_Gravity_Forms_Test extends GV_UnitTestCase {
	/**
	 * The field under test.
	 *
	 * @since 2.42
	 */
	private Search_Field_Gravity_Forms $search_field;

	/**
	 * Contains the mocked data.
	 *
	 * @since 2.42
	 */
	private array $mock_form_field;

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function setUp(): void {
		parent::setUp();

		$this->mock_form_field = [
			'id'      => '1',
			'form_id' => 123,
			'label'   => 'Test Field',
			'type'    => 'text',
		];

		$this->search_field = Search_Field_Gravity_Forms::from_field( $this->mock_form_field );
	}

	/**
	 * Tests {@see Search_Field_Gravity_Forms::get_type()}, {@see Search_Field_Gravity_Forms::get_description()}, and
	 * {@see Search_Field_Gravity_Forms::is_of_type()} with basic getter functionality.
	 *
	 * @since 2.42
	 */
	public function test_basic_getters(): void {
		self::assertSame( '123::1', $this->search_field->get_type() );
		self::assertSame( 'Gravity Forms Field', $this->search_field->get_description() );
		self::assertTrue( $this->search_field->is_of_type( '123::1' ) );
		self::assertFalse( $this->search_field->is_of_type( 'other_type' ) );
	}

	/**
	 * Tests {@see Search_Field_Gravity_Forms::from_field()} with empty field data returns null.
	 *
	 * @since 2.42
	 */
	public function test_from_field_with_empty_field(): void {
		$result = Search_Field_Gravity_Forms::from_field( [] );
		self::assertNull( $result );
	}

	/**
	 * Tests {@see Search_Field_Gravity_Forms::from_field()} creates proper instance with valid field data.
	 *
	 * @since 2.42
	 */
	public function test_from_field_with_valid_data(): void {
		$form        = $this->factory->form->import_and_get( 'standard.json' );
		$field       = $form['fields'][0];
		$field_array = [
			'id'      => $field['id'],
			'form_id' => $form['id'],
			'label'   => 'Radio',
			'type'    => 'radio',
		];

		$instance = Search_Field_Gravity_Forms::from_field( $field_array );
		$field_id = Search_Field_Gravity_Forms::generate_field_id( $form['id'], $field['id'] );

		$data   = $instance->to_template_data();
		$config = $instance->to_configuration();

		self::assertSame( $field_id, $instance->get_type() );
		self::assertCount( 3, $data['choices'] );
		self::assertSame( '1', $config['id'] );
		self::assertSame( $field_id, $config['type'] );
		self::assertSame( 'Radio', $config['label'] );
		self::assertArrayHasKey( 'form_field', $config );
	}

	/**
	 * Tests {@see Search_Field_Gravity_Forms::generate_field_id()} creates correctly formatted field ID string.
	 *
	 * @since 2.42
	 */
	public function test_generate_field_id(): void {
		$field_id = Search_Field_Gravity_Forms::generate_field_id( 789, '3.1' );
		self::assertSame( '789::3.1', $field_id );
	}

	/**
	 * Tests {@see Search_Field_Gravity_Forms::to_template_data()} includes correct input name format.
	 *
	 * @since 2.42
	 */
	public function test_template_data_input_name(): void {
		$data = $this->search_field->to_template_data();
		self::assertSame( 'filter_1', $data['name'] );
	}

	/**
	 * Tests {@see Search_Field_Gravity_Forms()} input name formatting with subfield (dot notation).
	 *
	 * @since 2.42
	 */
	public function test_input_name_with_subfield(): void {
		$field_with_subfield = [
			'id'      => '2.3',
			'form_id' => 123,
			'label'   => 'Address - City',
			'type'    => 'address',
		];

		$instance = Search_Field_Gravity_Forms::from_field( $field_with_subfield );
		$data     = $instance->to_template_data();

		self::assertSame( 'filter_2_3', $data['name'] );
	}

	/**
	 * Tests {@see Search_Field_Gravity_Forms::icon_html()} returns correct icons for various field types.
	 *
	 * @since 2.42
	 */
	public function test_field_icon_for_special_types(): void {
		$test_cases = [
			'is_fulfilled'   => 'dashicons-yes-alt',
			'currency'       => 'dashicons-money-alt',
			'payment_amount' => 'dashicons-cart',
			'payment_date'   => 'dashicons-cart',
			'payment_method' => 'dashicons-cart',
			'payment_status' => 'dashicons-cart',
			'geolocation'    => 'dashicons-admin-site',
			'unknown_type'   => 'dashicons-admin-generic',
		];

		foreach ( $test_cases as $field_type => $expected_icon ) {
			$field = [
				'id'      => $field_type,
				'form_id' => 123,
				'label'   => "Test $field_type",
				'type'    => $field_type,
			];

			$instance = Search_Field_Gravity_Forms::from_field( $field );
			self::assertStringContainsString( $expected_icon, $instance->icon_html() );
		}
	}

	/**
	 * Tests {@see Search_Field_Gravity_Forms::has_request_value()} with different request scenarios.
	 *
	 * @since 2.42
	 */
	public function test_has_request_value(): void {
		// Test with no request values.
		unset( $_REQUEST['filter_1'] );
		self::assertFalse( $this->search_field->has_request_value() );

		// Test with value set.
		$_REQUEST['filter_1'] = 'test value';
		self::assertTrue( $this->search_field->has_request_value() );

		// Test with empty value.
		$_REQUEST['filter_1'] = '';
		self::assertFalse( $this->search_field->has_request_value() );

		// Clean up.
		unset( $_REQUEST['filter_1'] );
	}

	/**
	 * Tests {@see Search_Field_Gravity_Forms::to_legacy_format()} includes form_id when available.
	 *
	 * @since 2.42
	 */
	public function test_to_legacy_format_includes_form_id(): void {
		$legacy_data = $this->search_field->to_legacy_format();

		self::assertArrayHasKey( 'field', $legacy_data );
		self::assertArrayHasKey( 'input', $legacy_data );
		self::assertArrayHasKey( 'title', $legacy_data );
		self::assertSame( '1', $legacy_data['field'] );
	}

	/**
	 * Tests {@see Search_Field_Gravity_Forms()} choices handling for various choice-based fields.
	 *
	 * @since 2.42
	 */
	public function test_choices_handling(): void {
		$term = wp_insert_term(
			'Test Category',
			'category',
			[
				'slug'   => 'test-category',
				'parent' => 0,
			],
		);

		$form = $this->factory->form->import_and_get( 'complete.json' );
		$user = $this->factory->user->create_and_get(
			[
				'user_login' => 'author',
				'role'       => 'author',
			]
		);
		$this->factory->user->create(
			[
				'user_login' => 'author_2',
				'role'       => 'author',
			]
		);

		$post_id = $this->factory->post->create();
		$entry   = $this->factory->entry->create_and_get(
			[
				'post_id'        => $post_id,
				'status'         => 'active',
				'form_id'        => $form['id'],
				23               => 'Test Category:' . $term['term_id'],
				35               => json_encode( [ 'Second Choice', 'Third Choice' ] ),
				'created_by'     => $user->ID,
				'payment_status' => 'Failed',
			]
		);

		$collection = Search_Field_Collection::available_fields( $form['id'] );

		$configuration = [
			'payment_status' => 'Authorized',
			13               => 'Prefer Not to Answer',
			15               => 'Neutral',
			23               => 'Uncategorized',
			26               => 'Once a week',
			28               => 'Second Option',
			35               => 'Third Choice',
		];

		foreach ( $configuration as $field_type => $contains_value ) {
			$field = $collection
				->by_type( Search_Field_Gravity_Forms::generate_field_id( $form['id'], $field_type ) )
				->first();
			self::assertInstanceOf( Search_Field::class, $field, "Failed to find field $field_type" );;

			$sieved_data = $field->to_template_data();
			self::assertContains( $contains_value, array_column( $sieved_data['choices'], 'text' ) );
		}

		// Sieving requires an active View and Form.
		$view       = new View();
		$view->ID   = 123;
		$view->form = GF_Form::by_id( $form['id'] );

		$sieve = Search_Field_Collection::from_configuration( [] );
		$sieve->add(
			$post_category_23 = Search_Field::from_configuration(
				[
					'id'            => Search_Field_Gravity_Forms::generate_field_id( $form['id'], '23' ),
					'form_id'       => $form['id'],
					'sieve_choices' => true,
				],
				$view,
			),
			$multiselect_35 = Search_Field::from_configuration(
				[
					'id'            => Search_Field_Gravity_Forms::generate_field_id( $form['id'], '35' ),
					'form_id'       => $form['id'],
					'sieve_choices' => true,
				],
				$view
			),
			$payment_status = Search_Field::from_configuration(
				[
					'id'            => Search_Field_Gravity_Forms::generate_field_id( $form['id'], 'payment_status' ),
					'form_id'       => $form['id'],
					'sieve_choices' => true,
				],
				$view
			),
			$created_by = Search_Field_Created_By::from_configuration(
				[
					'form_id'       => $form['id'],
					'sieve_choices' => true,
				],
				$view
			),
		);

		$reduce = static function ( $carry, $item ): array {
			$carry[ $item['key'] ] = array_column( $item['choices'], 'text' );

			return $carry;
		};

		$unsieved_data = array_reduce(
			[
				$post_category_23->to_template_data(),
				$multiselect_35->to_template_data(),
				$payment_status->to_template_data(),
				$created_by->to_template_data(),
			],
			$reduce,
			[]
		);

		self::assertContains( 'Uncategorized', $unsieved_data['23'] );
		self::assertNotCount( 1, $unsieved_data['35'] );
		self::assertContains( 'First Choice', $unsieved_data['35'] );
		self::assertNotCount( 1, $unsieved_data['payment_status'] );
		self::assertContains( 'author', $unsieved_data['created_by'] );
		self::assertContains( 'author_2', $unsieved_data['created_by'] );

		$sieved_data = array_reduce(
			$sieve->to_template_data(),
			$reduce,
			[]
		);

		self::assertSame(
			[
				'23'             => [ 'Test Category' ],
				// 35 is a multiselect, which contains JSON-encoded values.
				'35'             => [ 'Second Choice', 'Third Choice' ],
				'payment_status' => [ 'Failed' ],
				'created_by'     => [ 'author' ],
			],
			$sieved_data,
		);
	}
}
