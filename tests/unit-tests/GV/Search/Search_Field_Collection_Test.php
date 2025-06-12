<?php

use GV\Collection_Position_Aware;
use GV\Grid;
use GV\Search\Fields\Search_Field;
use GV\Search\Fields\Search_Field_All;
use GV\Search\Fields\Search_Field_Gravity_Forms;
use GV\Search\Fields\Search_Field_Submit;
use GV\Search\Search_Field_Collection;
use GV\View;

/**
 * Unit tests for {@see Search_Field_Collection}
 *
 * @since $ver$
 */
final class Search_Field_Collection_Test extends GV_UnitTestCase {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function setUp(): void {
		parent::setUp();
		include_once GRAVITYVIEW_DIR . 'includes/admin/class-gravityview-admin-view-item.php';
	}

	/**
	 * Makes sure the collection is position aware, as it is used in various places.
	 *
	 * @since $ver$
	 */
	public function test_collection_is_position_aware(): void {
		$collection = Search_Field_Collection::from_configuration( [] );
		self::assertInstanceOf( Collection_Position_Aware::class, $collection );
	}

	/**
	 * Test case for {@see Search_Field_Collection::add()} with multiple fields.
	 *
	 * @since $ver$
	 */
	public function test_add_multiple_fields(  ):void {
		$collection = Search_Field_Collection::from_configuration( [] );
		$collection->add(
			new Search_Field_All(),
			new Search_Field_Submit(),
		);

		self::assertCount( 2, $collection );
	}

	/**
	 * Test case for {@see Search_Field_Collection::from_legacy_configuration()}.
	 *
	 * @since $ver$
	 * @throws JsonException When JSON could not be generated.
	 */
	public function test_from_legacy_configuration(): void {
		$form  = $this->factory->form->import_and_get( 'standard.json' );
		$entry = $this->factory->entry->import_and_get( 'simple_entry.json', [
			'form_id' => $form['id'],
			'1'       => 'Second Choice',
		] );

		$view_id = $this->factory->view->create( [ 'form_id' => $form['id'] ] );
		$view    = View::by_id( $view_id );

		$configuration = [
			'search_layout' => 'horizontal',
			'search_clear'  => '0',
			'search_fields' => json_encode(
				[
					[
						'field' => 'search_all',
						'input' => 'input_text',
						'label' => 'Custom label',
					],
					[
						'field' => 'entry_date',
						'input' => 'date_range',
						'label' => 'Entry Date',
					],
					[
						'field' => '1',
						'input' => 'radio',
						'label' => '',
					],
				],
				JSON_THROW_ON_ERROR
			),
			'search_mode'   => 'all',
			'sieve_choices' => '1',
			'id'            => 'search_bar',
			'label'         => 'Search Bar',
			'form_id'       => $form['id'],
		];

		$collection = Search_Field_Collection::from_legacy_configuration( $configuration, $view );
		$rows       = Grid::get_rows_from_collection( $collection, 'search-general' );
		self::assertCount( 2, $rows );
		self::assertCount( 5, $collection ); // 3 fields + Search Button and Search Mode

		$submit      = $collection->by_type( 'submit' )->first();
		$search_mode = $collection->by_type( 'search_mode' )->first();
		$field_id    = Search_Field_Gravity_Forms::generate_field_id( $form['id'], 1 );
		$field       = $collection->by_type( $field_id )->first();

		self::assertSame( 'all', $search_mode->to_template_data()['mode'] );
		self::assertFalse( $submit->to_template_data()['search_clear'] );
		self::assertTrue( $field->to_template_data()['sieve_choices'] );
		self::assertCount( 3, $field->to_template_data()['choices'] );

		self::assertCount( 2, $rows[0] ); // Horizontal has two columns.

		// Field is sieved, so the actual visible choices should be 1: Second Choice.
		$template_data = array_column( $collection->to_template_data(), null, 'type' );
		self::assertCount( 1, $template_data[ $field_id ]['choices'] );
		self::assertSame( 'Second Choice', $template_data[ $field_id ]['choices'][0]['value'] );
	}

	/**
	 * Test case for {@see Search_Field_Collection::from_configuration()} and
	 * {@see Search_Field_Collection::to_configuration()}.
	 *
	 * @since $ver$
	 */
	public function test_from_configuration(): void {
		$collection = Search_Field_Collection::from_configuration( [
			'search_default'  => [
				'asdf' => [ 'id' => 'search_all' ],
			],
			'search_advanced' => [
				'asdf2' => [ 'id' => 'search_all' ],
			],
		] );

		self::assertSame( 2, $collection->count() );
		self::assertSame(
			[
				'search_default'  => [
					'asdf' => [
						'id'         => 'search_all',
						'UID'        => 'asdf',
						'type'       => 'search_all',
						'label'      => 'Search Everything',
						'position'   => 'search_default',
						'show_label' => true,
					],
				],
				'search_advanced' => [
					'asdf2' => [
						'id'         => 'search_all',
						'UID'        => 'asdf2',
						'type'       => 'search_all',
						'label'      => 'Search Everything',
						'position'   => 'search_advanced',
						'show_label' => true,
					],
				],
			],
			$collection->to_configuration()
		);
	}

