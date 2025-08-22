<?php
/**
 * @file class-gravityview-field-sequence-test.php
 *
 * @internal This file may change in the next versions, so it's not recommended to use its code as a reference.
 *
 * @since TODO
 *
 * @package GravityView
 * @subpackage tests
 * @license GPL2+
 * @author GravityKit <hello@gravitykit.com>
 * @link https://www.gravitykit.com
 * @copyright Copyright 2025, Katz Web Services, Inc.
 */
defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * Test the GravityView_Field_Sequence class.
 *
 * @group fields
 * @group sequence
 *
 * @since TODO
 */
class GravityView_Field_Sequence_Test extends GV_UnitTestCase {

	/**
	 * @var \GravityView_Field_Sequence $sequence_field
	 */
	protected $sequence_field;

	/**
	 * @since TODO
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sequence_field = GravityView_Fields::get_instance( 'sequence' );
	}

	/**
	 * Test basic sequence functionality with manual property setting.
	 *
	 * This is the original test from GravityView_Field_Test.php.
	 *
	 * @covers GravityView_Field_Sequence::get_sequence
	 */
	public function test_sequence_basic_functionality() {
		$form = $this->factory->form->import_and_get( 'simple.json' );
		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 3,
				'show_only_approved' => 0,
			],
			'fields' => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'sequence',
					],
				],
			],
		] );
		$view = \GV\View::from_post( $post );

		$field = \GV\Internal_Field::by_id( 'sequence' );

		$entry_0 = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
		] ) );

		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry_0,
			'field' => $field,
		] );

		// Default behavior - starts at 1 (after our fix)
		$this->assertEquals( 1, $field->field->get_sequence( $context ) );
		$this->assertEquals( 2, $field->field->get_sequence( $context ) );
		$this->assertEquals( 3, $field->field->get_sequence( $context ) );

		// Test with manually set start value
		$field->UID   = wp_generate_password( 8, false );
		$field->start = 1000;

		$this->assertEquals( 1000, $field->field->get_sequence( $context ) );
		$this->assertEquals( 1001, $field->field->get_sequence( $context ) );
		$this->assertEquals( 1002, $field->field->get_sequence( $context ) );

		// Test pagination
		$field->start = 1;
		$field->UID   = wp_generate_password( 8, false );

		$_GET['pagenum'] = 3;

		$this->assertEquals( 7, $field->field->get_sequence( $context ) );
		$this->assertEquals( 8, $field->field->get_sequence( $context ) );
		$this->assertEquals( 9, $field->field->get_sequence( $context ) );

		$field->UID   = wp_generate_password( 8, false );
		$_GET['pagenum'] = 0;

		// Add more entries
		foreach ( range( 1, 10 ) as $_ ) {
			\GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( [
				'form_id' => $form['id'],
				'status' => 'active',
			] ) );
		}

		// Test reverse
		$field->reverse = true;

		$this->assertEquals( 11, $field->field->get_sequence( $context ) );
		$this->assertEquals( 10, $field->field->get_sequence( $context ) );
		$this->assertEquals(  9, $field->field->get_sequence( $context ) );

		$field->UID   = wp_generate_password( 8, false );
		$_GET['pagenum'] = 3;

		$this->assertEquals( 5, $field->field->get_sequence( $context ) );
		$this->assertEquals( 4, $field->field->get_sequence( $context ) );
		$this->assertEquals( 3, $field->field->get_sequence( $context ) );

		$_GET = 0;

		// Test reverse with custom start
		$field->UID   = wp_generate_password( 8, false );
		$field->start = 5;

		$this->assertEquals( 15, $field->field->get_sequence( $context ) );
		$this->assertEquals( 14, $field->field->get_sequence( $context ) );
		$this->assertEquals( 13, $field->field->get_sequence( $context ) );
	}

	/**
	 * Test single entry sequence functionality
	 * This is the original test from GravityView_Field_Test.php
	 * @covers GravityView_Field_Sequence::get_sequence
	 */
	public function test_sequence_single_entry() {
		add_filter( 'gk/gravityview/view/entries/cache', '__return_false' );
		add_filter( 'gravityview_use_cache', '__return_false' );

		$form = $this->factory->form->import_and_get( 'simple.json' );
		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 3,
				'show_only_approved' => 0,
			],
			'fields' => [
				'single_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'sequence',
					],
				],
			],
		] );
		$view = \GV\View::from_post( $post );

		$field = \GV\Internal_Field::by_id( 'sequence' );

		$entry_0 = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
		] ) );

		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry_0,
			'field' => $field,
			'request' => new \GV\Mock_Request(),
		] );

		$context->request->returns = ['is_entry' => $entry_0];

		foreach ( range( 1, 10 ) as $_ ) {
			\GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( [
				'form_id' => $form['id'],
				'status' => 'active',
			] ) );
		}

		$this->assertEquals( 11, $field->field->get_sequence( $context ) );

		$field->reverse = true;

		$this->assertEquals( 1, $field->field->get_sequence( $context ) );

		remove_all_filters( 'gk/gravityview/view/entries/cache' );
		remove_all_filters( 'gravityview_use_cache' );
	}

	/**
	 * Test that the field configuration 'start' setting is properly respected
	 * This tests the actual bug that was reported
	 * @covers GravityView_Field_Sequence::get_sequence
	 */
	public function test_start_configuration_from_field_settings() {
		$form = $this->factory->form->import_and_get( 'simple.json' );

		// Test with start = 5
		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 3,
				'show_only_approved' => 0,
			],
			'fields' => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'sequence',
						'start' => '5', // Testing the start configuration
					],
				],
			],
		] );

		$view = \GV\View::from_post( $post );
		$field = $view->fields->by_visible( $view )->first();

		// Create test entries
		$entries = [];
		for ( $i = 0; $i < 3; $i++ ) {
			$entries[] = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( [
				'form_id' => $form['id'],
				'status' => 'active',
			] ) );
		}

		// Test that sequence starts at 5
		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entries[0],
			'field' => $field,
		] );

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 5, $sequence, 'Sequence should start at 5 when configured' );

		// Test second entry increments properly
		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 6, $sequence, 'Second call should increment to 6' );
	}

	/**
	 * Test that negative start numbers work properly
	 * @covers GravityView_Field_Sequence::get_sequence
	 */
	public function test_negative_start_numbers() {
		$form = $this->factory->form->import_and_get( 'simple.json' );

		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 3,
				'show_only_approved' => 0,
			],
			'fields' => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'sequence',
						'start' => '-10', // Testing negative start
					],
				],
			],
		] );

		$view = \GV\View::from_post( $post );
		$field = $view->fields->by_visible( $view )->first();

		$entry = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
		] ) );

		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry,
			'field' => $field,
		] );

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( -10, $sequence, 'Sequence should handle negative start numbers' );

		// Test increment from negative
		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( -9, $sequence, 'Should increment from negative correctly' );
	}

	/**
	 * Test that zero start number works
	 *
	 * @covers GravityView_Field_Sequence::get_sequence
	 */
	public function test_zero_start_number() {
		$form = $this->factory->form->import_and_get( 'simple.json' );

		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 3,
				'show_only_approved' => 0,
			],
			'fields' => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'sequence',
						'start' => '0', // Testing zero start
					],
				],
			],
		] );

		$view = \GV\View::from_post( $post );
		$field = $view->fields->by_visible( $view )->first();

		$entry = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
		] ) );

		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry,
			'field' => $field,
		] );

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 0, $sequence, 'Sequence should handle zero as start number' );

		// Test increment from zero
		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 1, $sequence, 'Should increment from zero correctly' );
	}

	/**
	 * Test reverse sequence with custom start
	 * @covers GravityView_Field_Sequence::get_sequence
	 */
	public function test_reverse_with_custom_start() {
		$form = $this->factory->form->import_and_get( 'simple.json' );

		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 3,
				'show_only_approved' => 0,
			],
			'fields' => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'sequence',
						'start' => '100',
						'reverse' => '1', // Enable reverse
					],
				],
			],
		] );

		$view = \GV\View::from_post( $post );
		$field = $view->fields->by_visible( $view )->first();

		// Create 5 entries
		for ( $i = 0; $i < 5; $i++ ) {
			$this->factory->entry->create_and_get( [
				'form_id' => $form['id'],
				'status' => 'active',
			] );
		}

		$entry = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
		] ) );

		// Flush the cache to ensure we see all entries
		\GV\View::_flush_cache();

		// Force the view to load all entries
		$view = \GV\View::from_post( $post );
		$entries = $view->get_entries();
		// The view has page_size=3, so all() returns only current page (3 entries)
		// But total() should return the total count (6 entries)
		$this->assertCount( 3, $entries->all(), 'Current page should have 3 entries' );
		$this->assertEquals( 6, $entries->total(), 'Total should be 6 entries across all pages' );

		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry,
			'field' => $field,
		] );

		// With 6 total entries, reverse with start=100 should give us 105 for first entry
		// (total=6, position=0, so start + total - 1 = 100 + 6 - 1 = 105)

		// Debug: Check what the view sees
		$all_entries = \GFAPI::get_entries( $form['id'] );
		$this->assertCount( 6, $all_entries, 'Should have 6 entries in the form' );

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 105, $sequence, 'Reverse sequence should respect custom start value' );

		// Next should be 104
		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 104, $sequence, 'Should decrement in reverse mode' );
	}

	/**
	 * Test pagination with custom start values
	 * @covers GravityView_Field_Sequence::get_sequence
	 */
	public function test_pagination_with_custom_start() {
		$form = $this->factory->form->import_and_get( 'simple.json' );

		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 2,
				'show_only_approved' => 0,
			],
			'fields' => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'sequence',
						'start' => '50',
					],
				],
			],
		] );

		$view = \GV\View::from_post( $post );
		$field = $view->fields->by_visible( $view )->first();

		// Create 6 entries
		for ( $i = 0; $i < 6; $i++ ) {
			$this->factory->entry->create_and_get( [
				'form_id' => $form['id'],
				'status' => 'active',
			] );
		}

		$entry = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
		] ) );

		// Test page 1 (should start at 50)
		$_GET['pagenum'] = 1;
		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry,
			'field' => $field,
		] );

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 50, $sequence, 'First page should start at 50' );

		// Test page 2 (should start at 52 with page_size=2)
		$_GET['pagenum'] = 2;
		$field->UID = wp_generate_password( 8, false ); // Force new context
		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry,
			'field' => $field,
		] );

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 52, $sequence, 'Second page should start at 52 (50 + 2)' );

		// Test page 3 (should start at 54)
		$_GET['pagenum'] = 3;
		$field->UID = wp_generate_password( 8, false ); // Force new context
		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry,
			'field' => $field,
		] );

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 54, $sequence, 'Third page should start at 54 (50 + 4)' );

		// Clean up
		unset( $_GET['pagenum'] );
	}

	/**
	 * Test single entry view respects start configuration
	 * @covers GravityView_Field_Sequence::get_sequence
	 */
	public function test_single_entry_with_custom_start() {
		add_filter( 'gk/gravityview/view/entries/cache', '__return_false' );
		add_filter( 'gravityview_use_cache', '__return_false' );

		$form = $this->factory->form->import_and_get( 'simple.json' );

		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 3,
				'show_only_approved' => 0,
			],
			'fields' => [
				'single_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'sequence',
						'start' => '1000',
					],
				],
			],
		] );

		$view = \GV\View::from_post( $post );
		$field = \GV\Internal_Field::by_id( 'sequence' );
		$field->update_configuration( ['start' => '1000'] );

		// Create first entry
		$entry_1 = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
		] ) );

		// Create more entries
		for ( $i = 0; $i < 4; $i++ ) {
			$this->factory->entry->create_and_get( [
				'form_id' => $form['id'],
				'status' => 'active',
			] );
		}

		// Create last entry
		$entry_last = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
		] ) );

		// Flush the cache to ensure we see all entries
		\GV\View::_flush_cache();

		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry_1,
			'field' => $field,
			'request' => new \GV\Mock_Request(),
		] );

		$context->request->returns = ['is_entry' => $entry_1];

		// First entry should be 1000
		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 1000, $sequence, 'First entry in single view should respect start value' );

		// Last entry should be 1005 (6th entry)
		$context->entry = $entry_last;
		$context->request->returns = ['is_entry' => $entry_last];
		$field->UID = wp_generate_password( 8, false ); // Force recalculation

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 1005, $sequence, 'Last entry should be start + position - 1' );

		remove_all_filters( 'gk/gravityview/view/entries/cache' );
		remove_all_filters( 'gravityview_use_cache' );
	}

	/**
	 * Test single entry view with filters respects sequence numbering
	 * @covers GravityView_Field_Sequence::get_sequence
	 */
	public function test_single_entry_with_filters() {
		add_filter( 'gk/gravityview/view/entries/cache', '__return_false' );
		add_filter( 'gravityview_use_cache', '__return_false' );

		$form = $this->factory->form->import_and_get( 'simple.json' );

		// Create entries with different field values for filtering
		$entry_1 = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'Include', // Field 1 value for filtering
		] );

		$entry_2 = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'Exclude', // This will be filtered out
		] );

		$entry_3 = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'Include',
		] );

		$entry_4 = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'Exclude', // This will be filtered out
		] );

		$entry_5 = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'Include',
		] );

		// Create view with filter to only show entries where field 1 = 'Include'
		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 10,
				'show_only_approved' => 0,
				'search_field' => '1',
				'search_value' => 'Include',
				'search_operator' => 'is',
			],
			'fields' => [
				'single_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'sequence',
						'start' => '100',
					],
				],
			],
		] );

		$view = \GV\View::from_post( $post );
		$field = \GV\Internal_Field::by_id( 'sequence' );
		$field->update_configuration( ['start' => '100'] );

		// Flush the cache to ensure we see all entries
		\GV\View::_flush_cache();

		// Test first included entry (entry_1)
		$entry = \GV\GF_Entry::from_entry( $entry_1 );
		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry,
			'field' => $field,
			'request' => new \GV\Mock_Request(),
		] );
		$context->request->returns = ['is_entry' => $entry];

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 100, $sequence, 'First filtered entry should start at 100' );

		// Test third included entry (entry_5)
		$entry = \GV\GF_Entry::from_entry( $entry_5 );
		$context->entry = $entry;
		$context->request->returns = ['is_entry' => $entry];
		$field->UID = wp_generate_password( 8, false ); // Force recalculation

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 102, $sequence, 'Third filtered entry should be 102 (100 + position 2)' );

		remove_all_filters( 'gk/gravityview/view/entries/cache' );
		remove_all_filters( 'gravityview_use_cache' );
	}

	/**
	 * Test single entry view with filters and default sort order
	 * @covers GravityView_Field_Sequence::get_sequence
	 */
	public function test_single_entry_with_filters_default_sort() {
		add_filter( 'gk/gravityview/view/entries/cache', '__return_false' );
		add_filter( 'gravityview_use_cache', '__return_false' );

		$form = $this->factory->form->import_and_get( 'simple.json' );

		// Create entries with different field values for filtering
		$entry_1 = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'Show', // Field 1 value for filtering
		] );

		$entry_2 = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'Hide', // This will be filtered out
		] );

		$entry_3 = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'Show',
		] );

		// Create view with filter but NO custom start (defaults to 1)
		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 10,
				'show_only_approved' => 0,
				'search_field' => '1',
				'search_value' => 'Show',
				'search_operator' => 'is',
			],
			'fields' => [
				'single_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'sequence',
						// No start value - defaults to 1
					],
				],
			],
		] );

		$view = \GV\View::from_post( $post );
		$field = \GV\Internal_Field::by_id( 'sequence' );

		// Flush the cache to ensure we see all entries
		\GV\View::_flush_cache();

		// Test last entry (entry_3) - with default DESC sort, it should be first
		$entry = \GV\GF_Entry::from_entry( $entry_3 );
		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry,
			'field' => $field,
			'request' => new \GV\Mock_Request(),
		] );
		$context->request->returns = ['is_entry' => $entry];

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 1, $sequence, 'With default sort (DESC), newest filtered entry should be 1' );

		// Test first entry (entry_1) - with default DESC sort, it should be last
		$entry = \GV\GF_Entry::from_entry( $entry_1 );
		$context->entry = $entry;
		$context->request->returns = ['is_entry' => $entry];
		$field->UID = wp_generate_password( 8, false ); // Force recalculation

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 2, $sequence, 'With default sort (DESC), oldest filtered entry should be 2' );

		remove_all_filters( 'gk/gravityview/view/entries/cache' );
		remove_all_filters( 'gravityview_use_cache' );
	}

	/**
	 * Test single entry with advanced View filters and search
	 * @covers GravityView_Field_Sequence::get_sequence
	 */
	public function test_single_entry_with_advanced_view_filters() {
		add_filter( 'gk/gravityview/view/entries/cache', '__return_false' );
		add_filter( 'gravityview_use_cache', '__return_false' );

		$form = $this->factory->form->import_and_get( 'simple.json' );

		// Create entries with different statuses and field values
		$approved_1 = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'Product A',
			'is_approved' => '1', // Approved
		] );

		$not_approved = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'Product A',
			'is_approved' => '0', // Not approved - will be filtered out
		] );

		$approved_2 = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'Product B', // Different product - will be filtered out
			'is_approved' => '1',
		] );

		$approved_3 = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'1' => 'Product A',
			'is_approved' => '1', // Approved
		] );

		// Approve the entries that should be visible
		gform_update_meta( $approved_1['id'], 'is_approved', '1' );
		gform_update_meta( $approved_3['id'], 'is_approved', '1' );
		gform_update_meta( $approved_2['id'], 'is_approved', '1' );

		// Create view with multiple filters:
		// - Only approved entries
		// - Only "Product A" entries
		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 10,
				'show_only_approved' => 1, // View filter: only approved
				'search_field' => '1',
				'search_value' => 'Product A', // Search filter
				'search_operator' => 'is',
			],
			'fields' => [
				'single_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'sequence',
						'start' => '500',
					],
				],
			],
		] );

		$view = \GV\View::from_post( $post );
		$field = \GV\Internal_Field::by_id( 'sequence' );
		$field->update_configuration( ['start' => '500'] );

		// Flush the cache
		\GV\View::_flush_cache();

		// Test first approved Product A entry
		$entry = \GV\GF_Entry::from_entry( $approved_1 );
		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry,
			'field' => $field,
			'request' => new \GV\Mock_Request(),
		] );
		$context->request->returns = ['is_entry' => $entry];

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 500, $sequence, 'First entry matching all filters should be 500' );

		// Test second approved Product A entry (approved_3)
		$entry = \GV\GF_Entry::from_entry( $approved_3 );
		$context->entry = $entry;
		$context->request->returns = ['is_entry' => $entry];
		$field->UID = wp_generate_password( 8, false ); // Force recalculation

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 501, $sequence, 'Second entry matching all filters should be 501' );

		// Verify that filtered-out entries would return 0 if we tried to get their sequence
		$entry = \GV\GF_Entry::from_entry( $not_approved );
		$context->entry = $entry;
		$context->request->returns = ['is_entry' => $entry];
		$field->UID = wp_generate_password( 8, false ); // Force recalculation

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 0, $sequence, 'Non-approved entry should not be found in filtered results' );

		remove_all_filters( 'gk/gravityview/view/entries/cache' );
		remove_all_filters( 'gravityview_use_cache' );
	}

	/**
	 * Test that numeric string start values are properly preserved
	 * This specifically tests the fix where is_numeric() is used instead of empty()
	 * @covers GravityView_Field_Sequence::ensure_field_configuration
	 */
	public function test_numeric_string_start_values_preserved() {
		$form = $this->factory->form->import_and_get( 'simple.json' );

		// Test "0" as string (the main bug being fixed)
		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 3,
				'show_only_approved' => 0,
			],
			'fields' => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'sequence',
						'start' => '0', // String "0" should be preserved
					],
				],
			],
		] );

		$view = \GV\View::from_post( $post );
		$field = $view->fields->by_visible( $view )->first();

		$entry = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
		] ) );

		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry,
			'field' => $field,
		] );

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 0, $sequence, 'String "0" should be preserved and not overridden' );

		// Test other numeric strings
		$test_values = ['100', '-50', '999'];
		foreach ( $test_values as $test_value ) {
			$field->UID = wp_generate_password( 8, false );
			$field->start = $test_value;
			
			$expected = (int) $test_value;
			$sequence = $this->sequence_field->get_sequence( $context );
			$this->assertEquals( $expected, $sequence, "Numeric string '$test_value' should be converted to int $expected" );
		}
	}

	/**
	 * Test that invalid start values default to 1
	 * @covers GravityView_Field_Sequence::get_sequence
	 */
	public function test_invalid_start_defaults_to_one() {
		$form = $this->factory->form->import_and_get( 'simple.json' );

		// Test non-numeric start value
		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 3,
				'show_only_approved' => 0,
			],
			'fields' => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'sequence',
						'start' => 'abc', // Invalid non-numeric value
					],
				],
			],
		] );

		$view = \GV\View::from_post( $post );
		$field = $view->fields->by_visible( $view )->first();

		$entry = \GV\GF_Entry::from_entry( $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
		] ) );

		$context = \GV\Template_Context::from_template( [
			'view' => $view,
			'entry' => $entry,
			'field' => $field,
		] );

		$sequence = $this->sequence_field->get_sequence( $context );
		$this->assertEquals( 1, $sequence, 'Invalid start value should default to 1' );
	}

	/**
	 * Reset GravityView context between tests.
	 *
	 * @since 2.7
	 */
	private function _reset_context() {
		\GV\Mocks\Legacy_Context::reset();
		gravityview()->request = new \GV\Frontend_Request();

		global $wp_query, $post;

		$wp_query = new WP_Query();
		$post = null;
		$_GET = [];

		\GV\View::_flush_cache();
	}

	/**
	 * Test merge tag with custom start value
	 * From GravityView_Future_Test.php
	 * @covers GravityView_Field_Sequence::replace_merge_tag
	 */
	public function test_merge_tag_with_custom_start() {
		$this->_reset_context();

		$form = $this->factory->form->import_and_get( 'simple.json' );

		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size' => 25,
				'show_only_approved' => 0,
			],
			'fields' => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'custom',
						'content' => 'Test {sequence start:100} and {sequence start=-5}',
					],
				],
			],
		] );

		$this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
		] );

		$view = \GV\View::from_post( $post );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns = ['is_view' => $view];

		$renderer = new \GV\View_Renderer();
		$out = $renderer->render( $view );

		$this->assertStringContainsString( 'Test 100 and -5', $out, 'Merge tags should respect custom start values including negative' );
	}

	/**
	 * Test comprehensive merge tag functionality
	 * From GravityView_Future_Test.php
	 * @covers GravityView_Field_Sequence::replace_merge_tag
	 */
	public function test_sequence_merge_tag_comprehensive() {
		$this->_reset_context();

		$form = $this->factory->form->import_and_get( 'simple.json' );

		$post = $this->factory->view->create_and_get( [
			'form_id' => $form['id'],
			'template_id' => 'table',
			'settings' => [
				'page_size'  => '25',
				'show_only_approved' => 0,
			],
			'fields' => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id' => 'custom',
						'content' => 'Row {sequence}, yes, {sequence:reverse}, {sequence start:11}, {sequence start:10,reverse} {sequence:reverse,start=10} {sequence:start=10,reverse}',
						'custom_class' => 'class-{sequence}-custom-1',
					],
					wp_generate_password( 4, false ) => [
						'id' => 'custom',
						'content' => 'Another row {sequence}, ha, {sequence start=2}, {sequence:reverse} {sequence reverse}. This will be the field value: {sequence:start:2}.',
						'custom_class' => 'class-{sequence start:11}-custom-2',
					],
					wp_generate_password( 4, false ) => [
						'id' => '2',
						'label' => 'Conflicts w/ `start:2`, Works w/ `start=2`',
						'custom_class' => 'class-{sequence}-field-2',
					],
				],
			],
			'widgets' => [
				'header_top' => [
					wp_generate_password( 4, false ) => [
						'id' => $widget_id = wp_generate_password( 4, false ) . '-widget',
						'content' => 'Widgets are working.',
					],
					wp_generate_password( 4, false ) => [
						'id' => $widget_id,
						'content' => 'But as expected, "{sequence}" is not working.',
					],
				],
			],
		] );

		/** Trigger registration under this ID */
		if ( class_exists( 'GVFutureTest_Widget_Test_Merge_Tag' ) ) {
			new GVFutureTest_Widget_Test_Merge_Tag( 'Widget', $widget_id );
		}

		$this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'2' => '150',
		] );

		$this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'2' => '300',
		] );

		$this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status' => 'active',
			'2' => '450',
		] );

		$this->assertCount( 3, \GFAPI::get_entries( $form['id'] ), 'Not all entries were created properly.' );

		$view = \GV\View::from_post( $post );

		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns = ['is_view' => $view];

		$this->assertEquals( 3, $view->get_entries()->count(), 'View is not returning the entries as expected.' );

		$renderer = new \GV\View_Renderer();

		$out = $renderer->render( $view );

		$this->assertStringContainsString( 'Row 1, yes, 3, 11, 12 12 12', $out );
		$this->assertStringContainsString( 'class-1-custom-1', $out );
		$this->assertStringContainsString( 'Row 2, yes, 2, 12, 11 11 11', $out );
		$this->assertStringContainsString( 'class-2-custom-1', $out );
		$this->assertStringContainsString( 'Row 3, yes, 1, 13, 10 10 10', $out );
		$this->assertStringContainsString( 'class-3-custom-1', $out );
		$this->assertStringContainsString( 'Another row 1, ha, 2, 3 3. This will be the field value: 450.', $out );
		$this->assertStringContainsString( 'class-11-custom-2', $out );
		$this->assertStringContainsString( 'Another row 2, ha, 3, 2 2. This will be the field value: 300.', $out );
		$this->assertStringContainsString( 'class-12-custom-2', $out );
		$this->assertStringContainsString( 'Another row 3, ha, 4, 1 1. This will be the field value: 150.', $out );
		$this->assertStringContainsString( 'class-13-custom-2', $out );
		$this->assertStringContainsString( 'class-3-field-2', $out );

		$this->assertStringContainsString( 'Widgets are working.', $out );
		$this->assertStringContainsString( 'But as expected, "{sequence}" is not working.', $out );
	}
}

/**
 * Widget test helper class for testing merge tag functionality.
 *
 * @since 2.7
 */
class GVFutureTest_Widget_Test_Merge_Tag extends \GV\Widget {
	/**
	 * Render widget frontend with merge tag replacement.
	 *
	 * @param array  $widget_args Widget arguments.
	 * @param string $content     Widget content.
	 * @param mixed  $context     Widget context.
	 *
	 * @return void
	 */
	public function render_frontend( $widget_args, $content = '', $context = '' ) {
		if ( ! $this->pre_render_frontend( $context ) ) {
			return;
		}

		echo \GravityView_Merge_Tags::replace_variables( \GV\Utils::get( $widget_args, 'content' ) );
	}
}