<?php

class GF_UnitTest_Factory extends WP_UnitTest_Factory {

	/**
	 * @var GF_UnitTest_Factory_For_Form
	 */
	public $form;

	/**
	 * @var GF_UnitTest_Factory_For_Entry
	 */
	public $entry;

	/**
	 * @var GV_UnitTest_Factory_For_User
	 */
	public $user;

	function __construct() {
		parent::__construct();

		$this->user = new GV_UnitTest_Factory_For_User( $this );

		$this->entry = new GF_UnitTest_Factory_For_Entry( $this );
		$this->form = new GF_UnitTest_Factory_For_Form( $this );

	}

}

class GV_UnitTest_Factory_For_User extends WP_UnitTest_Factory_For_User {

	function create( $args = array(), $generation_definitions = array() ) {
		$user = false;
		if( ! empty( $args['user_login'] ) ) {
			$user = get_user_by( 'login', $args['user_login'] );
		} else if( ! empty( $args['id'] ) ) {
			$user = get_user_by( 'id', $args['id'] );
		}

		return $user ? $user->ID : parent::create( $args, $generation_definitions );
	}

	/**
	 * Create the user, then set the current user to the created user.
	 *
	 * @param array $args
	 * @param null $generation_definitions
	 *
	 * @return bool|WP_User
	 */
	function create_and_set( $args = array(), $generation_definitions = null ) {

		$user_id = $this->create( $args, $generation_definitions );

		if( ! $user_id || is_wp_error( $user_id ) ) {
			return false;
		}

		return $this->set( $user_id );
	}

	/**
	 * Alias for wp_set_current_user()
	 *
	 * @see wp_set_current_user()
	 *
	 * @param $user_id
	 *
	 * @return WP_User
	 */
	function set( $user_id ) {
		return wp_set_current_user( $user_id );
	}
}

class GF_UnitTest_Factory_For_Entry extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'1' => 'Value for field one',
			'2' => 'Value for field two',
			'3' => '3.33333',
			'ip' => '127.0.0.1',
			'source_url' => 'http://example.com/wordpress/?gf_page=preview&id=16',
			'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.78.2 (KHTML, like Gecko) Version/7.0.6 Safari/537.78.2',
			'payment_status' => 'Processing',
			'payment_date' => '2014-08-29 20:55:06',
			'payment_amount' => '0.01',
			'transaction_id' => 'asdfpaoj442gpoagfadf',
			'created_by' => 1,
			'status' => 'active',
			'date_created' => '2014-08-29 18:25:39',
		);
	}

	function create_object( $args ) {
		return GFAPI::add_entry( $args );
	}

	function get_object_by_id( $object_id ) {
		return GFAPI::get_entry( $object_id );
	}

	/**
	 * @param $object
	 * @param $fields
	 *
	 * @return mixed
	 */
	function update_object( $entry_id = '', $entry = array() ) {
		return GFAPI::update_entry( $entry_id, $entry );
	}
}

class GF_UnitTest_Factory_For_Form extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'title' => 'This is the form title',
			'fields' => array(
				new GF_Field_Text(array(
					'id' => 1,
					'label' => 'Label for field one (text)',
					'choices' => array(),
					'inputs' => '',
				)),
				new GF_Field_Hidden(array(
					'id' => 2,
					'label' => 'Label for field two (hidden)',
					'choices' => array(),
					'inputs' => '',
				)),
				new GF_Field_Number(array(
					'id' => 3,
					'label' => 'Label for field three (number)',
					'choices' => array(),
					'inputs' => '',
				))
			),
		);
	}

	function create_object( $file, $parent = 0, $args = array() ) {
		return GFAPI::add_form( $args );
	}

	function get_object_by_id( $object_id ) {
		return GFAPI::get_form( $object_id );
	}

	function update_object( $object, $fields ) {}
}