	/**
	 * Test case for {@see Search_Field_Collection::available_fields()}
	 * and {@see Search_Field_Collection::has_fields_of_type()}.
	 *
	 * @since $ver$
	 */
	public function test_available_fields(): void {
		$provided_form_id = null; // Will receive the provided form ID.

		add_filter(
			'gk/gravityview/search/available-fields',
			$cb = static function ( array $fields, $form_id ) use ( &$provided_form_id, &$cb ): array {
				$provided_form_id = $form_id;

				// Register a custom field.
				$fields[] = new class extends Search_Field {
					protected static string $type = 'custom';
				};

				remove_filter( 'gk/gravityview/search/available-fields', $cb );

				return $fields;
			},
			10,
			2
		);

		$collection = Search_Field_Collection::available_fields( 123 );

		// Should provide the form ID as the second filter param.
		self::assertSame( 123, $provided_form_id );
		// Should not have non-existent fields.
		self::assertFalse( $collection->has_fields_of_type( 'non_existent_type' ) );

		// Should contain at least the basic search fields.
		self::assertTrue( $collection->has_fields_of_type( 'search_all' ) );
		self::assertTrue( $collection->has_fields_of_type( 'search_mode' ) );
		self::assertTrue( $collection->has_fields_of_type( 'submit' ) );
		self::assertTrue( $collection->has_fields_of_type( 'entry_date' ) );
		self::assertTrue( $collection->has_fields_of_type( 'entry_id' ) );
		self::assertTrue( $collection->has_fields_of_type( 'created_by' ) );
		self::assertTrue( $collection->has_fields_of_type( 'is_starred' ) );
		self::assertTrue( $collection->has_fields_of_type( 'is_read' ) );
		// Should contain the custom field.
		self::assertTrue( $collection->has_fields_of_type( 'custom' ) );
	}

	/**
	 * Test case for {@see Search_Field_Collection::get_field_by_field_id()}.
	 *
	 * @since $ver$
	 */
	public function test_get_field_by_field_id(): void {
		// Test getting a search_all field.
		$field = Search_Field_Collection::get_field_by_field_id( 0, 'search_all' );
		self::assertNotNull( $field );
		self::assertSame( 'search_all', $field->get_type() );

		// Test getting a non-existent field.
		$field = Search_Field_Collection::get_field_by_field_id( 0, 'non_existent_field' );
		self::assertNull( $field );
	}

	/**
	 * Test case for {@see Search_Field_Collection::by_type()}.
	 *
	 * @since $ver$
	 */
	public function test_by_type(): void {
		$collection = Search_Field_Collection::from_configuration( [
			'search_default' => [
				'field1' => [ 'id' => 'search_all' ],
				'field2' => [ 'id' => 'entry_date' ],
			],
		] );

		$filtered = $collection->by_type( 'search_all' );

		self::assertNotSame( $filtered, $collection );
		self::assertCount( 1, $filtered );
		self::assertTrue( $filtered->has_fields_of_type( 'search_all' ) );

		self::assertTrue( $collection->has_fields_of_type( 'entry_date' ) );
		self::assertFalse( $filtered->has_fields_of_type( 'entry_date' ) );
	}

	/**
	 * Test case for {@see Search_Field_Collection::has_request_values()}.
	 *
	 * @since $ver$
	 */
	public function test_has_request_values(): void {
		$collection = Search_Field_Collection::from_configuration( [
			'search_default' => [
				'field1' => [ 'id' => 'search_all' ],
			],
		] );

		unset( $_REQUEST['gv_search'] );

		// Initially should be false as no request values are set.
		self::assertFalse( $collection->has_request_values() );

		$_REQUEST['gv_search'] = 'value';

		self::assertTrue( $collection->has_request_values() );

		// Cleanup.
		unset( $_REQUEST['gv_search'] );
	}

	/**
	 * Test case for {@see Search_Field_Collection::to_template_data()} with no visible searchable fields.
	 *
	 * @since $ver$
	 */
	public function test_to_template_data_empty_when_no_visible_searchable_fields(): void {
		// Test collection with only non-searchable fields returns empty.
		$collection = Search_Field_Collection::from_configuration( [
			'search_default' => [
				'submit'      => [ 'id' => 'submit' ],
				'search_mode' => [ 'id' => 'search_mode' ],
			],
		] );

		$template_data = $collection->to_template_data();
		self::assertIsArray( $template_data );
		self::assertEmpty( $template_data );
	}

	/**
	 * Test case for {@see Search_Field_Collection::to_template_data()} with invisible searchable fields.
	 *
	 * @since $ver$
	 */
	public function test_to_template_data_with_invisible_searchable_fields(): void {
		// Test collection with searchable fields returns data.
		$class = new class extends Search_Field {
			protected static string $type = 'custom-hidden';
		};

		$field = $class::from_configuration( [
			'only_loggedin' => true,
		] );

		self::assertFalse( $field->is_visible() );
		$collection = Search_Field_Collection::from_configuration( [] );
		$collection->add( $field );

		$collection = $collection->ensure_required_search_fields();
		self::assertFalse( $collection->has_visible_fields() );
		$this->factory->user->create_and_set();
		self::assertTrue( $collection->has_visible_fields() );

		$by_position = $collection->by_position( 'missing' );
		self::assertFalse( $by_position->has_visible_fields( true ) ); // Strict check on the split off collection.
		self::assertTrue( $by_position->has_visible_fields() ); // parent collection has.
	}

