<?php

if ( ! class_exists( 'GFAddOn' ) ) {
	return;
}

/**
 * GravityView Settings class (get/set/license validation) using the Gravity Forms App framework
 * @since 1.7.4 (Before, used the Redux Framework)
 * @deprecated Use gravityview()->plugin->settings
 */
class GravityView_Settings {
	/**
	 * @deprecated Use gravityview()->plugin->settings
	 * @return \GV\Global_Settings
	 */
	private function __construct() {}
	private function __wakeup() {}
	private function __clone() {}

	/**
	 * @deprecated Use gravityview()->plugin->settings
	 * @return \GV\Addon_Settings
	 */
	public static function get_instance() {
		gravityview()->log->warning( '\GravityView_Settings is deprecated. Use gravityview()->settings instead.' );
		return gravityview()->plugin->settings;
	}

	/**
	 * Display a notice if the plugin is inactive.
	 * @return void
	 */
	function license_key_notice() {

		$license_status = self::getSetting('license_key_status');
		$license_key = self::getSetting('license_key');
		if( '' === $license_key ) {
			$license_status = 'inactive';
        }
		$license_id = empty( $license_key ) ? 'license' : $license_key;

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
			$message = sprintf( $message, $status, "\n\n" . '<a href="' . esc_url( $primary_button_link ) . '" class="button button-primary">', '</a>', '<a href="' . esc_url( $url ) . '" class="button button-secondary">', '</a>' );
		}

