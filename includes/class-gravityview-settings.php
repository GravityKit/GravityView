<?php

if( ! class_exists('GFAddOn') ) {
	return;
}

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
	protected $_capabilities_uninstall = 'gravityview_uninstall';

	/**
	 * @var string|array A string or an array of capabilities or roles that have access to the settings page
	 */
	protected $_capabilities_app_settings = 'gravityview_view_settings';

	/**
	 * @var string|array A string or an array of capabilities or roles that have access to the settings page
	 */
	protected $_capabilities_app_menu = 'gravityview_view_settings';

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
		 * Prevent Gravity Forms from showing the uninstall tab on the settings page
		 * @hack
		 */
		if( $caps === $this->_capabilities_uninstall ) {
			return false;
		}

		if( empty( $caps ) ) {
			$caps = array( 'gravityview_full_access' );
		}

		return GVCommon::has_cap( $caps );
	}

	/**
	 * Run actions when initializing admin
	 *
	 * Triggers the license key notice
	 *
	 * @return void
	 */
	function init_admin() {

		$this->_load_license_handler();

		$this->license_key_notice();

		add_filter( 'gform_addon_app_settings_menu_gravityview', array( $this, 'modify_app_settings_menu_title' ) );

		/** @since 1.7.6 */
		add_action('network_admin_menu', array( $this, 'add_network_menu' ) );

		parent::init_admin();
	}

	/**
	 * Change the settings page header title to "GravityView"
	 *
	 * @param $setting_tabs
	 *
	 * @return array
	 */
	public function modify_app_settings_menu_title( $setting_tabs ) {

		$setting_tabs[0]['label'] = __( 'GravityView Settings', 'gravityview');

		return $setting_tabs;
	}

	/**
	 * Load license handler in admin-ajax.php
	 */
	public function init_ajax() {
		$this->_load_license_handler();
	}

	/**
	 * Make sure the license handler is available
	 */
	private function _load_license_handler() {

		if( !empty( $this->License_Handler ) ) {
			return;
		}

		require_once( GRAVITYVIEW_DIR . 'includes/class-gv-license-handler.php');

		$this->License_Handler = GV_License_Handler::get_instance( $this );
	}

	/**
	 * Display a notice if the plugin is inactive.
	 * @return void
	 */
	function license_key_notice() {

		// Only show on GravityView pages
		if( ! gravityview_is_admin_page() ) {
			return;
		}

		$license_status = self::getSetting('license_key_status');
		$license_id = self::getSetting('license_key');
		$license_id = empty( $license_id ) ? 'license' : $license_id;

		$message = esc_html__('Your GravityView license %s. This means you&rsquo;re missing out on updates and support! %sActivate your license%s or %sget a license here%s.', 'gravityview');

		/**
		 * I wanted to remove the period from after the buttons in the string,
		 * but didn't want to mess up the translation strings for the translators.
		 */
		$message = mb_substr( $message, 0, mb_strlen( $message ) - 1 );
		$title = __('Inactive License', 'gravityview');
		$status = '';
		$update_below = false;
		$primary_button_link = admin_url( 'edit.php?post_type=gravityview&amp;page=gravityview_settings' );
		switch ( $license_status ) {
			/** @since 1.17 */
			case 'expired':
				$title = __('Expired License', 'gravityview');
				$status = 'expired';
				$message = $this->get_license_handler()->strings( 'expired', self::getSetting('license_key_response') );
				break;
			case 'invalid':
				$title = __('Invalid License', 'gravityview');
				$status = __('is invalid', 'gravityview');
				break;
			case 'deactivated':
				$status = __('is inactive', 'gravityview');
				$update_below = __('Activate your license key below.', 'gravityview');
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case '':
				$license_status = 'site_inactive';
				// break intentionally left blank
			case 'inactive':
			case 'site_inactive':
				$status = __('has not been activated', 'gravityview');
				$update_below = __('Activate your license key below.', 'gravityview');
				break;
		}
		$url = 'https://gravityview.co/pricing/?utm_source=admin_notice&utm_medium=admin&utm_content='.$license_status.'&utm_campaign=Admin%20Notice';

		// Show a different notice on settings page for inactive licenses (hide the buttons)
		if( $update_below && gravityview_is_admin_page( '', 'settings' ) ) {
			$message = sprintf( $message, $status, '<div class="hidden">', '', '', '</div><a href="#" onclick="jQuery(\'#license_key\').focus(); return false;">' . $update_below . '</a>' );
		} else {
			$message = sprintf( $message, $status, "\n\n" . '<a href="' . $primary_button_link . '" class="button button-primary">', '</a>', '<a href="' . esc_url( $url ) . '" class="button button-secondary">', '</a>' );
		}

		if( !empty( $status ) ) {
			GravityView_Admin_Notices::add_notice( array(
				'message' => $message,
				'class'	=> 'updated',
				'title' => $title,
				'cap' => 'gravityview_edit_settings',
				'dismiss' => sha1( $license_status.'_'.$license_id ),
			));
		}
	}

	/**
	 * Register styles in the app admin page
	 * @return array
	 */
	public function styles() {

		$styles = parent::styles();

		$styles[] = array(
			'handle'  => 'gravityview_settings',
			'src'     => plugins_url( 'assets/css/admin-settings.css', GRAVITYVIEW_FILE ),
			'version' => GravityView_Plugin::version,
			"deps" => array(
				'gaddon_form_settings_css'
			),
			'enqueue' => array(
				array( 'admin_page' => array(
					'app_settings'
				) ),
			)
		);

		return $styles;
	}

	/**
	 * Add global Settings page for Multisite
	 * @since 1.7.6
	 * @return void
	 */
	public function add_network_menu() {
		if( GravityView_Plugin::is_network_activated() ) {
			add_menu_page( __( 'Settings', 'gravityview' ), __( 'GravityView', 'gravityview' ), $this->_capabilities_app_settings, "{$this->_slug}_settings", array( $this, 'app_tab_page' ), 'none' );
		}
	}

	/**
	 * Add Settings link to GravityView menu
	 * @return void
	 */
	public function create_app_menu() {

		/**
		 * If not multisite, always show.
		 * If multisite and the plugin is network activated, show; we need to register the submenu page for the Network Admin settings to work.
		 * If multisite and not network admin, we don't want the settings to show.
		 * @since 1.7.6
		 */
		$show_submenu = !is_multisite() ||  is_main_site() || !GravityView_Plugin::is_network_activated() || ( is_network_admin() && GravityView_Plugin::is_network_activated() );

		/**
		 * Override whether to show the Settings menu on a per-blog basis.
		 * @since 1.7.6
		 * @param bool $hide_if_network_activated Default: true
		 */
		$show_submenu = apply_filters( 'gravityview/show-settings-menu', $show_submenu );

		if( $show_submenu ) {
			add_submenu_page( 'edit.php?post_type=gravityview', __( 'Settings', 'gravityview' ), __( 'Settings', 'gravityview' ), $this->_capabilities_app_settings, $this->_slug . '_settings', array( $this, 'app_tab_page' ) );
		}
	}

	/**
	 * The Settings title
	 * @return string
	 */
	public function app_settings_title() {
		return null;
	}

	/**
	 * Prevent displaying of any icon
	 * @return string
	 */
	public function app_settings_icon() {
		return '<i></i>';
	}

	/**
	 * Make protected public
	 * @inheritDoc
	 * @access public
	 */
	public function get_app_setting( $setting_name ) {

		/**
		 * Backward compatibility with Redux
		 */
		if( $setting_name === 'license' ) {
			return array(
				'license' => parent::get_app_setting( 'license_key' ),
				'status' => parent::get_app_setting( 'license_key_status' ),
				'response' => parent::get_app_setting( 'license_key_response' ),
			);
		}

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

	/**
	 * Allow public access to the GV_License_Handler class
	 * @since 1.7.4
	 *
	 * @return GV_License_Handler
	 */
	public function get_license_handler() {
		return $this->License_Handler;
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

		$field['type']  = ( isset($field['type']) && in_array( $field['type'], array('submit','reset','button') ) ) ? $field['type'] : 'submit';

		$attributes    = $this->get_field_attributes( $field );
		$default_value = rgar( $field, 'value' ) ? rgar( $field, 'value' ) : rgar( $field, 'default_value' );
		$value         = $this->get_setting( $field['name'], $default_value );


		$attributes['class'] = isset( $attributes['class'] ) ? esc_attr( $attributes['class'] ) : 'button-primary gfbutton';
		$name    = ( $field['name'] === 'gform-settings-save' ) ? $field['name'] : '_gaddon_setting_'.$field['name'];

		if ( empty( $value ) ) {
			$value = __( 'Update Settings', 'gravityview' );
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
	 * Allow customizing the Save field parameters
	 *
	 * @param array $field
	 * @param bool $echo
	 *
	 * @return string
	 */
	public function settings_save( $field, $echo = true ) {
		$field['type']  = 'submit';
		$field['name']  = 'gform-settings-save';
		$field['class'] = isset( $field['class'] ) ? $field['class'] : 'button-primary gfbutton';

		if ( ! rgar( $field, 'value' ) )
			$field['value'] = __( 'Update Settings', 'gravityview' );

		$output = $this->settings_submit( $field, false );

		if( $echo ) {
			echo $output;
		}

		return $output;
	}

	/**
	 * The same as the parent, except added support for field descriptions
	 * @inheritDoc
	 * @param $field array
	 */
	public function single_setting_label( $field ) {

		parent::single_setting_label( $field );

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
			'support_port' => '1',
			'delete-on-uninstall' => '0',
		);

		return $defaults;
	}

	/**
	 * Check for the `gravityview_edit_settings` capability before saving plugin settings.
	 * Gravity Forms says you're able to edit if you're able to view settings. GravityView allows two different permissions.
	 *
	 * @since 1.15
	 * @return void
	 */
	public function maybe_save_app_settings() {

		if ( $this->is_save_postback() ) {
			if ( ! GVCommon::has_cap( 'gravityview_edit_settings' ) ) {
				$_POST = array(); // If you don't reset the $_POST array, it *looks* like the settings were changed, but they weren't
				GFCommon::add_error_message( __( 'You don\'t have the ability to edit plugin settings.', 'gravityview' ) );
				return;
			}
		}

		parent::maybe_save_app_settings();
	}

	/**
	 * When the settings are saved, make sure the license key matches the previously activated key
	 *
	 * @return array settings from parent::get_posted_settings(), with `license_key_response` and `license_key_status` potentially unset
	 */
	public function get_posted_settings() {

		$posted_settings = parent::get_posted_settings();

		$local_key = rgar( $posted_settings, 'license_key' );
		$response_key = rgars( $posted_settings, 'license_key_response/license_key' );

		// If the posted key doesn't match the activated/deactivated key (set using the Activate License button, AJAX response),
		// then we assume it's changed. If it's changed, unset the status and the previous response.
		if( $local_key !== $response_key ) {

			unset( $posted_settings['license_key_response'] );
			unset( $posted_settings['license_key_status'] );
			GFCommon::add_error_message( __('The license key you entered has been saved, but not activated. Please activate the license.', 'gravityview' ) );
		}

		return $posted_settings;
	}

	/**
	 * Gets the required indicator
	 * Gets the markup of the required indicator symbol to highlight fields that are required
	 *
	 * @param $field - The field meta.
	 *
	 * @return string - Returns markup of the required indicator symbol
	 */
	public function get_required_indicator( $field ) {
		return '<span class="required" title="' . esc_attr__( 'Required', 'gravityview' ) . '">*</span>';
	}

	/**
	 * Specify the settings fields to be rendered on the plugin settings page
	 * @return array
	 */
	public function app_settings_fields() {

		$default_settings = $this->get_default_settings();

		$disabled_attribute = GVCommon::has_cap( 'gravityview_edit_settings' ) ? false : 'disabled';

		$fields = apply_filters( 'gravityview_settings_fields', array(
			array(
				'name'                => 'license_key',
				'required'               => true,
				'label'             => __( 'License Key', 'gravityview' ),
				'description'          => __( 'Enter the license key that was sent to you on purchase. This enables plugin updates &amp; support.', 'gravityview' ),
				'type'              => 'edd_license',
				'data-pending-text' => __('Verifying license&hellip;', 'gravityview'),
				'default_value'           => $default_settings['license_key'],
				'class'             => ( '' == $this->get_app_setting( 'license_key' ) ) ? 'activate code regular-text edd-license-key' : 'deactivate code regular-text edd-license-key',
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
			/**
			 * @since 1.15 Added Support Port support
			 */
			array(
				'name'         => 'support_port',
				'type'       => 'radio',
				'label'      => __( 'Show Support Port?', 'gravityview' ),
				'default_value'    => $default_settings['support_port'],
				'horizontal' => 1,
				'choices'    => array(
					array(
						'label' => _x('Show', 'Setting: Show or Hide', 'gravityview'),
						'value' => '1',
					),
					array(
						'label' => _x('Hide', 'Setting: Show or Hide', 'gravityview'),
						'value' => '0',
					),
				),
				'tooltip' => '<p><img src="' . esc_url_raw( plugins_url('assets/images/screenshots/beacon.png', GRAVITYVIEW_FILE ) ) . '" alt="' . esc_attr__( 'The Support Port looks like this.', 'gravityview' ) . '" class="alignright" style="max-width:40px; margin:.5em;" />' . esc_html__('The Support Port provides quick access to how-to articles and tutorials. For administrators, it also makes it easy to contact support.', 'gravityview') . '</p>',
				'description'   => __( 'Show the Support Port on GravityView pages?', 'gravityview' ),
			),
			array(
				'name'         => 'no-conflict-mode',
				'type'       => 'radio',
				'label'      => __( 'No-Conflict Mode', 'gravityview' ),
				'default_value'    => $default_settings['no-conflict-mode'],
				'horizontal' => 1,
				'choices'    => array(
					array(
						'label' => _x('On', 'Setting: On or off', 'gravityview'),
						'value' => '1',
					),
					array(
						'label' => _x('Off', 'Setting: On or off', 'gravityview'),
						'value' => '0',
					),
				),
				'description'   => __( 'Set this to ON to prevent extraneous scripts and styles from being printed on GravityView admin pages, reducing conflicts with other plugins and themes.', 'gravityview' ) . ' ' . __('If your Edit View tabs are ugly, enable this setting.', 'gravityview'),
			),
			array(
				'name'       => 'delete-on-uninstall',
				'type'       => 'radio',
				'label'      => __( 'Remove Data on Delete?', 'gravityview' ),
				'default_value'    => $default_settings['delete-on-uninstall'],
				'horizontal' => 1,
				'choices'    => array(
					array(
						'label' => _x( 'Keep GravityView Data', 'Setting: what to do when uninstalling plugin', 'gravityview' ),
						'value' => '0',
						'tooltip' => sprintf( '<h6>%s</h6><p>%s</p>', __( 'Keep GravityView content and settings', 'gravityview' ), __( 'If you delete then re-install the plugin, all GravityView data will be kept. Views, settings, etc. will be untouched.', 'gravityview' ) ),
					),
					array(
						'label' => _x( 'Permanently Delete', 'Setting: what to do when uninstalling plugin', 'gravityview' ),
						'value' => 'delete',
					    'tooltip' => sprintf( '<h6>%s</h6><p><span class="howto">%s</span></p><p>%s</p>', __( 'Delete all GravityView content and settings', 'gravityview' ), __( 'If you delete then re-install GravityView, it will be like installing GravityView for the first time.', 'gravityview' ), __( 'When GravityView is uninstalled and deleted, delete all Views, GravityView entry approvals, GravityView-generated entry notes (including approval and entry creator changes), and GravityView plugin settings. No Gravity Forms data will be touched.', 'gravityview' ) ),
					),
				),
				'description'   => sprintf( __( 'Should GravityView content and entry approval status be removed from the site when the GravityView plugin is deleted?', 'gravityview' ), __( 'Permanently Delete', 'gravityview' ) ),
			),

		) );



		/**
		 * Redux backward compatibility
		 * @since 1.7.4
		 */
		foreach ( $fields as &$field ) {
			$field['name']          = isset( $field['name'] ) ? $field['name'] : rgget('id', $field );
			$field['label']         = isset( $field['label'] ) ? $field['label'] : rgget('title', $field );
			$field['default_value'] = isset( $field['default_value'] ) ? $field['default_value'] : rgget('default', $field );
			$field['description']   = isset( $field['description'] ) ? $field['description'] : rgget('subtitle', $field );

			if( $disabled_attribute ) {
				$field['disabled']  = $disabled_attribute;
			}
		}


        $sections = array(
            array(
                'description' =>      sprintf( '<span class="version-info description">%s</span>', sprintf( __('You are running GravityView version %s', 'gravityview'), GravityView_Plugin::version ) ),
                'fields'      => $fields,
            )
        );

        // custom 'update settings' button
        $button = array(
            'class' => 'button button-primary button-hero',
            'type'     => 'save',
        );

		if( $disabled_attribute ) {
			$button['disabled'] = $disabled_attribute;
		}


        /**
         * @filter `gravityview/settings/extension/sections` Modify the GravityView settings page
         * Extensions can tap in here to insert their own section and settings.
         * <code>
         *   $sections[] = array(
         *      'title' => __( 'GravityView My Extension Settings', 'gravityview' ),
         *      'fields' => $settings,
         *   );
         * </code>
         * @param array $extension_settings Empty array, ready for extension settings!
         */
        $extension_sections = apply_filters( 'gravityview/settings/extension/sections', array() );

		// If there are extensions, add a section for them
		if ( ! empty( $extension_sections ) ) {

			if( $disabled_attribute ) {
				foreach ( $extension_sections as &$section ) {
					foreach ( $section['fields'] as &$field ) {
						$field['disabled'] = $disabled_attribute;
					}
				}
			}

            $k = count( $extension_sections ) - 1 ;
            $extension_sections[ $k ]['fields'][] = $button;
			$sections = array_merge( $sections, $extension_sections );
		} else {
            // add the 'update settings' button to the general section
            $sections[0]['fields'][] = $button;
        }

		return $sections;
	}

	/**
	 * Get the setting for GravityView by name
	 *
	 * @param  string $key     Option key to fetch
	 *
	 * @return mixed
	 */
	static public function getSetting( $key ) {
		return self::get_instance()->get_app_setting( $key );
	}

}

GravityView_Settings::get_instance();