	/**
	 * Test case for {@see Search_Field_Collection::to_template_data()} with visible searchable fields.
	 *
	 * @since $ver$
	 */

	public function test_to_template_data_with_visible_searchable_fields(): void {
		$collection = Search_Field_Collection::from_configuration( [] );
		$collection->add( new Search_Field_All() );
		$collection    = $collection->ensure_required_search_fields();
		$template_data = $collection->to_template_data();

		self::assertIsArray( $template_data );
		self::assertCount( 3, $template_data );

		$rows = Grid::get_rows_from_collection( $collection, 'search-general' );

		$left  = $collection->by_position( 'search-general_' . $rows[0]['1-2 left'][0]['areaid'] );
		$right = $collection->by_position( 'search-general_' . $rows[0]['1-2 right'][0]['areaid'] );
		self::assertSame( 'submit', $left->first()->get_type() );
		self::assertSame( 'search_mode', $right->first()->get_type() );
	}

	/**
	 * Test case for {@see Search_Field_Collection::to_template_data()}.
	 *
	 * @since $ver$
	 */
	public function test_to_template_data(): void {
		$collection = Search_Field_Collection::from_configuration( [
			'search_default' => [
				'field1' => [
					'id'    => 'search_all',
					'label' => 'Custom Search Label',
				],
				'submit' => [ 'id' => 'submit' ],
			],
		] );

		$template_data = $collection->to_template_data();
		self::assertIsArray( $template_data );
		self::assertCount( 2, $template_data );

		// Should contain the search field data.
		$types = array_column( $template_data, 'type' );
		self::assertSame( [ 'search_all', 'submit' ], $types );
	}

	/**
	 * Test case for {@see Search_Field_Collection::ensure_required_search_fields()}.
	 *
	 * @since $ver$
	 */
	public function test_ensure_required_search_fields(): void {
		$collection = Search_Field_Collection::from_configuration( [
			'search_default' => [
				'field1' => [ 'id' => 'search_all' ],
			],
		] );

		$collection = $collection->ensure_required_search_fields( [
			'search_mode'  => 'all',
			'search_clear' => '0',
		] );

		// Should have both search mode and submit fields.
		self::assertTrue( $collection->has_fields_of_type( 'search_mode' ) );
		self::assertTrue( $collection->has_fields_of_type( 'submit' ) );

		// Get the search mode field and verify configuration.
		$search_mode = $collection->by_type( 'search_mode' )->first();
		self::assertSame( 'all', $search_mode->to_template_data()['mode'] );

		// Get the submit field and verify configuration.
		$submit = $collection->by_type( 'submit' )->first();
		self::assertFalse( $submit->to_template_data()['search_clear'] );
	}

	/**
	 * Test case for {@see Search_Field_Collection::has_date_field()}.
	 *
	 * @since $ver$
	 */
	public function test_has_date_field(): void {
		// Test collection without date fields.
		$collection = Search_Field_Collection::from_configuration( [
			'search_default' => [
				'field1' => [ 'id' => 'search_all' ],
				'field2' => [ 'id' => 'entry_id' ],
				'field3' => [ 'id' => 'submit' ],
			],
		] );

		self::assertFalse( $collection->has_date_field() );

		// Test with entry_date field (which has input type 'entry_date').
		$collection_with_entry_date = Search_Field_Collection::from_configuration( [
			'search_default' => [
				'field1' => [ 'id' => 'entry_date' ],
			],
		] );

		self::assertTrue( $collection_with_entry_date->has_date_field() );

		// Test with mixed fields including a date field.
		$mixed_collection = Search_Field_Collection::from_configuration( [
			'search_default' => [
				'field1' => [ 'id' => 'search_all' ],
				'field2' => [ 'id' => 'entry_date' ],
				'field3' => [ 'id' => 'submit' ],
			],
		] );

		self::assertTrue( $mixed_collection->has_date_field() );

		$legacy_config = [
			'search_fields' => json_encode( [
				[
					'field' => 'entry_date',
					'input' => 'date_range',
					'label' => 'Entry Date Range',
				],
			], JSON_THROW_ON_ERROR ),
		];

		$legacy_collection = Search_Field_Collection::from_legacy_configuration( $legacy_config, null );
		self::assertTrue( $legacy_collection->has_date_field() );

		// Test with only non-date legacy fields.
		$non_date_legacy_config = [
			'search_fields' => json_encode( [
				[
					'field' => 'search_all',
					'input' => 'input_text',
					'label' => 'Search All',
				],
			], JSON_THROW_ON_ERROR ),
		];

		$non_date_legacy_collection = Search_Field_Collection::from_legacy_configuration( $non_date_legacy_config,
			null );
		self::assertFalse( $non_date_legacy_collection->has_date_field() );
	}
}
