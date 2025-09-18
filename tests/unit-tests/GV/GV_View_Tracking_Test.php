<?php

use GV\Mock_Request;
use GV\View;
use GV\View_Renderer;
use GV\View_Settings;

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * Tests for the View rendering tracking API.
 *
 * @group gv_view_tracking
 * @since TBD
 */
class GV_View_Tracking_Test extends GV_UnitTestCase {
	/**
	 * Resets the rendering stack before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		View::reset_rendering_stack();
	}

	/**
	 * Cleans up after each test.
	 */
	public function tearDown(): void {
		View::reset_rendering_stack();
		parent::tearDown();
	}

	/**
	 * @covers View::push_rendering
	 * @covers View::pop_rendering
	 * @covers View::is_rendering
	 */
	public function test_basic_push_pop_rendering() {
		// Initially, no Views should be rendering.
		$this->assertFalse( View::is_rendering() );
		$this->assertFalse( View::is_rendering( 100 ) );

		// Push a View onto the stack.
		View::push_rendering( 100 );
		$this->assertTrue( View::is_rendering() );
		$this->assertTrue( View::is_rendering( 100 ) );
		$this->assertFalse( View::is_rendering( 200 ) );

		// Push another View.
		View::push_rendering( 200 );
		$this->assertTrue( View::is_rendering() );
		$this->assertTrue( View::is_rendering( 100 ) );
		$this->assertTrue( View::is_rendering( 200 ) );

		// Pop the second View.
		$popped = View::pop_rendering();
		$this->assertEquals( 200, $popped );
		$this->assertTrue( View::is_rendering() );
		$this->assertTrue( View::is_rendering( 100 ) );
		$this->assertFalse( View::is_rendering( 200 ) );

		// Pop the first View.
		$popped = View::pop_rendering();
		$this->assertEquals( 100, $popped );
		$this->assertFalse( View::is_rendering() );
		$this->assertFalse( View::is_rendering( 100 ) );

		// Pop from empty stack should return null.
		$popped = View::pop_rendering();
		$this->assertNull( $popped );
	}

	/**
	 * @covers View::get_current_rendering
	 */
	public function test_get_current_rendering() {
		// No current rendering when stack is empty.
		$this->assertNull( View::get_current_rendering() );

		// Push first View.
		View::push_rendering( 100 );
		$this->assertEquals( 100, View::get_current_rendering() );

		// Push second View - should be current.
		View::push_rendering( 200 );
		$this->assertEquals( 200, View::get_current_rendering() );

		// Push third View.
		View::push_rendering( 300 );
		$this->assertEquals( 300, View::get_current_rendering() );

		// Pop third View - second should be current.
		View::pop_rendering();
		$this->assertEquals( 200, View::get_current_rendering() );

		// Pop second View - first should be current.
		View::pop_rendering();
		$this->assertEquals( 100, View::get_current_rendering() );

		// Pop first View - no current.
		View::pop_rendering();
		$this->assertNull( View::get_current_rendering() );
	}

	/**
	 * @covers View::get_rendering_stack
	 */
	public function test_get_rendering_stack() {
		// Empty stack initially.
		$this->assertEquals( [], View::get_rendering_stack() );

		// Add Views.
		View::push_rendering( 100 );
		$this->assertEquals( [ 100 ], View::get_rendering_stack() );

		View::push_rendering( 200 );
		$this->assertEquals( [ 100, 200 ], View::get_rendering_stack() );

		View::push_rendering( 300 );
		$this->assertEquals( [ 100, 200, 300 ], View::get_rendering_stack() );

		// Remove a View.
		View::pop_rendering();
		$this->assertEquals( [ 100, 200 ], View::get_rendering_stack() );
	}

	/**
	 * @covers View::is_primary_view
	 */
	public function test_is_primary_view() {
		// No primary View when stack is empty.
		$this->assertFalse( View::is_primary_view( 100 ) );

		// First View is primary.
		View::push_rendering( 100 );
		$this->assertTrue( View::is_primary_view( 100 ) );
		$this->assertFalse( View::is_primary_view( 200 ) );

		// Second View is not primary.
		View::push_rendering( 200 );
		$this->assertTrue( View::is_primary_view( 100 ) );
		$this->assertFalse( View::is_primary_view( 200 ) );

		// Third View is not primary.
		View::push_rendering( 300 );
		$this->assertTrue( View::is_primary_view( 100 ) );
		$this->assertFalse( View::is_primary_view( 200 ) );
		$this->assertFalse( View::is_primary_view( 300 ) );
	}

