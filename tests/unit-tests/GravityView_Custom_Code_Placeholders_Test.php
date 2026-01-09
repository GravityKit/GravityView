<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * Tests the custom code placeholder replacement functionality.
 *
 * Placeholders:
 * - `VIEW_ID`: Replaced with the View ID.
 * - `GF_FORM_ID`: Replaced with the Gravity Forms form ID.
 * - `VIEW_SELECTOR`: Replaced with `.gv-container.gv-container-{view_id}` for high-specificity CSS.
 *
 * @group frontend
 * @group placeholders
 * @since 2.50.0
 */
class GravityView_Custom_Code_Placeholders_Test extends GV_UnitTestCase {

	/**
	 * @var ReflectionMethod The reflected private method for direct testing.
	 */
	private $replace_code_placeholders_method;

	/**
	 * Set up the test environment.
	 */
	function setUp(): void {
		parent::setUp();

		// Use reflection to access the private method for direct testing.
		$reflection = new ReflectionClass( GravityView_frontend::class );
		$this->replace_code_placeholders_method = $reflection->getMethod( 'replace_code_placeholders' );
		$this->replace_code_placeholders_method->setAccessible( true );
	}

	/**
	 * Helper method to call the private replace_code_placeholders method.
	 *
	 * @param string   $content The content with placeholders.
	 * @param \GV\View $view    The View object.
	 *
	 * @return string The content with placeholders replaced.
	 */
	private function replace_placeholders( $content, $view ) {
		return $this->replace_code_placeholders_method->invoke(
			GravityView_frontend::getInstance(),
			$content,
			$view
		);
	}

