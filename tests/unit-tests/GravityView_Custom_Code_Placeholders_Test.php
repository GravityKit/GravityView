<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * Tests the custom code placeholder replacement functionality.
 *
 * @group frontend
 * @group placeholders
 * @since $ver$
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
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_view_anchor_id_placeholder_replacement() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		// When anchor_id is not set, it should default to gv-view-{id}-1.
		$content = 'Anchor is VIEW_ANCHOR_ID';
		$result = $this->replace_placeholders( $content, $view );

		$expected_anchor = "gv-view-{$view->ID}-1";
		$this->assertEquals( "Anchor is {$expected_anchor}", $result );
	}

	/**
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_view_id_selector_placeholder_replacement() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		$content = 'Selector is VIEW_ID_SELECTOR';
		$result = $this->replace_placeholders( $content, $view );

		$expected_selector = "#gv-view-{$view->ID}-1";
		$this->assertEquals( "Selector is {$expected_selector}", $result );
	}

	/**
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_anchor_id_with_set_anchor() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		// Simulate the anchor being set during rendering.
		$view->set_anchor_id( 3 );

		$content = 'Anchor is VIEW_ANCHOR_ID and selector is VIEW_ID_SELECTOR';
		$result = $this->replace_placeholders( $content, $view );

		$expected_anchor = "gv-view-{$view->ID}-3";
		$this->assertStringContainsString( $expected_anchor, $result );
		$this->assertStringContainsString( "#{$expected_anchor}", $result );
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
VIEW_ID_SELECTOR .entry {
    color: red;
}
[data-anchor="VIEW_ANCHOR_ID"] {
    display: block;
}
CSS;

		$result = $this->replace_placeholders( $content, $view );

		$this->assertStringContainsString( ".my-view-{$view->ID}", $result );
		$this->assertStringContainsString( "/* Form: {$form['id']} */", $result );
		$this->assertStringContainsString( "#gv-view-{$view->ID}-1 .entry", $result );
		$this->assertStringContainsString( "[data-anchor=\"gv-view-{$view->ID}-1\"]", $result );
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
		$content = 'view_id gf_form_id view_anchor_id view_id_selector';
		$result = $this->replace_placeholders( $content, $view );

		$this->assertEquals( $content, $result, 'Lowercase placeholders should not be replaced' );

		// Mixed case should not be replaced.
		$content = 'View_Id GF_Form_ID View_Anchor_Id View_Id_Selector';
		$result = $this->replace_placeholders( $content, $view );

		$this->assertEquals( $content, $result, 'Mixed case placeholders should not be replaced' );

		// Only uppercase should be replaced.
		$content = 'VIEW_ID GF_FORM_ID VIEW_ANCHOR_ID VIEW_ID_SELECTOR';
		$result = $this->replace_placeholders( $content, $view );

		$this->assertStringContainsString( (string) $view->ID, $result );
		$this->assertStringContainsString( (string) $form['id'], $result );
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
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_multiple_views_on_page() {
		// Create first View with first form.
		$form1 = $this->factory->form->create_and_get();
		$_view1 = $this->factory->view->create_and_get( array( 'form_id' => $form1['id'] ) );
		$view1 = \GV\View::from_post( $_view1 );
		$view1->set_anchor_id( 1 );

		// Create second View with second form.
		$form2 = $this->factory->form->create_and_get();
		$_view2 = $this->factory->view->create_and_get( array( 'form_id' => $form2['id'] ) );
		$view2 = \GV\View::from_post( $_view2 );
		$view2->set_anchor_id( 2 );

		$content = 'VIEW_ID GF_FORM_ID VIEW_ID_SELECTOR';

		// Test View 1.
		$result1 = $this->replace_placeholders( $content, $view1 );
		$this->assertStringContainsString( (string) $view1->ID, $result1 );
		$this->assertStringContainsString( (string) $form1['id'], $result1 );
		$this->assertStringContainsString( "#gv-view-{$view1->ID}-1", $result1 );

		// Test View 2.
		$result2 = $this->replace_placeholders( $content, $view2 );
		$this->assertStringContainsString( (string) $view2->ID, $result2 );
		$this->assertStringContainsString( (string) $form2['id'], $result2 );
		$this->assertStringContainsString( "#gv-view-{$view2->ID}-2", $result2 );

		// Ensure View 1 and View 2 have different results.
		$this->assertNotEquals( $result1, $result2 );
	}

	/**
	 * Test same View embedded multiple times has different anchor IDs.
	 *
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_same_view_multiple_instances() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		$content = 'VIEW_ID_SELECTOR';

		// First instance.
		$view->set_anchor_id( 1 );
		$result1 = $this->replace_placeholders( $content, $view );
		$this->assertEquals( "#gv-view-{$view->ID}-1", $result1 );

		// Second instance.
		$view->set_anchor_id( 2 );
		$result2 = $this->replace_placeholders( $content, $view );
		$this->assertEquals( "#gv-view-{$view->ID}-2", $result2 );

		// Third instance.
		$view->set_anchor_id( 3 );
		$result3 = $this->replace_placeholders( $content, $view );
		$this->assertEquals( "#gv-view-{$view->ID}-3", $result3 );
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
    var selector = 'VIEW_ID_SELECTOR';
    var anchorId = 'VIEW_ANCHOR_ID';

    $(selector).on('click', '.entry', function() {
        console.log('View ' + viewId + ' clicked');
    });
})(jQuery);
JS;

		$result = $this->replace_placeholders( $content, $view );

		$this->assertStringContainsString( "var viewId = {$view->ID};", $result );
		$this->assertStringContainsString( "var formId = {$form['id']};", $result );
		$this->assertStringContainsString( "var selector = '#gv-view-{$view->ID}-1';", $result );
		$this->assertStringContainsString( "var anchorId = 'gv-view-{$view->ID}-1';", $result );
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
VIEW_ID_SELECTOR {
    background: #f5f5f5;
}

VIEW_ID_SELECTOR .gv-table-view {
    border: 1px solid #ccc;
}

VIEW_ID_SELECTOR .gv-table-view th {
    background: #333;
    color: white;
}

[id="VIEW_ANCHOR_ID"] .entry-row:hover {
    background: #e5e5e5;
}
CSS;

		$result = $this->replace_placeholders( $content, $view );

		$this->assertStringContainsString( "/* Styles for View {$view->ID} connected to Form {$form['id']} */", $result );
		$this->assertStringContainsString( "#gv-view-{$view->ID}-1 {", $result );
		$this->assertStringContainsString( "#gv-view-{$view->ID}-1 .gv-table-view {", $result );
		$this->assertStringContainsString( "[id=\"gv-view-{$view->ID}-1\"]", $result );
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
	 * Test that existing user code with similar text is not affected.
	 *
	 * @covers GravityView_frontend::replace_code_placeholders()
	 */
	public function test_similar_text_not_affected() {
		$form = $this->factory->form->create_and_get();
		$_view = $this->factory->view->create_and_get( array( 'form_id' => $form['id'] ) );
		$view = \GV\View::from_post( $_view );

		// Text that is similar but not exact should not be replaced.
		$content = 'MY_VIEW_ID PREFIX_GF_FORM_ID VIEW_ID_SUFFIX';
		$result = $this->replace_placeholders( $content, $view );

		// These should remain unchanged (partial matches).
		$this->assertStringContainsString( 'MY_VIEW_ID', $result );
		$this->assertStringContainsString( 'PREFIX_GF_FORM_ID', $result );

		// But VIEW_ID should be replaced in VIEW_ID_SUFFIX.
		$this->assertStringContainsString( "{$view->ID}_SUFFIX", $result );
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
		$this->assertArrayHasKey( 'VIEW_ANCHOR_ID', $received_placeholders );
		$this->assertArrayHasKey( 'VIEW_ID_SELECTOR', $received_placeholders );

		remove_all_filters( 'gk/gravityview/custom-code/placeholders' );
	}
}
