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
	 * @var GV_UnitTest_Factory_For_View
	 */
	public $view;

	/**
	 * @var GV_UnitTest_Factory_For_User
	 */
	public $user;

	function __construct() {
		parent::__construct();

		$this->user = new GV_UnitTest_Factory_For_User( $this );

		$this->entry = new GF_UnitTest_Factory_For_Entry( $this );

		$this->form = new GF_UnitTest_Factory_For_Form( $this );

		$this->view = new GV_UnitTest_Factory_For_View( $this );

	}
}

class GV_UnitTest_Factory_For_View extends WP_UnitTest_Factory_For_Post {

	/**
	 * @param GF_UnitTest_Factory $factory
	 */
	function __construct( $factory = null ) {

		parent::__construct( $factory );

		$form = $factory->form->create_and_get();

		$this->default_generation_definitions = array(
			'post_status' => 'publish',
			'post_title' => new WP_UnitTest_Generator_Sequence( 'GravityView title %s' ),
			'post_content' => new WP_UnitTest_Generator_Sequence( 'Post content %s' ),
			'post_excerpt' => new WP_UnitTest_Generator_Sequence( 'Post excerpt %s' ),
			'post_author' => '',
			'post_type' => 'gravityview',
			'form_id' => $form['id'],
		);
	}

	/**
	 * Alias for parent method
	 * Only purpose is to add return values for IDE
	 * @return array|null|WP_Post
	 */
	function create_and_get( $args = array(), $generation_definitions = null ) {
		return parent::create_and_get( $args, $generation_definitions );
	}

	/**
	 * @param $args
	 *
	 * @return int|WP_Error
	 */
	function create_object( $args ) {

		$form_id = $args['form_id'];
		$settings = isset( $args['settings'] ) ? $args['settings'] : GravityView_View_Data::get_default_args();
		$fields = isset( $args['fields'] ) ? $args['fields'] : array();

		$insert_post_response = parent::create_object( $args );

		if( $insert_post_response && ! is_wp_error( $insert_post_response ) ) {

			$view_meta = array(
				'_gravityview_form_id' => $form_id,
				'_gravityview_template_settings' => $settings,
				'_gravityview_directory_template' => 'preset_business_data',
				'_gravityview_directory_widgets' => 'a:0:{}',
				'_gravityview_directory_fields' => 'a:1:{s:23:"directory_table-columns";a:3:{s:13:"535d63d1488b0";a:9:{s:2:"id";s:1:"4";s:5:"label";s:13:"Business Name";s:10:"show_label";s:1:"1";s:12:"custom_label";s:0:"";s:12:"custom_class";s:0:"";s:12:"show_as_link";s:1:"0";s:13:"search_filter";s:1:"0";s:13:"only_loggedin";s:1:"0";s:17:"only_loggedin_cap";s:4:"read";}s:13:"535d63d379a3c";a:9:{s:2:"id";s:2:"12";s:5:"label";s:20:"Business Description";s:10:"show_label";s:1:"1";s:12:"custom_label";s:0:"";s:12:"custom_class";s:0:"";s:12:"show_as_link";s:1:"0";s:13:"search_filter";s:1:"0";s:13:"only_loggedin";s:1:"0";s:17:"only_loggedin_cap";s:4:"read";}s:13:"535d63dc735a6";a:9:{s:2:"id";s:1:"2";s:5:"label";s:7:"Address";s:10:"show_label";s:1:"1";s:12:"custom_label";s:0:"";s:12:"custom_class";s:0:"";s:12:"show_as_link";s:1:"0";s:13:"search_filter";s:1:"0";s:13:"only_loggedin";s:1:"0";s:17:"only_loggedin_cap";s:4:"read";}}}',
			);

			foreach ( $view_meta as $meta_key => $meta_value ) {
				$meta_value = maybe_unserialize( $meta_value );
				update_post_meta( $insert_post_response, $meta_key, $meta_value );
			}
		}

		return $insert_post_response;
	}

}

class GV_UnitTest_Factory_For_User extends WP_UnitTest_Factory_For_User {

	function create( $args = array(), $generation_definitions = array() ) {
		$user = false;
		if( ! empty( $args['user_login'] ) ) {
			$user = get_user_by( 'login', $args['user_login'] );
		} else if( ! empty( $args['id'] ) ) {
			$user = $this->get_object_by_id( $args['id'] );
		}

		if( ! $user ) {
			$user_id = parent::create( $args, $generation_definitions );
			$user = $this->get_object_by_id( $user_id );
		}

		$this->_add_gravityview_caps( $user );

		return $user->ID;
	}

	function create_object( $args ) {
		return wp_insert_user( $args );
	}

	/**
	 * Add GravityView user caps based on role
	 * @since 1.15
	 * @param WP_User $user
	 */
	function _add_gravityview_caps( WP_User $user ) {
		foreach( $user->roles as $role ) {
			$capabilities = GravityView_Roles_Capabilities::all_caps( $role );

			foreach ( $capabilities as $cap ) {
				$user->add_cap( $cap, true );
			}
		}
	}

	/**
	 * @param array $args
	 * @param null $generation_definitions
	 *
	 * @return WP_User
	 */
	function create_and_get( $args = array(), $generation_definitions = null ) {
		return parent::create_and_get( $args, $generation_definitions );
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
		$user = wp_set_current_user( $user_id );

		$this->_add_gravityview_caps( $user );
		return $user;
	}
}

class GF_UnitTest_Factory_For_Entry extends WP_UnitTest_Factory_For_Thing {

	/**
	 * @param GF_UnitTest_Factory $factory
	 */
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
		$args = wp_parse_args( $args, $this->default_generation_definitions );
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

	/**
	 * @param GF_UnitTest_Factory $factory
	 */
	function __construct( $factory = null ) {

		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'title' => new WP_UnitTest_Generator_Sequence( 'Form Title %s' ),
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