	/**
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_view_id_placeholder_replacement() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		$content = 'View ID is VIEW_ID';
		$result = $this->replace_placeholders( $content, $view );

		$this->assertEquals( "View ID is {$view->ID}", $result );
	}

	/**
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_gf_form_id_placeholder_replacement() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		$content = 'Form ID is GF_FORM_ID';
		$result = $this->replace_placeholders( $content, $view );

		$this->assertEquals( "Form ID is {$form['id']}", $result );
	}

	/**
	 * Test VIEW_SELECTOR placeholder replacement.
	 *
	 * VIEW_SELECTOR is replaced with `.gv-container.gv-container-{view_id}` for
	 * high-specificity CSS targeting without needing !important.
	 *
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_view_id_selector_placeholder_replacement() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		$content = 'Selector is VIEW_SELECTOR';
		$result = $this->replace_placeholders( $content, $view );

		$expected_selector = ".gv-container.gv-container-{$view->ID}";
		$this->assertEquals( "Selector is {$expected_selector}", $result );
	}

	/**
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_all_placeholders_together() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		$content = <<<CSS
.my-view-VIEW_ID {
    /* Form: GF_FORM_ID */
}
VIEW_SELECTOR .entry {
    color: red;
}
CSS;

		$result = $this->replace_placeholders( $content, $view );

		$this->assertStringContainsString( ".my-view-{$view->ID}", $result );
		$this->assertStringContainsString( "/* Form: {$form['id']} */", $result );
		$this->assertStringContainsString( ".gv-container.gv-container-{$view->ID} .entry", $result );
	}

	/**
	 * Test case sensitivity - lowercase placeholders should NOT be replaced.
	 *
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_case_sensitivity() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		// Lowercase should not be replaced.
		$content = 'view_id gf_form_id view_id_selector';
		$result = $this->replace_placeholders( $content, $view );

		$this->assertEquals( $content, $result, 'Lowercase placeholders should not be replaced' );

		// Mixed case should not be replaced.
		$content = 'View_Id GF_Form_ID View_Id_Selector';
		$result = $this->replace_placeholders( $content, $view );

		$this->assertEquals( $content, $result, 'Mixed case placeholders should not be replaced' );

		// Only uppercase should be replaced.
		$content = 'VIEW_ID GF_FORM_ID VIEW_SELECTOR';
		$result = $this->replace_placeholders( $content, $view );

		$this->assertStringContainsString( (string) $view->ID, $result );
		$this->assertStringContainsString( (string) $form['id'], $result );
		$this->assertStringContainsString( ".gv-container.gv-container-{$view->ID}", $result );
	}

	/**
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_empty_content_returns_empty() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		$result = $this->replace_placeholders( '', $view );
		$this->assertEquals( '', $result );

		$result = $this->replace_placeholders( null, $view );
		$this->assertNull( $result );
	}

	/**
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_non_view_object_returns_original() {
		$content = 'VIEW_ID should not be replaced';

		// Passing null as view.
		$result = $this->replace_placeholders( $content, null );
		$this->assertEquals( $content, $result );

		// Passing a non-View object.
		$result = $this->replace_placeholders( $content, new stdClass() );
		$this->assertEquals( $content, $result );

		// Passing a string.
		$result = $this->replace_placeholders( $content, 'not a view' );
		$this->assertEquals( $content, $result );
	}

	/**
	 * Test that GF_FORM_ID is not replaced when form is null.
	 *
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_empty_form_id_placeholder_not_replaced() {
		// Create a view without a valid form connection.
		$_view = $this->factory->view->create_and_get( array( 'form_id' => 0 ) );
		$view = \GV\View::from_post( $_view );

		// Force form to null to simulate the edge case.
		$view->form = null;

		$content = '.form-GF_FORM_ID { color: red; }';
		$result = $this->replace_placeholders( $content, $view );

		// The GF_FORM_ID placeholder should remain as-is when form is null.
		$this->assertEquals( $content, $result, 'GF_FORM_ID should not be replaced when form is null to avoid invalid CSS like ".form- { }"' );
	}

	/**
	 * Test custom placeholders via filter.
	 *
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_custom_placeholders_via_filter() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		// Add a custom placeholder.
		add_filter( 'gk/gravityview/custom-code/placeholders', function( $placeholders, $view, $content ) {
			$placeholders['CUSTOM_PLACEHOLDER'] = 'custom_value_123';
			$placeholders['VIEW_TITLE'] = get_the_title( $view->ID );
			return $placeholders;
		}, 10, 3 );

		$content = 'Custom: CUSTOM_PLACEHOLDER, Title: VIEW_TITLE';
		$result = $this->replace_placeholders( $content, $view );

		$this->assertStringContainsString( 'custom_value_123', $result );
		$this->assertStringContainsString( get_the_title( $view->ID ), $result );

		remove_all_filters( 'gk/gravityview/custom-code/placeholders' );
	}

	/**
	 * Test filter can modify existing placeholders.
	 *
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_filter_can_modify_existing_placeholders() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		// Modify an existing placeholder value.
		add_filter( 'gk/gravityview/custom-code/placeholders', function( $placeholders ) {
			$placeholders['VIEW_ID'] = 'modified-view-id';
			return $placeholders;
		} );

		$content = 'View: VIEW_ID';
		$result = $this->replace_placeholders( $content, $view );

		$this->assertEquals( 'View: modified-view-id', $result );

		remove_all_filters( 'gk/gravityview/custom-code/placeholders' );
	}

	/**
	 * Test filter can remove placeholders.
	 *
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_filter_can_remove_placeholders() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		// Remove a placeholder.
		add_filter( 'gk/gravityview/custom-code/placeholders', function( $placeholders ) {
			unset( $placeholders['GF_FORM_ID'] );
			return $placeholders;
		} );

		$content = 'Form: GF_FORM_ID';
		$result = $this->replace_placeholders( $content, $view );

		// GF_FORM_ID should not be replaced since it was removed from placeholders.
		$this->assertEquals( 'Form: GF_FORM_ID', $result );

		remove_all_filters( 'gk/gravityview/custom-code/placeholders' );
	}

	/**
	 * Test multiple Views on the same page have distinct placeholders.
	 *
	 * @group integration
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_multiple_views_on_page() {
		// Create first View with first form.
		$form1  = $this->factory->form->create_and_get();
		$_view1 = $this->factory->view->create_and_get( array( 'form_id' => $form1['id'] ) );
		$view1  = \GV\View::from_post( $_view1 );

		// Create second View with second form.
		$form2  = $this->factory->form->create_and_get();
		$_view2 = $this->factory->view->create_and_get( array( 'form_id' => $form2['id'] ) );
		$view2  = \GV\View::from_post( $_view2 );

		$content = 'VIEW_ID GF_FORM_ID VIEW_SELECTOR';

		// View 1.
		$result1 = $this->replace_placeholders( $content, $view1 );
		$this->assertStringContainsString( (string) $view1->ID, $result1 );
		$this->assertStringContainsString( (string) $form1['id'], $result1 );
		$this->assertStringContainsString( ".gv-container.gv-container-{$view1->ID}", $result1 );

		// View 2.
		$result2 = $this->replace_placeholders( $content, $view2 );
		$this->assertStringContainsString( (string) $view2->ID, $result2 );
		$this->assertStringContainsString( (string) $form2['id'], $result2 );
		$this->assertStringContainsString( ".gv-container.gv-container-{$view2->ID}", $result2 );

		// Ensure View 1 and View 2 have different results.
		$this->assertNotEquals( $result1, $result2 );
	}

	/**
	 * Test JavaScript code with placeholders.
	 *
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_javascript_placeholder_replacement() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		$content = <<<JS
(function($) {
    var viewId = VIEW_ID;
    var formId = GF_FORM_ID;
    var selector = 'VIEW_SELECTOR';

    $(selector).on('click', '.entry', function() {
        console.log('View ' + viewId + ' clicked');
    });
})(jQuery);
JS;

		$result = $this->replace_placeholders( $content, $view );

		$this->assertStringContainsString( "var viewId = {$view->ID};", $result );
		$this->assertStringContainsString( "var formId = {$form['id']};", $result );
		$this->assertStringContainsString( "var selector = '.gv-container.gv-container-{$view->ID}';", $result );
	}

	/**
	 * Test CSS code with placeholders.
	 *
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_css_placeholder_replacement() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		$content = <<<CSS
/* Styles for View VIEW_ID connected to Form GF_FORM_ID */
VIEW_SELECTOR {
    background: #f5f5f5;
}

