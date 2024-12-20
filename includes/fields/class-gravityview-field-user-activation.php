<?php
/**
 * User Activation field for Gravityforms User Registration Add-On.
 *
 * @package GravityView
 * @subpackage GravityView_Field_User_Activation
 */
class GravityView_Field_User_Activation extends GravityView_Field {
	var $name = 'user_activation';

	var $group = 'gravityview';

	var $contexts = array( 'single', 'multiple' );

	var $icon = 'dashicons-unlock';

	public function __construct() {

		$this->label = esc_attr__( 'User Activation', 'gk-gravityview' );

		$this->description = esc_attr__( 'Activate and deactivate users.', 'gk-gravityview' );

		$this->add_hooks();

		parent::__construct();
	}

	public function add_hooks() {
		add_filter( 'gravityview_field_entry_value_' . $this->name . '_pre_link', array( $this, 'get_content' ), 10, 4 );

		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts_and_styles' ) );

		// Make sure scripts are registered for FSE themes
		add_action( 'gravityview/template/before', array( $this, 'register_scripts_and_styles' ) );

		add_action( 'gravityview/field/user_activation/load_scripts', array( $this, 'enqueue_and_localize_script' ) );

		add_action( 'gravityview_datatables_scripts_styles', array( $this, 'enqueue_and_localize_script' ) );
	}

	/**
	 * Enqueues and localizes the script
	 *
	 * @since 2.33
	 */
	public function enqueue_and_localize_script() {
		// The script is already registered and enqueued
		if ( wp_script_is( 'gv-user-activation', 'enqueued' ) ) {
			return;
		}
		wp_enqueue_script( 'gv-user-activation' );

		wp_localize_script(
			'gv-user-activation',
			'gvUserActivation',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'gf_user_activate' ),
				'success_message' => esc_html__( 'User Activated Successfully!', 'gk-gravityview' ),
				'confirm_message' => esc_html__( 'Are you sure you want to activate this user?', 'gk-gravityview' ),
				'spinner_url'     => GFCommon::get_base_url() . '/images/spinner.svg',
			)
		);
	}

	/**
	 * Registers the scripts and styles
	 *
	 * @since 2.33
	 */
	public function register_scripts_and_styles() {
		if ( wp_script_is( 'gv-user-activation' ) ) {
			return;
		}

		$script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script( 'gv-user-activation', GRAVITYVIEW_URL . 'assets/js/field-user-activation' . $script_debug . '.js', array( 'jquery' ), GV_PLUGIN_VERSION, true );
	}

	/**
	 * Gets the content of the field
	 *
	 * @since 2.33
	 *
	 * @param string $output
	 * @param array  $entry
	 * @param array  $field_settings
	 * @param array  $field
	 *
	 * @return string
	 */
	public function get_content( $output = '', $entry = array(), $field_settings = array(), $field = array() ) {
		/** Overridden by a template. */
		if ( ! empty( $field['field_path'] ) ) {
			return $output;
		}

		return $output;
	}

	/**
	 * Checks if the user exists
	 *
	 * @since 2.33
	 *
	 * @param array $form
	 * @param array $entry
	 *
	 * @return bool
	 */
	public static function check_if_user_exist( $form, $entry ) {
		foreach ( $form['fields'] as $field ) {
			if ( $field->type !== 'email' ) {
				continue;
			}

			$email = rgar( $entry, $field->id );
			if ( $email ) {
				return email_exists( $email );
			}
		}
		
		return false;
	}

	/**
	 * Checks if the activation key is valid
	 *
	 * @since 2.33
	 *
	 * @param string $activation_key
	 *
	 * @return WP_Error|true
	 */
	public static function check_activation_key( $activation_key ) {
		global $wpdb;

		$signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}signups WHERE activation_key = %s", $activation_key ) );

		if ( empty( $signup ) ) {
			return new WP_Error( 'invalid_key', __( 'Invalid activation key', 'gk-gravityview' ) );
		}

		if ( $signup->active ) {
			return new WP_Error( 'already_active', __( 'The user is already active', 'gk-gravityview' ) );
		}

		return true;
	}

	/**
	 * Checks if the feeds are valid and have the user activation value set to manual.
	 *
	 * @since 2.33
	 *
	 * @param int $form_id
	 *
	 * @return bool
	 */
	public static function check_if_feeds_are_valid( $form_id ) {
		$valid = false;
		$feeds = GFAPI::get_feeds( null, $form_id );
		if ( empty( $feeds ) ) {
			return $valid;
		}

		foreach ( $feeds as $feed ) {
			if ( ( isset( $feed['is_active'] ) && 1 !== (int) $feed['is_active'] ) || !isset( $feed['addon_slug'] ) || 'gravityformsuserregistration' !== $feed['addon_slug'] ) {
				continue;
			}

			if ( 'manual' === $feed['meta']['userActivationValue'] ) {
				$valid = true;
				break;
			}
		}

		return $valid;
	}

}

if ( class_exists( 'GF_User_Registration_Bootstrap' ) ) {
	new GravityView_Field_User_Activation();
}
