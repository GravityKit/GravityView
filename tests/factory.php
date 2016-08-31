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

	/**
	 * @var WP_UnitTest_Factory_For_Post
	 */
	public $post;

	function __construct() {
		parent::__construct();

		$this->user = new GV_UnitTest_Factory_For_User( $this );

		$this->entry = new GF_UnitTest_Factory_For_Entry( $this );

		$this->form = new GF_UnitTest_Factory_For_Form( $this );

		$this->view = new GV_UnitTest_Factory_For_View( $this );

		$this->post = new WP_UnitTest_Factory_For_Post();
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
		$fields = isset( $args['fields'] ) ? serialize( $args['fields'] ) : 'a:1:{s:23:"directory_table-columns";a:3:{s:13:"535d63d1488b0";a:9:{s:2:"id";s:1:"4";s:5:"label";s:13:"Business Name";s:10:"show_label";s:1:"1";s:12:"custom_label";s:0:"";s:12:"custom_class";s:0:"";s:12:"show_as_link";s:1:"0";s:13:"search_filter";s:1:"0";s:13:"only_loggedin";s:1:"0";s:17:"only_loggedin_cap";s:4:"read";}s:13:"535d63d379a3c";a:9:{s:2:"id";s:2:"12";s:5:"label";s:20:"Business Description";s:10:"show_label";s:1:"1";s:12:"custom_label";s:0:"";s:12:"custom_class";s:0:"";s:12:"show_as_link";s:1:"0";s:13:"search_filter";s:1:"0";s:13:"only_loggedin";s:1:"0";s:17:"only_loggedin_cap";s:4:"read";}s:13:"535d63dc735a6";a:9:{s:2:"id";s:1:"2";s:5:"label";s:7:"Address";s:10:"show_label";s:1:"1";s:12:"custom_label";s:0:"";s:12:"custom_class";s:0:"";s:12:"show_as_link";s:1:"0";s:13:"search_filter";s:1:"0";s:13:"only_loggedin";s:1:"0";s:17:"only_loggedin_cap";s:4:"read";}}}';

		$insert_post_response = parent::create_object( $args );

		if( $insert_post_response && ! is_wp_error( $insert_post_response ) ) {

			$view_meta = array(
				'_gravityview_form_id' => $form_id,
				'_gravityview_template_settings' => $settings,
				'_gravityview_directory_template' => $template_id,
				'_gravityview_directory_widgets' => 'a:0:{}',
				'_gravityview_directory_fields' => $fields,
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

class GF_UnitTest_Generator_Number extends GF_UnitTest_Generator {

	var $number_format = true;
	var $decimals = 0;
	var $low = -10000000000;
	var $high = 10000000000;

	/**
	 * GF_UnitTest_Generator_Number constructor.
	 *
	 * @param bool $number_format
	 * @param int $decimals
	 * @param int $low
	 * @param int $high
	 */
	public function __construct( $number_format = true, $decimals = false, $low = -10000000000, $high = 10000000000 ) {
		$this->number_format = $number_format;

		if( is_bool( $decimals )) {
			$this->decimals = $decimals ? mt_rand( 0, 10 ) : '';
		} else if ( is_int( $decimals ) ) {
			$this->decimals = $decimals;
		}

		$this->low           = $low;
		$this->high          = $high;
	}

	function next() {
		$number = mt_rand( $this->low, $this->high );
		$generated = gravityview_number_format( $number, $this->decimals  );
		return $generated;
	}
}

abstract class GF_UnitTest_Generator {

	static private $instances = array();

	public static function get_instance() {

		$class_name = get_called_class();

		if( ! isset( self::$instances[ $class_name ] ) ) {
			$function_args = func_get_args();
			self::$instances[ $class_name ] = new $class_name( $function_args );
		}

		return self::$instances[ $class_name ];
	}

	public static function get() {
		$function_args = func_get_args();

		/** @noinspection PhpUndefinedMethodInspection */
		return self::get_instance( $function_args )->next();
	}

	function __toString() {
		return $this->next();
	}

	abstract function next();

}

class GF_UnitTest_Generator_IP extends GF_UnitTest_Generator {

	function next() {
		return long2ip( mt_rand() );
	}
}

abstract class GF_UnitTest_Generator_Array extends GF_UnitTest_Generator {

	var $possible_items = array();

	function next() {
		$key = mt_rand( 0, count( $this->possible_items ) - 1 );
		return $this->possible_items[ $key ];
	}
}

class GF_UnitTest_Generator_Payment_Status extends GF_UnitTest_Generator_Array {
	var $possible_items = array(
		'Active',
		'Paid',
		'Processing',
		'Failed',
		'Cancelled',
	);
}

class GF_UnitTest_Generator_Status extends GF_UnitTest_Generator_Array {
	var $possible_items = array(

		'active',
		'active',
		'active', // Weight the "active" value

		'trash',
		'spam',
		'delete',
	);
}

class GF_UnitTest_Generator_User_Agent extends GF_UnitTest_Generator_Array {

	var $possible_items = array(
			'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.85 Safari/537.36',
			'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36',
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:34.0) Gecko/20100101 Firefox/34.0',
			'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0',
			'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/5.0)',
			'Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53 (compatible; bingbot/2.0; http://www.bing.com/bingbot.htm)',
			'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; Media Center PC',
			'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0',
			'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.112 Safari/535.1',
			'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:30.0) Gecko/20100101 Firefox/30.0',
			'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
			'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; rv:11.0) like Gecko',
			'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
			'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0; Trident/5.0)',
			'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0',
	);
}

class GF_UnitTest_Generator_Date extends GF_UnitTest_Generator {

	var $format = 'Y-m-d H:i:s';

	function __construct( $format = 'Y-m-d H:i:s' ) {
		if( is_string( $format ) ) {
			$this->format = $format;
		}
	}

	function next() {
		$hour = mt_rand( 0, 24 );
		$minute = mt_rand( 0, 60 );
		$second = mt_rand( 0, 60 );
		$month = mt_rand( 1, 12 );
		$day = mt_rand( 1, 28 );
		$year = mt_rand( 2000, (int)date('Y') );
		$time = mktime( $hour, $minute, $second, $month, $day, $year);
		return date( $this->format, $time );
	}
}

class GF_UnitTest_Generator_Date_Created extends GF_UnitTest_Generator_Date {

	var $format = 'Y-m-d H:i:s';

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
			'ip' => GF_UnitTest_Generator_IP::get(),
			'source_url' => 'http://example.com/wordpress/?gf_page=preview&id=16',
			'user_agent' => GF_UnitTest_Generator_User_Agent::get(),
			'payment_status' => GF_UnitTest_Generator_Payment_Status::get(),
			'payment_date' => '2014-08-29 20:55:06',
			'payment_amount' => '0.01',
			'transaction_id' => 'asdfpaoj442gpoagfadf',
			'created_by' => 1,
			'status' => GF_UnitTest_Generator_Status::get(),
			'date_created' => GF_UnitTest_Generator_Date_Created::get(),
		);
	}

	function create_object( $args ) {

		$args = wp_parse_args( $args, $this->default_generation_definitions );

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
			'title' => 'Form Title %s',
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

	function create_object( $args = array() ) {
		$args = wp_parse_args( $args, $this->default_generation_definitions );
		$title_sequence = new WP_UnitTest_Generator_Sequence( $args['title'] );
		$args['title'] = $title_sequence->next();
		return GFAPI::add_form( $args );
	}

	function get_object_by_id( $object_id ) {
		return GFAPI::get_form( $object_id );
	}

	function update_object( $object, $fields ) {}
}