		if( !empty( $status ) ) {
			GravityView_Admin_Notices::add_notice( array(
				'message' => $message,
				'class'   => 'updated',
				'title'   => $title,
				'cap'     => 'gravityview_edit_settings',
				'dismiss' => sha1( $license_status . '_' . $license_id . '_' . date( 'z' ) ), // Show every day, instead of every 8 weeks (which is the default)
			) );
		}
	}

	/**
     * Add tooltip script to app settings page. Not enqueued by Gravity Forms for some reason.
     *
     * @since 1.21.5
     *
     * @see GFAddOn::scripts()
     *
	 * @return array Array of scripts
	 */
	public function scripts() {
		$scripts = parent::scripts();

		$scripts[] = array(
			'handle'  => 'gform_tooltip_init',
			'enqueue' => array(
                array(
			        'admin_page' => array( 'app_settings' )
                )
            )
		);

		return $scripts;
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
                'gform_admin',
				'gaddon_form_settings_css',
                'gform_tooltip',
                'gform_font_awesome',
			),
			'enqueue' => array(
				array( 'admin_page' => array(
					'app_settings',
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

		/**
		 * If not multisite, always show.
		 * If multisite and the plugin is network activated, show; we need to register the submenu page for the Network Admin settings to work.
		 * If multisite and not network admin, we don't want the settings to show.
		 * @since 1.7.6
		 */
		$show_submenu = !is_multisite() ||  is_main_site() || !gravityview()->plugin->is_network_activated() || ( is_network_admin() && GravityView_Plugin::is_network_activated() );

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

	    if ( defined( 'GRAVITYVIEW_LICENSE_KEY' ) && GRAVITYVIEW_LICENSE_KEY ) {
		    $field['input_type'] = 'password';
        }

		$text = $this->settings_text( $field, false );

		$activation = $this->License_Handler->settings_edd_license_activation( $field, false );

		$return = $text . $activation;

		if( $echo ) {
			echo $return;
		}

		return $return;
	}

	/**
	 * Allow public access to the GV\License_Handler class
	 * @since 1.7.4
	 *
	 * @return GV\License_Handler
	 */
	public function get_license_handler() {
		return $this->License_Handler;
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

		if ( ! \GV\Utils::get( $field, 'value' ) ) {
			$field['value'] = __( 'Update Settings', 'gravityview' );
		}

		$output = $this->settings_submit( $field, false );

		ob_start();
		$this->app_settings_uninstall_tab();
		$output .= ob_get_clean();

		if( $echo ) {
			echo $output;
		}

		return $output;
	}


	/**
     * Keep GravityView styling for `$field['description']`, even though Gravity Forms added support for it
     *
     * Converts `$field['description']` to `$field['gv_description']`
     * Converts `$field['subtitle']` to `$field['description']`
     *
     * @see GravityView_Settings::single_setting_label Converts `gv_description` back to `description`
     * @see http://share.gravityview.co/P28uGp/2OIRKxog for image that shows subtitle vs description
     *
     * @since 1.21.5.2
     *
	 * @param array $field
     *
     * @return void
	 */
	public function single_setting_row( $field ) {

		$field['gv_description'] = \GV\Utils::get( $field, 'description' );
		$field['description']    = \GV\Utils::get( $field, 'subtitle' );

		parent::single_setting_row( $field );
	}

	/**
	 * The same as the parent, except added support for field descriptions
	 * @inheritDoc
	 * @param $field array
	 */
	public function single_setting_label( $field ) {

		parent::single_setting_label( $field );

		if ( $description = \GV\Utils::get( $field, 'gv_description' ) ) {
			echo '<span class="description">'. $description .'</span>';
		}
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

		$local_key = \GV\Utils::get( $posted_settings, 'license_key' );
		$response_key = \GV\Utils::get( $posted_settings, 'license_key_response/license_key' );

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
				'description'          => __( 'Enter the license key that was sent to you on purchase. This enables plugin updates &amp; support.', 'gravityview' ) . $this->get_license_handler()->license_details( $this->get_app_setting( 'license_key_response' ) ),
				'type'              => 'edd_license',
				'disabled'          => ( defined( 'GRAVITYVIEW_LICENSE_KEY' )  && GRAVITYVIEW_LICENSE_KEY ),
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
				'tooltip' => '<p><img src="' . esc_url_raw( plugins_url('assets/images/beacon.png', GRAVITYVIEW_FILE ) ) . '" alt="' . esc_attr__( 'The Support Port looks like this.', 'gravityview' ) . '" class="alignright" style="max-width:40px; margin:.5em;" />' . esc_html__('The Support Port provides quick access to how-to articles and tutorials. For administrators, it also makes it easy to contact support.', 'gravityview') . '</p>',
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
				'name'       => 'beta',
				'type'       => 'checkbox',
				'label'      => __( 'Become a Beta Tester', 'gravityview' ),
				'default_value'    => $default_settings['beta'],
				'horizontal' => 1,
				'choices'    => array(
					array(
						'label' => _x('Show me beta versions if they are available.', 'gravityview'),
						'value' => '1',
                        'name'  => 'beta',
					),
				),
				'description'   => __( 'You will have early access to the latest GravityView features and improvements. There may be bugs! If you encounter an issue, help make GravityView better by reporting it!', 'gravityview'),
			),
		) );



		/**
		 * Redux backward compatibility
		 * @since 1.7.4
		 */
		foreach ( $fields as &$field ) {
			$field['name']          = isset( $field['name'] ) ? $field['name'] : \GV\Utils::_GET( 'id', \GV\Utils::get( $field, 'id' ) );
			$field['label']         = isset( $field['label'] ) ? $field['label'] : \GV\Utils::_GET( 'title', \GV\Utils::get( $field, 'title' ) );
			$field['default_value'] = isset( $field['default_value'] ) ? $field['default_value'] : \GV\Utils::_GET( 'default', \GV\Utils::get( $field, 'default' ) );
			$field['description']   = isset( $field['description'] ) ? $field['description'] : \GV\Utils::_GET( 'subtitle', \GV\Utils::get( $field, 'subtitle' ) );

			if( $disabled_attribute ) {
				$field['disabled']  = $disabled_attribute;
			}

			if( empty( $field['disabled'] ) ) {
				unset( $field['disabled'] );
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
