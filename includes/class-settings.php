<?php

/**
 * GravityView Settings class (get/set/license validation) using the Gravity Forms App framework
 * @since 1.7.4 (Before, used the Redux Framework)
 */
class GravityView_Settings extends GFAddOn {

	/**
	 * @var string Version number of the Add-On
	 */
	protected $_version = GravityView_Plugin::version;
	/**
	 * @var string Gravity Forms minimum version requirement
	 */
	protected $_min_gravityforms_version = GV_MIN_GF_VERSION;

	/**
	 * @var string Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
	 */
	protected $_title = 'GravityView';

	/**
	 * @var string Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
	 */
	protected  $_short_title = 'GravityView';

	/**
	 * @var string URL-friendly identifier used for form settings, add-on settings, text domain localization...
	 */
	protected $_slug = 'gravityview';

	/**
	 * @var string|array A string or an array of capabilities or roles that can uninstall the plugin
	 */
	protected $_capabilities_uninstall = 'gravityview_gfaddon_uninstall';

	/**
	 * @var string The hook suffix for the app menu
	 */
	public  $app_hook_suffix = 'gravityview';

	/**
	 * @var GV_License_Handler Process license validation
	 */
	private $License_Handler;

	/**
	 * @var GravityView_Settings
	 */
	private static $instance;

	/**
	 * We're not able to set the __construct() method to private because we're extending the GFAddon class, so
	 * we fake it. When called using `new GravityView_Settings`, it will return get_instance() instead. We pass
	 * 'get_instance' as a test string.
	 *
	 * @see get_instance()
	 *
	 * @param string $prevent_multiple_instances
	 */
	public function __construct( $prevent_multiple_instances = '' ) {

		if( $prevent_multiple_instances === 'get_instance' ) {
			return parent::__construct();
		}

		return self::get_instance();
	}

	/**
	 * @return GravityView_Settings
	 */
	public static function get_instance() {

		if( empty( self::$instance ) ) {
			self::$instance = new self( 'get_instance' );
		}

		return self::$instance;
	}

	/**
	 * Before initialization
	 *
	 * @inheritDoc
	 */
	public function pre_init() {

		require_once( GRAVITYVIEW_DIR . 'includes/class-gv-license-handler.php');

		$this->License_Handler = GV_License_Handler::get_instance( $this );

		$this->_capabilities_app_settings = apply_filters( 'gravityview_settings_capability' , 'manage_options' );
	}

	/**
	 * Should the Uninstall tab be shown?
	 * @return bool
	 */
	protected function show_uninstall_tab() {
		return false;
	}

	/**
	 * Prevent uninstall tab from being shown by returning false for the uninstall capability check. Otherwise:
	 * @inheritDoc
	 *
	 * @hack
	 *
	 * @param array|string $caps
	 *
	 * @return bool
	 */
	public function current_user_can_any( $caps ) {

		/**
		 * Don't show uninstall tab
		 * @hack
		 */
		if( $caps === $this->_capabilities_uninstall && false === $this->show_uninstall_tab() ) {
			return false;
		}

		return parent::current_user_can_any( $caps );
	}

	function init_admin() {
		parent::init_admin();
	}

	/**
	 * Register styles in the app admin page
	 * @return array
	 */
	public function styles() {

		$styles = parent::styles();

		/**
		 * Fix a reported bug that GF Apps don't have the CSS file enqueued properly
		 */
		foreach( $styles as &$style ) {
			if( $style['handle'] === 'gaddon_form_settings_css' ) {
				$style['enqueue']['admin_page'][] = 'app_settings';
			}
		}

		$styles[] = array(
			'handle'  => 'gravityview_settings',
			'src'     => plugins_url( 'assets/css/admin-settings.css', GRAVITYVIEW_FILE ),
			'version' => GravityView_Plugin::version,
			'enqueue' => array(
				array( 'admin_page' => array(
					'app_settings'
				) ),
			)
		);

		return $styles;
	}

	/**
	 * Add Settings link to GravityView menu
	 * @return void
	 */
	public function create_app_menu() {
		add_submenu_page( 'edit.php?post_type=gravityview', __( 'Settings', 'gravityview' ), __( 'Settings', 'gravityview' ), $this->_capabilities_app_settings, $this->_slug . '_settings', array( $this, 'app_tab_page' ) );
	}