	/**
	 * @covers View::is_embedded_view
	 */
	public function test_is_embedded_view() {
		// No embedded View when stack is empty.
		$this->assertFalse( View::is_embedded_view( 100 ) );

		// First View is not embedded.
		View::push_rendering( 100 );
		$this->assertFalse( View::is_embedded_view( 100 ) );

		// Second View is embedded.
		View::push_rendering( 200 );
		$this->assertFalse( View::is_embedded_view( 100 ) );
		$this->assertTrue( View::is_embedded_view( 200 ) );

		// Third View is also embedded.
		View::push_rendering( 300 );
		$this->assertFalse( View::is_embedded_view( 100 ) );
		$this->assertTrue( View::is_embedded_view( 200 ) );
		$this->assertTrue( View::is_embedded_view( 300 ) );

		// Non-rendering View is not embedded.
		$this->assertFalse( View::is_embedded_view( 400 ) );
	}

	/**
	 * @covers View::get_parent_view
	 */
	public function test_get_parent_view() {
		// No parent when not in stack.
		$this->assertNull( View::get_parent_view( 100 ) );

		// Primary View has no parent.
		View::push_rendering( 100 );
		$this->assertNull( View::get_parent_view( 100 ) );

		// Second View's parent is first View.
		View::push_rendering( 200 );
		$this->assertNull( View::get_parent_view( 100 ) );
		$this->assertEquals( 100, View::get_parent_view( 200 ) );

		// Third View's parent is second View.
		View::push_rendering( 300 );
		$this->assertNull( View::get_parent_view( 100 ) );
		$this->assertEquals( 100, View::get_parent_view( 200 ) );
		$this->assertEquals( 200, View::get_parent_view( 300 ) );

		// Non-rendering View has no parent.
		$this->assertNull( View::get_parent_view( 400 ) );
	}

	/**
	 * @covers View::get_rendering_depth
	 */
	public function test_get_rendering_depth() {
		// False when not in stack.
		$this->assertFalse( View::get_rendering_depth( 100 ) );

		// First View has depth 0.
		View::push_rendering( 100 );
		$this->assertEquals( 0, View::get_rendering_depth( 100 ) );

		// Second View has depth 1.
		View::push_rendering( 200 );
		$this->assertEquals( 0, View::get_rendering_depth( 100 ) );
		$this->assertEquals( 1, View::get_rendering_depth( 200 ) );

		// Third View has depth 2.
		View::push_rendering( 300 );
		$this->assertEquals( 0, View::get_rendering_depth( 100 ) );
		$this->assertEquals( 1, View::get_rendering_depth( 200 ) );
		$this->assertEquals( 2, View::get_rendering_depth( 300 ) );

		// Non-rendering View returns false.
		$this->assertFalse( View::get_rendering_depth( 400 ) );
	}

	/**
	 * @covers View::reset_rendering_stack
	 */
	public function test_reset_rendering_stack() {
		// Add some Views.
		View::push_rendering( 100 );
		View::push_rendering( 200 );
		View::push_rendering( 300 );

		// Verify they're in the stack.
		$this->assertTrue( View::is_rendering( 100 ) );
		$this->assertTrue( View::is_rendering( 200 ) );
		$this->assertTrue( View::is_rendering( 300 ) );
		$this->assertEquals( [ 100, 200, 300 ], View::get_rendering_stack() );

		// Reset the stack.
		View::reset_rendering_stack();

		// Verify stack is empty.
		$this->assertFalse( View::is_rendering() );
		$this->assertFalse( View::is_rendering( 100 ) );
		$this->assertFalse( View::is_rendering( 200 ) );
		$this->assertFalse( View::is_rendering( 300 ) );
		$this->assertEquals( [], View::get_rendering_stack() );
		$this->assertNull( View::get_current_rendering() );
	}

