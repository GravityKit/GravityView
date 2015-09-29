<?php

class GravityView_Uninstall_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @var int
	 */
	var $form_id = 0;

	/**
	 * @var array GF Form array
	 */
	var $form = array();

	/**
	 * @var int
	 */
	var $entry_id = 0;

	/**
	 * @var array GF Entry array
	 */
	var $entry = array();

	function setUp() {
		parent::setUp();

		define( 'WP_UNINSTALL_PLUGIN', true );

		require_once GV_Unit_Tests_Bootstrap::instance()->plugin_dir . '/uninstall.php';


		$this->form = GV_Unit_Tests_Bootstrap::instance()->get_form();
		$this->form_id = GV_Unit_Tests_Bootstrap::instance()->get_form_id();

		$this->entry = GV_Unit_Tests_Bootstrap::instance()->get_entry();
		$this->entry_id = GV_Unit_Tests_Bootstrap::instance()->get_entry_id();

		do_action( 'deactivate_gravityview/gravityview.php' );
	}

	/**
	 * @group uninstall
	 */
	function test_gravityview_has_shortcode_r() {



	}
}
