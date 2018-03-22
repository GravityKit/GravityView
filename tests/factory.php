<?php

class GV_UnitTest_Factory extends GF_UnitTest_Factory {

	/**
	 * @var GF_UnitTest_Factory_For_Form
	 */
	public $form;

	/**
	 * @var GV_UnitTest_Factory_For_Entry
	 */
	public $entry;

	/**
	 * @var GV_UnitTest_Factory_For_View
	 */
	public $view;

	/**
	 * @var GV_UnitTest_Factory_For_User
	 */
	public $user;

	/**
	 * @var WP_UnitTest_Factory_For_Post
	 */
	public $post;

	function __construct() {
		parent::__construct();

		$this->user = new GV_UnitTest_Factory_For_User( $this );

		$this->entry = new GV_UnitTest_Factory_For_Entry( $this );

		$this->form = new GV_UnitTest_Factory_For_Form( $this );

		$this->view = new GV_UnitTest_Factory_For_View( $this );

		$this->post = new WP_UnitTest_Factory_For_Post();
	}
}