	/**
	 * Tests complex nested rendering scenario.
	 */
	public function test_complex_nested_rendering() {
		// Simulate a complex nested rendering scenario:
		// View 100 renders View 200 which renders View 300.
		// Then View 200 also renders View 400.

		// Start rendering main View.
		View::push_rendering( 100 );
		$this->assertTrue( View::is_primary_view( 100 ) );
		$this->assertFalse( View::is_embedded_view( 100 ) );

		// Main View starts rendering embedded View 200.
		View::push_rendering( 200 );
		$this->assertTrue( View::is_embedded_view( 200 ) );
		$this->assertEquals( 100, View::get_parent_view( 200 ) );

		// View 200 starts rendering embedded View 300.
		View::push_rendering( 300 );
		$this->assertTrue( View::is_embedded_view( 300 ) );
		$this->assertEquals( 200, View::get_parent_view( 300 ) );
		$this->assertEquals( 2, View::get_rendering_depth( 300 ) );

		// View 300 finishes.
		View::pop_rendering();
		$this->assertFalse( View::is_rendering( 300 ) );

		// View 200 now starts rendering View 400.
		View::push_rendering( 400 );
		$this->assertTrue( View::is_embedded_view( 400 ) );
		$this->assertEquals( 200, View::get_parent_view( 400 ) );
		$this->assertEquals( 2, View::get_rendering_depth( 400 ) );

		// Check current state.
		$this->assertEquals( [ 100, 200, 400 ], View::get_rendering_stack() );
		$this->assertEquals( 400, View::get_current_rendering() );

		// Unwind the stack.
		View::pop_rendering(); // 400 finishes.
		$this->assertEquals( 200, View::get_current_rendering() );

		View::pop_rendering(); // 200 finishes.
		$this->assertEquals( 100, View::get_current_rendering() );

		View::pop_rendering(); // 100 finishes.
		$this->assertNull( View::get_current_rendering() );
		$this->assertFalse( View::is_rendering() );
	}