VIEW_SELECTOR .gv-table-view {
    border: 1px solid #ccc;
}

VIEW_SELECTOR .gv-table-view th {
    background: #333;
    color: white;
}
CSS;

		$result = $this->replace_placeholders( $content, $view );

		$this->assertStringContainsString( "/* Styles for View {$view->ID} connected to Form {$form['id']} */", $result );
		$this->assertStringContainsString( ".gv-container.gv-container-{$view->ID} {", $result );
		$this->assertStringContainsString( ".gv-container.gv-container-{$view->ID} .gv-table-view {", $result );
	}

	/**
	 * Test content without any placeholders is returned unchanged.
	 *
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_content_without_placeholders() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		$content = '.my-custom-style { color: red; }';
		$result = $this->replace_placeholders( $content, $view );

		$this->assertEquals( $content, $result );
	}

	/**
	 * Test that placeholders are replaced even when part of larger strings.
	 *
	 * IMPORTANT: strtr() replaces all occurrences of placeholder strings,
	 * including when they appear as substrings of other identifiers.
	 *
	 * Note: VIEW_SELECTOR is safe because strtr() processes longer keys first,
	 * so VIEW_SELECTOR is replaced before VIEW_ID.
	 *
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_placeholder_replacement_in_substrings() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		// Placeholders ARE replaced even when part of larger strings.
		// This documents the expected strtr() behavior.
		$content = 'MY_VIEW_ID PREFIX_GF_FORM_ID VIEW_ID_SUFFIX';
		$result = $this->replace_placeholders( $content, $view );

		// VIEW_ID is replaced in all occurrences.
		$this->assertStringContainsString( "MY_{$view->ID}", $result );
		$this->assertStringContainsString( "PREFIX_{$form['id']}", $result );
		$this->assertStringContainsString( "{$view->ID}_SUFFIX", $result );

		// The original placeholder strings should no longer exist.
		$this->assertStringNotContainsString( 'VIEW_ID', $result );
		$this->assertStringNotContainsString( 'GF_FORM_ID', $result );
	}

	/**
	 * Test filter receives correct parameters.
	 *
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_filter_receives_correct_parameters() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		$filter_called = false;
		$received_placeholders = null;
		$received_view = null;
		$received_content = null;

		add_filter( 'gk/gravityview/custom-code/placeholders', function( $placeholders, $view, $content ) use ( &$filter_called, &$received_placeholders, &$received_view, &$received_content ) {
			$filter_called = true;
			$received_placeholders = $placeholders;
			$received_view = $view;
			$received_content = $content;
			return $placeholders;
		}, 10, 3 );

		$original_content = 'VIEW_ID test content';
		$this->replace_placeholders( $original_content, $view );

		$this->assertTrue( $filter_called, 'Filter should be called' );
		$this->assertIsArray( $received_placeholders, 'Placeholders should be an array' );
		$this->assertInstanceOf( \GV\View::class, $received_view, 'View should be a View instance' );
		$this->assertEquals( $original_content, $received_content, 'Content should match original' );

		// Check default placeholders are present.
		$this->assertArrayHasKey( 'VIEW_ID', $received_placeholders );
		$this->assertArrayHasKey( 'GF_FORM_ID', $received_placeholders );
		$this->assertArrayHasKey( 'VIEW_SELECTOR', $received_placeholders );

		remove_all_filters( 'gk/gravityview/custom-code/placeholders' );
	}

	// =========================================================================
	// Integration Tests - Verify actual WordPress hook flow
	// =========================================================================

	/**
	 * Reset the GravityView context for integration tests.
	 */
	private function reset_gv_context() {
		\GV\Mocks\Legacy_Context::reset();
		gravityview()->request = new \GV\Frontend_Request();
		\GV\View::_flush_cache();
	}

	/**
	 * Test that custom CSS is enqueued with placeholders replaced.
	 *
	 * @group integration
	 * @covers GravityView_frontend::add_scripts_and_styles()
	 */
	public function test_custom_css_enqueued_with_placeholders_replaced() {
		$this->reset_gv_context();

		$form  = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'settings' => array(
				'custom_css' => '.view-VIEW_ID { color: red; } /* View VIEW_ID, Form GF_FORM_ID */',
			),
		) );
		$view = \GV\View::by_id( $_view->ID );

		// Create a View_Collection and add the view.
		$views = new \GV\View_Collection();
		$views->add( $view );

		// Push the views into the legacy context.
		\GV\Mocks\Legacy_Context::push( array(
			'views' => $views,
			'post'  => $_view,
			'view'  => $view,
		) );

		// Set up mock request.
		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		// NOTE: We intentionally do NOT pre-register the style handle here.
		// The add_scripts_and_styles() method must call enqueue_default_style()
		// BEFORE wp_add_inline_style() to ensure the handle exists.
		// This test will fail if the order is wrong.

		// Simulate wp_enqueue_scripts hook.
		$fe = GravityView_frontend::getInstance();
		$fe->add_scripts_and_styles();

		// Verify the style was registered by add_scripts_and_styles().
		global $wp_styles;
		$this->assertTrue(
			isset( $wp_styles->registered['gravityview_default_style'] ),
			'gravityview_default_style should be registered by add_scripts_and_styles()'
		);

		// Get the inline styles that were added.
		$inline_css = '';
		$style = $wp_styles->registered['gravityview_default_style'];
		if ( ! empty( $style->extra['after'] ) ) {
			$inline_css = implode( "\n", $style->extra['after'] );
		}

		// Verify inline CSS was attached (this fails if wp_add_inline_style is called before registration).
		$this->assertNotEmpty( $inline_css, 'Inline CSS should be attached to the style handle' );

		// Verify placeholders were replaced.
		$this->assertStringContainsString( ".view-{$view->ID}", $inline_css, 'VIEW_ID should be replaced' );
		$this->assertStringContainsString( "View {$view->ID}", $inline_css, 'VIEW_ID in comment should be replaced' );
		$this->assertStringContainsString( "Form {$form['id']}", $inline_css, 'GF_FORM_ID should be replaced' );

		// Original placeholders should not exist.
		$this->assertStringNotContainsString( 'VIEW_ID', $inline_css );
		$this->assertStringNotContainsString( 'GF_FORM_ID', $inline_css );

		// Clean up.
		wp_deregister_style( 'gravityview_default_style' );
		$this->reset_gv_context();
	}

	/**
	 * Test that custom JavaScript is enqueued with placeholder replacement.
	 *
	 * @group integration
	 * @covers GravityView_frontend::add_scripts_and_styles()
	 */
	public function test_custom_javascript_enqueued_with_placeholders_replaced() {
		$this->reset_gv_context();

		$form  = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array(
			'form_id' => $form['id'],
			'settings' => array(
				'custom_javascript' => 'console.log("View ID:", VIEW_ID, "Form ID:", GF_FORM_ID);',
			),
		) );
		$view = \GV\View::by_id( $_view->ID );

		// Create a View_Collection and add the view.
		$views = new \GV\View_Collection();
		$views->add( $view );

		// Push the views into the legacy context.
		\GV\Mocks\Legacy_Context::push( array(
			'views' => $views,
			'post'  => $_view,
			'view'  => $view,
		) );

		// Set up mock request.
		gravityview()->request = new \GV\Mock_Request();
		gravityview()->request->returns['is_view'] = $view;

		// NOTE: Unlike the CSS test, we DO need to pre-register the script handle.
		// The gravityview-fe-view script is registered in a separate WordPress hook
		// (wp_register_scripts), not within add_scripts_and_styles() itself.
		// This matches the real WordPress hook sequence.
		wp_register_script( 'gravityview-fe-view', false );

		// Simulate wp_enqueue_scripts hook.
		$fe = GravityView_frontend::getInstance();
		$fe->add_scripts_and_styles();

		// Get the inline scripts that were added.
		global $wp_scripts;
		$inline_js = '';
		if ( isset( $wp_scripts->registered['gravityview-fe-view'] ) ) {
			$script = $wp_scripts->registered['gravityview-fe-view'];
			if ( ! empty( $script->extra['after'] ) ) {
				$inline_js = implode( "\n", $script->extra['after'] );
			}
		}

		// Verify placeholders were replaced.
		$this->assertStringContainsString( "View ID:\", {$view->ID}", $inline_js, 'VIEW_ID should be replaced' );
		$this->assertStringContainsString( "Form ID:\", {$form['id']}", $inline_js, 'GF_FORM_ID should be replaced' );

		// Original placeholders should not exist.
		$this->assertStringNotContainsString( 'VIEW_ID,', $inline_js );

		// Clean up.
		wp_deregister_script( 'gravityview-fe-view' );
		$this->reset_gv_context();
	}

	/**
	 * Test that VIEW_SELECTOR provides high-specificity CSS selector.
	 *
	 * VIEW_SELECTOR outputs `.gv-container.gv-container-{view_id}` which has
	 * higher specificity than a single class, reducing the need for !important.
	 *
	 * @group integration
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_view_id_selector_provides_high_specificity() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		$content = 'VIEW_SELECTOR { color: red; }';
		$result = $this->replace_placeholders( $content, $view );

		// Should output double-class selector for high specificity.
		$expected = ".gv-container.gv-container-{$view->ID} { color: red; }";
		$this->assertEquals( $expected, $result );

		// The selector should match the actual container class output by gv_container_class().
		// This ensures CSS targeting works correctly.
		$this->assertStringContainsString( 'gv-container', $result, 'Selector should include gv-container class' );
	}
}
