<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

class GravityView_Ajax_Test extends GV_UnitTestCase {

	/**
	 * @var GravityView_Ajax
	 */
	var $AJAX;

	/**
	 * @var GravityView_Preset_Business_Data
	 */
	var $GravityView_Preset_Business_Data;

	function setUp() {

		parent::setUp();

		$this->AJAX = new GravityView_Ajax;
		$this->create_test_nonce();
		$this->GravityView_Preset_Business_Data = new GravityView_Preset_Business_Data;

		require_once( GFCommon::get_base_path() . '/export.php' );
	}

	/**
	 * Set a valid "gravityview_ajaxviews" $_POST['nonce'] value
	 * @see GravityView_Ajax::check_ajax_nonce()
	 */
	function create_test_nonce() {
		$_POST['nonce'] = wp_create_nonce( 'gravityview_ajaxviews' );
	}

	/**
	 * @covers GravityView_Ajax::pre_get_form_fields()
	 * @group gvajax
	 */
	function test_get_available_fields_html() {

		$_POST['template_id'] = $this->GravityView_Preset_Business_Data->template_id;

		// Test form generation and default context
		add_action( 'gravityview_render_available_fields', array( $this, 'get_available_fields_html_DEFAULT' ), 10, 2 );
		$this->AJAX->get_available_fields_html();
		remove_action( 'gravityview_render_available_fields', array( $this, 'get_available_fields_html_DEFAULT' ), 10 );

		// Test SINGLE context being set
		$_POST['context'] = 'single';
		add_action( 'gravityview_render_available_fields', array( $this, 'get_available_fields_html_SINGLE_CONTEXT' ), 10, 2 );
		$this->AJAX->get_available_fields_html();
		remove_action( 'gravityview_render_available_fields', array( $this, 'get_available_fields_html_SINGLE_CONTEXT' ), 10 );

		// Test EDIT context being set
		$_POST['context'] = 'edit';
		add_action( 'gravityview_render_available_fields', array( $this, 'get_available_fields_html_EDIT_CONTEXT' ), 10, 2 );
		$this->AJAX->get_available_fields_html();
		remove_action( 'gravityview_render_available_fields', array( $this, 'get_available_fields_html_EDIT_CONTEXT' ), 10 );

	}



	/**
	 * @param array $form
	 * @param string $context
	 */
	function get_available_fields_html_DEFAULT( $form, $context ) {

		$this->assertEquals( GravityView_Ajax::pre_get_form_fields( $this->GravityView_Preset_Business_Data->template_id ), $form );

		// When not defined, default to directory
		$this->assertEquals( 'directory', $context );
	}

	/**
	 * @param array $form
	 * @param string $context
	 */
	function get_available_fields_html_SINGLE_CONTEXT( $form, $context ) {

		// When not defined, default to directory
		$this->assertEquals( 'single', $context );
	}

	/**
	 * @param array $form
	 * @param string $context
	 */
	function get_available_fields_html_EDIT_CONTEXT( $form, $context ) {

		// When not defined, default to directory
		$this->assertEquals( 'edit', $context );
	}

	/**
	 * @covers GravityView_Ajax::pre_get_form_fields()
	 * @group gvajax
	 */
	function test_pre_get_form_fields() {

		$imported_form = $this->AJAX->import_form( $this->GravityView_Preset_Business_Data->settings['preset_form'] );

		$not_imported_form = GravityView_Ajax::pre_get_form_fields( $this->GravityView_Preset_Business_Data->template_id );

		// We don't test exact equality, since the import_form will return GF_Field objects for field items, and other
		// differences. We just want to make sure most stuff matches close enough to suggest it's working!
		$this->assertEquals( count( $imported_form['fields'] ), count( $not_imported_form['fields'] ) );
		$this->assertEquals( $imported_form['title'], $not_imported_form['title'] );
	}

	/**
	 * @covers GravityView_Ajax::import_form()
	 * @group gvajax
	 */
	function test_import_form() {
		/** @define "GRAVITYVIEW_DIR" "../../" */

		$forms = $this->AJAX->import_form( $this->GravityView_Preset_Business_Data->settings['preset_form'] );

		$this->assertNotEmpty( $forms );
		$this->assertEquals( 'GravityView - Business Data', $forms['title'] );
		$this->assertEquals( 14, sizeof( $forms['fields'] ) );

		$GravityView_Preset_Business_Listings = new GravityView_Preset_Business_Listings;

		$forms = $this->AJAX->import_form( $GravityView_Preset_Business_Listings->settings['preset_form'] );

		$this->assertNotEmpty( $forms );
		$this->assertEquals( 'GravityView - Business Listing', $forms['title'] );
		$this->assertEquals( 13, sizeof( $forms['fields'] ) );
	}
}