	/**
	 * The Settings title
	 * @return string
	 */
	public function app_settings_title() {
		return __('GravityView Settings', 'gravityview');
	}

	/**
	 * Add the Floaty icon to the settings title
	 * @return string
	 */
	public function app_settings_icon() {
		return '<i class="gv-icon-astronaut-head"></i>';
	}

	/**
	 * Make protected public
	 * @inheritDoc
	 * @access public
	 */
	public function get_app_setting( $setting_name ) {
		return parent::get_app_setting( $setting_name );
	}

	/**
	 * Returns the currently saved plugin settings
	 *
	 * Different from GFAddon in two ways:
	 * 1. Makes protected method public
	 * 2. Use default settings if the original settings don't exist
	 *
	 * @access public
	 *
	 * @return array
	 */
	public function get_app_settings() {
		return get_option( 'gravityformsaddon_' . $this->_slug . '_app_settings', $this->get_default_settings() );
	}


	/**
	 * Updates app settings with the provided settings
	 *
	 * Same as the GVAddon, except it returns the value from update_option()
	 *
	 * @param array $settings - App settings to be saved
	 *
	 * @return boolean False if value was not updated and true if value was updated.
	 */
	public function update_app_settings( $settings ) {
		return update_option( 'gravityformsaddon_' . $this->_slug . '_app_settings', $settings );
	}

	/**
	 * Make protected public
	 * @inheritDoc
	 * @access public
	 */
	public function set_field_error( $field, $error_message = '' ) {
		parent::set_field_error( $field, $error_message );
	}

	/**
	 * Register the settings field for the EDD License field type
	 * @param array $field
	 * @param bool $echo Whether to echo the
	 *
	 * @return string
	 */
	protected function settings_edd_license( $field, $echo = true ) {

		$text = self::settings_text( $field, false );

		$activation = $this->License_Handler->settings_edd_license_activation( $field, false );

		$return = $text . $activation;

		if( $echo ) {
			echo $return;
		}

		return $return;
	}

