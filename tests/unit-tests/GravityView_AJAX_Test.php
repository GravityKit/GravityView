<?php

class GravityView_Ajax_Test extends GV_UnitTestCase {

	/**
	 * @var GravityView_Ajax
	 */
	var $AJAX;

	function setUp() {

		parent::setUp();

		$this->AJAX = new GravityView_Ajax;
		$this->create_test_nonce();

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
	 * @covers GravityView_Ajax::import_form()
	 * @group gvajax
	 */
	function test_import_form() {
		/** @define "GRAVITYVIEW_DIR" "../../" */

		$GravityView_Preset_Business_Data = new GravityView_Preset_Business_Data;

		$forms = $this->AJAX->import_form( $GravityView_Preset_Business_Data->settings['preset_form'] );

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