	/**
	 * Test View_Renderer integration with actual rendering
	 * This test verifies that the View_Renderer properly manages the rendering stack
	 */
	public function test_view_renderer_integration() {
		$form = $this->factory->form->import_and_get( 'simple.json' );

		$this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
			'status'  => 'active',
			'1'       => 'Test entry content',
		] );

		$settings                       = View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		$view_post = $this->factory->view->create_and_get( [
			'form_id'     => $form['id'],
			'template_id' => 'table',
			'fields'      => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id'    => '1',
						'label' => 'Text',
					],
				],
			],
			'settings'    => $settings,
		] );

		$view = View::from_post( $view_post );
		$this->assertInstanceOf( View::class, $view );

		// Set up the request.
		gravityview()->request                     = new Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		$renderer = new View_Renderer();

		// Initially, nothing should be rendering.
		$this->assertFalse( View::is_rendering( $view->ID ) );
		$this->assertNull( View::get_current_rendering() );

		// Hook into the render process to verify tracking.
		$tracking_checks = [];
		add_action( 'gravityview/template/before', function ( $context ) use ( &$tracking_checks, $view ) {
			$tracking_checks['before'] = [
				'is_rendering' => View::is_rendering( $view->ID ),
				'current'      => View::get_current_rendering(),
				'is_primary'   => View::is_primary_view( $view->ID ),
				'is_embedded'  => View::is_embedded_view( $view->ID ),
			];
		} );

		add_action( 'gravityview/template/after', function ( $context ) use ( &$tracking_checks, $view ) {
			$tracking_checks['after'] = [
				'is_rendering' => View::is_rendering( $view->ID ),
				'current'      => View::get_current_rendering(),
				'is_primary'   => View::is_primary_view( $view->ID ),
				'is_embedded'  => View::is_embedded_view( $view->ID ),
			];
		} );

		$output = $renderer->render( $view );

		// After rendering, stack should be empty.
		$this->assertFalse( View::is_rendering( $view->ID ) );
		$this->assertNull( View::get_current_rendering() );

		// Verify tracking was correct during render.
		$this->assertArrayHasKey( 'before', $tracking_checks );
		$this->assertTrue( $tracking_checks['before']['is_rendering'] );
		$this->assertEquals( $view->ID, $tracking_checks['before']['current'] );
		$this->assertTrue( $tracking_checks['before']['is_primary'] );
		$this->assertFalse( $tracking_checks['before']['is_embedded'] );

		$this->assertArrayHasKey( 'after', $tracking_checks );
		$this->assertTrue( $tracking_checks['after']['is_rendering'] );
		$this->assertEquals( $view->ID, $tracking_checks['after']['current'] );

		// Verify the View actually rendered.
		$this->assertStringContainsString( 'Test entry content', $output );
	}

	/**
	 * Tests embedded View rendering with tracking.
	 * This tests that Views embedded via [gravityview] shortcode are properly tracked.
	 */
	public function test_embedded_view_rendering_tracking() {
		$form1 = $this->factory->form->import_and_get( 'simple.json' );
		$form2 = $this->factory->form->import_and_get( 'simple.json' );

		$this->factory->entry->create_and_get( [
			'form_id' => $form1['id'],
			'status'  => 'active',
			'1'       => 'Entry in OUTER View form',
		] );

		$this->factory->entry->create_and_get( [
			'form_id' => $form2['id'],
			'status'  => 'active',
			'1'       => 'Entry in INNER View form',
		] );

		$settings                       = View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		// Create the inner View that will be embedded.
		$inner_view_post = $this->factory->view->create_and_get( [
			'form_id'     => $form2['id'],
			'template_id' => 'table',
			'fields'      => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id'    => '1',
						'label' => 'Text from inner View',
					],
				],
			],
			'settings'    => $settings,
		] );

		// Create the outer View with custom content field using [gravityview] shortcode.
		$outer_view_post = $this->factory->view->create_and_get( [
			'form_id'     => $form1['id'],
			'template_id' => 'table',
			'fields'      => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id'    => '1',
						'label' => 'Text from outer View',
					],
					wp_generate_password( 4, false ) => [
						'id'      => 'custom',
						'content' => '[gravityview id="' . $inner_view_post->ID . '"]',
					],
				],
			],
			'settings'    => $settings,
		] );

		$inner_view = View::from_post( $inner_view_post );
		$outer_view = View::from_post( $outer_view_post );

		// Set up the request for directory View (which will render the embedded View via shortcode).
		$request                     = new Mock_Request();
		$request->returns['is_view'] = $outer_view;

		// Track what happens during rendering.
		$render_tracking = [];

		add_action( 'gravityview/template/before', function ( $context ) use ( &$render_tracking ) {
			$view_id                 = $context->view->ID;
			$key                     = 'template_before_' . $view_id;
			$render_tracking[ $key ] = [
				'view_id'      => $view_id,
				'stack'        => View::get_rendering_stack(),
				'is_rendering' => View::is_rendering( $view_id ),
				'is_primary'   => View::is_primary_view( $view_id ),
				'is_embedded'  => View::is_embedded_view( $view_id ),
				'parent'       => View::get_parent_view( $view_id ),
				'depth'        => View::get_rendering_depth( $view_id ),
			];
		} );

		// Render the outer View (which contains the embedded View via [gravityview] shortcode).
		$renderer = new View_Renderer();

		// Initially nothing should be rendering.
		$this->assertFalse( View::is_rendering() );

		$output = $renderer->render( $outer_view, $request );

		// After rendering, stack should be empty.
		$this->assertFalse( View::is_rendering() );
		$this->assertEmpty( View::get_rendering_stack() );

		// Verify the outer View was tracked.
		$outer_key = 'template_before_' . $outer_view->ID;
		$this->assertArrayHasKey( $outer_key, $render_tracking, 'Outer View should have been tracked during rendering' );
		$this->assertTrue( $render_tracking[ $outer_key ]['is_rendering'] );
		$this->assertTrue( $render_tracking[ $outer_key ]['is_primary'] );
		$this->assertFalse( $render_tracking[ $outer_key ]['is_embedded'] );
		$this->assertNull( $render_tracking[ $outer_key ]['parent'] );
		$this->assertEquals( 0, $render_tracking[ $outer_key ]['depth'] );

		// Verify output contains content from outer View.
		$this->assertStringContainsString( 'Entry in OUTER View form', $output, 'Output should contain content from outer View entries' );

		// CRITICAL: Verify that the inner View was actually rendered and tracked.
		$inner_key = 'template_before_' . $inner_view->ID;
		$this->assertArrayHasKey( $inner_key, $render_tracking, 'Inner View should have been tracked during rendering - this means the embedded View was actually rendered' );

		// If inner View was tracked, verify it was tracked correctly as embedded.
		$this->assertTrue( $render_tracking[ $inner_key ]['is_rendering'], 'Inner View should be marked as rendering when tracked' );
		$this->assertFalse( $render_tracking[ $inner_key ]['is_primary'], 'Inner View should not be primary' );
		$this->assertTrue( $render_tracking[ $inner_key ]['is_embedded'], 'Inner View should be marked as embedded' );
		$this->assertEquals( $outer_view->ID, $render_tracking[ $inner_key ]['parent'], 'Inner View parent should be outer View' );
		$this->assertEquals( 1, $render_tracking[ $inner_key ]['depth'], 'Inner View should have depth 1' );

		// The stack should show both Views when inner is rendering.
		$this->assertContains( $outer_view->ID, $render_tracking[ $inner_key ]['stack'], 'Outer View should be in stack when inner View renders' );
		$this->assertContains( $inner_view->ID, $render_tracking[ $inner_key ]['stack'], 'Inner View should be in stack when it renders' );

		// CRITICAL: Verify output contains content from embedded View.
		$this->assertStringContainsString( 'Entry in INNER View form', $output, 'Output should contain content from embedded View entries - this proves the embedded View actually rendered' );
	}

	/**
	 * Tests multiple nested Views (View A contains View B which contains View C)
	 */
	public function test_deeply_nested_view_rendering() {
		$form1 = $this->factory->form->import_and_get( 'simple.json' );
		$form2 = $this->factory->form->import_and_get( 'simple.json' );
		$form3 = $this->factory->form->import_and_get( 'simple.json' );

		$this->factory->entry->create_and_get( [
			'form_id' => $form1['id'],
			'status'  => 'active',
			'1'       => 'Content from VIEW A form',
		] );

		$this->factory->entry->create_and_get( [
			'form_id' => $form2['id'],
			'status'  => 'active',
			'1'       => 'Content from VIEW B form',
		] );

		$this->factory->entry->create_and_get( [
			'form_id' => $form3['id'],
			'status'  => 'active',
			'1'       => 'Content from VIEW C form',
		] );

		$settings                       = View_Settings::defaults();
		$settings['show_only_approved'] = 0;

		// Create View C (innermost).
		$view_c_post = $this->factory->view->create_and_get( [
			'form_id'     => $form3['id'],
			'template_id' => 'table',
			'fields'      => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id'    => '1',
						'label' => 'View C Content',
					],
				],
			],
			'settings'    => $settings,
		] );

		// Create View B (contains View C) using custom content field with shortcode.
		$view_b_post = $this->factory->view->create_and_get( [
			'form_id'     => $form2['id'],
			'template_id' => 'table',
			'fields'      => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id'    => '1',
						'label' => 'View B Content',
					],
					wp_generate_password( 4, false ) => [
						'id'      => 'custom',
						'content' => '[gravityview id="' . $view_c_post->ID . '"]',
					],
				],
			],
			'settings'    => $settings,
		] );

		// Create View A (contains View B) using custom content field with shortcode.
		$view_a_post = $this->factory->view->create_and_get( [
			'form_id'     => $form1['id'],
			'template_id' => 'table',
			'fields'      => [
				'directory_table-columns' => [
					wp_generate_password( 4, false ) => [
						'id'    => '1',
						'label' => 'View A Content',
					],
					wp_generate_password( 4, false ) => [
						'id'      => 'custom',
						'content' => '[gravityview id="' . $view_b_post->ID . '"]',
					],
				],
			],
			'settings'    => $settings,
		] );

		$view_a = View::from_post( $view_a_post );
		$view_b = View::from_post( $view_b_post );
		$view_c = View::from_post( $view_c_post );

		// Track all rendered Views and their rendering details.
		$render_tracking = [];
		$max_depth       = 0;
		$max_stack_size  = 0;

		add_action( 'gravityview/template/before', function ( $context ) use ( &$render_tracking, &$max_depth, &$max_stack_size ) {
			$view_id        = $context->view->ID;
			$stack          = View::get_rendering_stack();
			$max_stack_size = max( $max_stack_size, count( $stack ) );

			$depth = View::get_rendering_depth( $view_id );
			if ( $depth !== false ) {
				$max_depth = max( $max_depth, $depth );
			}

			$render_tracking[ $view_id ] = [
				'view_id'      => $view_id,
				'stack'        => $stack,
				'is_rendering' => View::is_rendering( $view_id ),
				'is_primary'   => View::is_primary_view( $view_id ),
				'is_embedded'  => View::is_embedded_view( $view_id ),
				'parent'       => View::get_parent_view( $view_id ),
				'depth'        => $depth,
			];
		} );

		$request                     = new Mock_Request();
		$request->returns['is_view'] = $view_a;

		// Render View A (which should recursively render B and C).
		$renderer = new View_Renderer();
		$output   = $renderer->render( $view_a, $request );

		// After rendering, stack should be empty.
		$this->assertFalse( View::is_rendering(), 'No Views should be rendering after completion' );
		$this->assertEmpty( View::get_rendering_stack(), 'Rendering stack should be empty after completion' );

		// Verify View A was tracked.
		$this->assertArrayHasKey( $view_a->ID, $render_tracking, 'View A should have been tracked' );
		$this->assertTrue( $render_tracking[ $view_a->ID ]['is_primary'], 'View A should be primary' );
		$this->assertFalse( $render_tracking[ $view_a->ID ]['is_embedded'], 'View A should not be embedded' );
		$this->assertEquals( 0, $render_tracking[ $view_a->ID ]['depth'], 'View A should have depth 0' );

		// Verify View A content is in output.
		$this->assertStringContainsString( 'Content from VIEW A form', $output, 'Output should contain View A content' );

		// CRITICAL: Verify View B was tracked (embedded in A).
		$this->assertArrayHasKey( $view_b->ID, $render_tracking, 'View B should have been tracked - this means B was actually rendered inside A' );
		$this->assertFalse( $render_tracking[ $view_b->ID ]['is_primary'], 'View B should not be primary' );
		$this->assertTrue( $render_tracking[ $view_b->ID ]['is_embedded'], 'View B should be embedded' );
		$this->assertEquals( $view_a->ID, $render_tracking[ $view_b->ID ]['parent'], 'View B parent should be View A' );
		$this->assertEquals( 1, $render_tracking[ $view_b->ID ]['depth'], 'View B should have depth 1' );

		// Verify View B content is in output.
		$this->assertStringContainsString( 'Content from VIEW B form', $output, 'Output should contain View B content - this proves B was actually rendered' );

		// CRITICAL: Verify View C was tracked (embedded in B).
		$this->assertArrayHasKey( $view_c->ID, $render_tracking, 'View C should have been tracked - this means C was actually rendered inside B' );
		$this->assertFalse( $render_tracking[ $view_c->ID ]['is_primary'], 'View C should not be primary' );
		$this->assertTrue( $render_tracking[ $view_c->ID ]['is_embedded'], 'View C should be embedded' );
		$this->assertEquals( $view_b->ID, $render_tracking[ $view_c->ID ]['parent'], 'View C parent should be View B' );
		$this->assertEquals( 2, $render_tracking[ $view_c->ID ]['depth'], 'View C should have depth 2' );

		// Verify View C content is in output.
		$this->assertStringContainsString( 'Content from VIEW C form', $output, 'Output should contain View C content - this proves C was actually rendered' );

		// Verify nesting levels.
		$this->assertEquals( 2, $max_depth, 'Maximum depth should be 2 for three-level nesting (A->B->C)' );
		$this->assertEquals( 3, $max_stack_size, 'Maximum stack size should be 3 when all three Views are rendering simultaneously' );
	}
}