	/***
	 * Renders the save button for settings pages
	 *
	 * @param array $field - Field array containing the configuration options of this field
	 * @param bool  $echo  = true - true to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string The HTML
	 */
	public function settings_submit( $field, $echo = true ) {

		$field['type']  = ( isset($field['type']) && in_array( $field['type'], array('submit','reset') ) ) ? $field['type'] : 'submit';

		$attributes    = $this->get_field_attributes( $field );
		$default_value = rgar( $field, 'value' ) ? rgar( $field, 'value' ) : rgar( $field, 'default_value' );
		$value         = $this->get_setting( $field['name'], $default_value );


		$attributes['class'] = isset( $attributes['class'] ) ? esc_attr( $attributes['class'] ) : 'button-primary gfbutton';
		$name    = ( $field['name'] === 'gform-settings-save' ) ? $field['name'] : '_gaddon_setting_'.$field['name'];

		if ( empty( $value ) ) {
			$value = __( 'Update Settings', 'gravityforms' );
		}

		$attributes = $this->get_field_attributes( $field );

		$html = '<input
                    type="' . $field['type'] . '"
                    name="' . esc_attr( $name ) . '"
                    value="' . $value . '" ' .
		        implode( ' ', $attributes ) .
		        ' />';

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Gets the invalid field icon
	 * Returns the markup for an alert icon to indicate and highlight invalid fields.
	 *
	 * @param array $field - The field meta.
	 *
	 * @return string - The full markup for the icon
	 */
	public function get_error_icon( $field ) {

		$error = $this->get_field_errors( $field );

		$icon = '<i class="fa fa-exclamation-circle icon-exclamation-sign gf_invalid"></i>';

		$error = '<div class="error">' . $error .'</div>';

		return $icon . $error;
	}

	/**
	 * The same as the parent, except added support for field descriptions
	 * @inheritDoc
	 * @param $field array
	 */
	public function single_setting_label( $field ) {

		echo $field['label'];


		if ( isset( $field['tooltip'] ) ) {
			echo ' ' . gform_tooltip( $field['tooltip'], rgar( $field, 'tooltip_class' ), true );
		}

		if ( rgar( $field, 'required' ) ) {
			echo ' ' . $this->get_required_indicator( $field );
		}

		// Added by GravityView
		if ( isset( $field['description'] ) ) {
			echo '<span class="description">'. $field['description'] .'</span>';
		}

	}

	/**
	 * Get the default settings for the plugin
	 *
	 * Merges previous settings created when using the Redux Framework
	 *
	 * @return array Settings with defaults set
	 */
	private function get_default_settings() {

		$defaults = array(
			// Set the default license in wp-config.php
			'license_key' => defined( 'GRAVITYVIEW_LICENSE_KEY' ) ? GRAVITYVIEW_LICENSE_KEY : '',
			'license_key_response' => '',
			'license_key_status' => '',
			'support-email' => get_bloginfo( 'admin_email' ),
			'no-conflict-mode' => '0',
		);

		return $defaults;
	}

	/**
	 * Specify the settings fields to be rendered on the plugin settings page
	 * @return array
	 */
	public function app_settings_fields() {

		$default_settings = $this->get_default_settings();

		$fields = apply_filters( 'gravityview_settings_fields', array(
			array(
				'name'                => 'license_key',
				'label'             => __( 'License Key', 'gravityview' ),
				'description'          => __( 'Enter the license key that was sent to you on purchase. This enables plugin updates &amp; support.', 'gravityview' ),
				'type'              => 'edd_license',
				'data-pending-text' => __('Verifying license&hellip;', 'gravityview'),
				'default_value'           => $default_settings['license_key'],
				'feedback_callback' => array( $this->License_Handler, 'validate_license_key' ),
				'class'             => ( '' == $this->get_app_setting( 'license_key' ) ) ? 'activate code regular-text' : 'deactivate code regular-text edd-license-key',
			),
			array(
				'name'       => 'license_key_response',
				'default_value'  => $default_settings['license_key_response'],
				'type'     => 'hidden',
			),
			array(
				'name'       => 'license_key_status',
				'default_value'  => $default_settings['license_key_status'],
				'type'     => 'hidden',
			),
			array(
				'name'       => 'support-email',
				'type'     => 'text',
				'validate' => 'email',
				'default_value'  => $default_settings['support-email'],
				'label'    => __( 'Support Email', 'gravityview' ),
				'description' => __( 'In order to provide responses to your support requests, please provide your email address.', 'gravityview' ),
				'class'    => 'code regular-text',
			),
			array(
				'name'         => 'no-conflict-mode',
				'type'       => 'radio',
				'label'      => __( 'No-Conflict Mode', 'gravityview' ),
				'default_value'    => $default_settings['no-conflict-mode'],
				'horizontal' => 1,
				'choices'    => array(
					array(
						'label' => 'On',
						'value' => 1
					),
					array(
						'label' => 'Off',
						'value' => 0,
					),
				),
				'description'   => __( 'Set this to ON to prevent extraneous scripts and styles from being printed on GravityView admin pages, reducing conflicts with other plugins and themes.', 'gravityview' ),
			)
		) );

		// Extensions can tap in here.
		$extension_fields = apply_filters( 'gravityview_extension_fields', array() );

		// If there are extensions, add a section for them
		if ( ! empty( $extension_fields ) ) {
			array_unshift( $extension_fields, array(
				'title'  => 'GravityView Extension Settings',
				'id'     => 'gravityview-extensions-header',
				'type'   => 'section',
				'indent' => false,
			) );
		}

		$fields = array_merge( $fields, $extension_fields );

		/**
		 * Redux backward compatibility
		 */
		foreach ( $fields as &$field ) {
			$field['name']          = isset( $field['name'] ) ? $field['name'] : rgget('id', $field );
			$field['label']         = isset( $field['label'] ) ? $field['label'] : rgget('title', $field );
			$field['default_value'] = isset( $field['default_value'] ) ? $field['default_value'] : rgget('default', $field );
			$field['description']   = isset( $field['description'] ) ? $field['description'] : rgget('subtitle', $field );
		}

		$sections = array(
			array(
				'title'       => '',
				'description' => '',
				'id'          => '',
				'class'       => '',
				'style'       => '',
				'fields'      => $fields,
			)
		);

		return $sections;
	}

	/**
	 * Get the setting for GravityView by name
	 *
	 * @deprecated 1.7.4 Now you should use `GravityView_Settings::get_instance()->get_app_setting( $key );` instead
	 *
	 * @param  string $key     Option key to fetch
	 *
	 * @return mixed
	 */
	static public function getSetting( $key ) {
		return self::get_instance()->get_app_setting( $key );
	}

}

new GravityView_Settings;