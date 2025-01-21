<?php

class GV_UnitTest_Factory_For_View extends WP_UnitTest_Factory_For_Post {

	/**
	 * @param GV_UnitTest_Factory $factory
	 */
	function __construct( $factory = null ) {

		parent::__construct( $factory );

		$form = $factory->form->create_and_get();

		$post_title = new WP_UnitTest_Generator_Sequence( 'GravityView title %s' );
		$post_content = new WP_UnitTest_Generator_Sequence( 'Post content %s' );
		$post_excerpt = new WP_UnitTest_Generator_Sequence( 'Post excerpt %s' );

		$this->default_generation_definitions = array(
			'post_status' => 'publish',
			'post_title' => $post_title->next(),
			'post_content' => $post_content->next(),
			'post_excerpt' => $post_excerpt->next(),
			'post_author' => '',
			'post_type' => 'gravityview',
			'form_id' => $form['id'],
			'template_id' => 'preset_business_data',
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

		$args = wp_parse_args( $args, $this->default_generation_definitions );

		$form_id = $args['form_id'];
		$template_id = isset( $args['template_id'] ) ? $args['template_id'] : 'preset_business_data';
		$settings = isset( $args['settings'] ) ? $args['settings'] : GravityView_View_Data::get_default_args();
		$widgets = isset( $args['widgets'] ) ? $args['widgets'] : array();
		$joins = isset( $args['joins'] ) ? $args['joins'] : array();
		$fields = isset( $args['fields'] ) ? serialize( $args['fields'] ) : 'a:1:{s:23:"directory_table-columns";a:3:{s:13:"535d63d1488b0";a:9:{s:2:"id";s:1:"4";s:5:"label";s:13:"Business Name";s:10:"show_label";s:1:"1";s:12:"custom_label";s:0:"";s:12:"custom_class";s:0:"";s:12:"show_as_link";s:1:"0";s:13:"search_filter";s:1:"0";s:13:"only_loggedin";s:1:"0";s:17:"only_loggedin_cap";s:4:"read";}s:13:"535d63d379a3c";a:9:{s:2:"id";s:2:"12";s:5:"label";s:20:"Business Description";s:10:"show_label";s:1:"1";s:12:"custom_label";s:0:"";s:12:"custom_class";s:0:"";s:12:"show_as_link";s:1:"0";s:13:"search_filter";s:1:"0";s:13:"only_loggedin";s:1:"0";s:17:"only_loggedin_cap";s:4:"read";}s:13:"535d63dc735a6";a:9:{s:2:"id";s:1:"2";s:5:"label";s:7:"Address";s:10:"show_label";s:1:"1";s:12:"custom_label";s:0:"";s:12:"custom_class";s:0:"";s:12:"show_as_link";s:1:"0";s:13:"search_filter";s:1:"0";s:13:"only_loggedin";s:1:"0";s:17:"only_loggedin_cap";s:4:"read";}}}';

		$insert_post_response = parent::create_object( $args );

		if( $insert_post_response && ! is_wp_error( $insert_post_response ) ) {

			$view_meta = array(
				'_gravityview_form_id' => $form_id,
				'_gravityview_template_settings' => $settings,
				'_gravityview_directory_template' => $template_id,
				'_gravityview_directory_widgets' => $widgets,
				'_gravityview_directory_fields' => $fields,
				'_gravityview_form_joins' => $joins,
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

	function create( $args = array(), $generation_definitions = null ) {

		if ( is_null( $generation_definitions ) ) {
			$generation_definitions = $this->default_generation_definitions;
		}

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

			foreach ( (array) $capabilities as $cap ) {
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

class GV_UnitTest_Factory_For_Entry extends GF_UnitTest_Factory_For_Entry {

	/**
	 * @param GF_UnitTest_Factory $factory
	 */
	function __construct( $factory = null ) {

		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'1' => 'Value for field one',
			'2' => 'Value for field two',
			'3' => GV_UnitTest_Generator_Number::get(),
			'ip' => GV_UnitTest_Generator_IP::get(),
			'source_url' => 'http://example.com/wordpress/?gf_page=preview&id=16',
			'source_id' => 16,
			'user_agent' => GF_UnitTest_Generator_User_Agent::get(),
			'payment_status' => GF_UnitTest_Generator_Payment_Status::get(),
			'payment_date' => GV_UnitTest_Generator_Date::get(),
			'payment_amount' => GV_UnitTest_Generator_Float::get(),
			'transaction_id' => 'asdfpaoj442gpoagfadf',
			'created_by' => 1,
			'status' => GF_UnitTest_Generator_Status::get(),
			'date_created' => GF_UnitTest_Generator_Date_Created::get(),
		);
	}

	function create_object( $args ) {

		foreach ( $this->default_generation_definitions as $key => $value ) {
			if ( ! isset( $args[ $key ] ) ) {
				$args[ $key ] = $this->default_generation_definitions[ $key ];
			}
		}

		if( !isset( $args['form_id'] ) ) {
			$form = $this->factory->form->create();
			$args['form_id'] = $form['id'];
		}

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
		return GFAPI::update_entry( $entry, $entry_id );
	}

	/**
	 * Create form from a json dump file.
	 *
	 * @param string $filename Name of the file in data/forms/
	 * @param int $overrides Data that we want to override
	 *
	 * @return array A form array as returned by Gravity Forms
	 */
	function import_and_get( $filename, $overrides ) {
		$entry_json = file_get_contents( dirname( __FILE__ ) . "/data/forms/" . $filename );
		$entry     = json_decode( $entry_json, true );

		/**
		 * Note: wp_parse_args does not work well here
		 *  since it uses array_merge which reindexes numeric keys.
		 * This messes up the field IDs. We do it our own way.
		 */
		foreach ( $overrides as $key => $value ) {
			$entry[ $key ] = $value;
		}
		return $this->get_object_by_id( GFAPI::add_entry( $entry ) );
	}
}

class GV_UnitTest_Factory_For_Form extends GF_UnitTest_Factory_For_Form {

	/**
	 * @param GF_UnitTest_Factory $factory
	 */
	function __construct( $factory = null ) {
		parent::__construct( $factory );
	}

	function create_object( $args = array() ) {
		$args = wp_parse_args( $args, $this->default_generation_definitions );
		$title_sequence = new WP_UnitTest_Generator_Sequence( 'Form Title %s' );
		$args['title'] = $title_sequence->next();
		return GFAPI::add_form( $args );
	}

	function get_object_by_id( $object_id ) {
		return GFAPI::get_form( $object_id );
	}

	function update_object( $object, $fields ) {}

	/**
	 * Create form from a json dump file.
	 *
	 * @param string $filename Name of the file in data/forms/
	 * @param int    $index    A subform in the json file.
	 *
	 * @return array A form array as returned by Gravity Forms
	 */
	function import_and_get( $filename, $index = 0 ) {
		$form_json = file_get_contents( dirname( __FILE__ ) . "/data/forms/" . $filename );
		$forms     = json_decode( $form_json, true );
		$form      = $forms[ $index ];

		return $this->create_and_get( array(), $form );
	}
}
