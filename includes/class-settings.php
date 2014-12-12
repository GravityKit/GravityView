<?php


if (!class_exists('GravityView_Settings')) {

	class GravityView_Settings {

		public $args        = array();
		public $sections    = array();
		public $ReduxFramework;

		public function __construct() {

			require_once( GRAVITYVIEW_DIR . 'includes/lib/redux-framework/redux-framework.php');

			// Add the EDD extension to Redux
			add_action( "redux/extensions/gravityview_settings", array($this, 'register_edd_extension') );

			add_filter( 'redux/gravityview_settings/field/class/edd_license/strings', array($this, 'edd_settings_strings') );

			if (!class_exists('ReduxFramework')) { return; }

			add_action('plugins_loaded', array($this, 'initSettings'), 10);

			if( !gravityview_is_admin_page() ) { return; }

			// Show license notice on all GV pages, except for settings page
			if( !gravityview_is_admin_page( '', 'settings' ) ) {
				add_action('plugins_loaded', array($this, 'license_key_notice'), 20);
			}

			// Disable Redux tracking script
			update_option( 'redux-framework-tracking', array( 'allow_tracking' => false ) );

			add_action('admin_enqueue_scripts', array($this, '_enqueue'));
			add_action('gravityview_remove_conflicts_after', array($this, '_enqueue'));

		}

		/**
		 * Add the EDD License settings field.
		 * @param  ReduxFramework $ReduxFramework ReduxFramework object
		 * @return void
		 */
		public function register_edd_extension($ReduxFramework) {

			require_once( GRAVITYVIEW_DIR . 'includes/lib/edd-redux-extension/extension_edd.php');

			$extension = new ReduxFramework_extension_edd($ReduxFramework);

			if( !gravityview_is_admin_page( '', 'settings' ) ) {
				// Remove the scripts; these will be added back in later by the `_enqueue()` method
				remove_action('admin_enqueue_scripts', array( $extension, 'enqueue_scripts') );
			}

		}

		/**
		 * Add the Redux scripts back in on settings page.
		 * @return [type] [description]
		 */
		public function _enqueue() {
			global $plugin_page;

			// We only want to show the settings scripts on the settings page.
			if( !gravityview_is_admin_page( '', 'settings' ) ) {
				return;
			}

			// Hide the sidebar and the sidebar toggle button in the settings.
			wp_enqueue_style( 'gravityview_settings', plugins_url( 'includes/css/admin-settings.css', GRAVITYVIEW_FILE ) );

			get_redux_instance('gravityview_settings')->_enqueue();

		}

		public function initSettings() {

			if( !is_admin() ) { return; }

			// Set the default arguments
			$this->setArguments();

			// Create the sections and fields
			$this->setSections();

			if (!isset($this->args['opt_name'])) { // No errors please
				return;
			}

			// Then populate properly.
			$this->ReduxFramework = new ReduxFramework($this->sections, $this->args);
		}

		function license_key_notice() {

			$license = $this->getSetting('license', array( 'license' => NULL, 'status' => NULL ));

			$license_status = empty( $license['status'] ) ? 'site_inactive' : $license['status'];
			$license_id = empty( $license['license'] ) ? 'license' : $license['license'];

			$message = esc_html__('Your GravityView license %s. This means you&rsquo;re missing out on updates and support! %sActivate your license%s or %sget a license here%s.', 'gravityview');

			$title = __('Inactive License', 'gravityview');
			$status = '';

			switch ( $license_status ) {
				case 'invalid':
					$title = __('Invalid License', 'gravityview');
					$status = __('is invalid', 'gravityview');
					break;
				case 'deactivated':
					$status = __('is inactive', 'gravityview');
					break;
				case 'site_inactive':
					$status = __('has not been activated', 'gravityview');
					break;
			}

			$message = sprintf( $message, $status, '<a href="'.admin_url( 'edit.php?post_type=gravityview&amp;page=settings' ).'">', '</a>', '<a href="https://gravityview.co/pricing/">', '</a>' );

			if( !empty( $status ) ) {

				GravityView_Admin::add_notice( array(
					'message' => $message,
					'class'	=> 'updated',
					'title' => $title,
					'dismiss' => sha1( $license_status.'_'.$license_id ),
				));

			}

		}

		/**
		 * Override the text used in the Redux Framework EDD field extension
		 * @param  array $strings array of content
		 * @return array          Modified array of content
		 */
		public function edd_settings_strings( $strings ) {

			$new_strings = array(
				'status' => esc_html__('Status', 'gravityview'),
				'error' => esc_html__('There was an error processing the request.', 'gravityview'),
				'failed'  => esc_html__('Could not deactivate the license. The submitted license key may not be active.', 'gravityview'),
				'site_inactive' => esc_html__('Not Activated', 'gravityview'),
				'no_activations_left' => esc_html__('Invalid; this license has reached its activation limit.', 'gravityview'),
				'deactivated' => esc_html__('Deactivated', 'gravityview'),
				'valid' => esc_html__('Valid', 'gravityview'),
				'invalid' => esc_html__('Not Valid', 'gravityview'),
				'missing' => esc_html__('Not Valid', 'gravityview'),
				'revoked' => esc_html__('The license key has been revoked.', 'gravityview'),
				'expired' => esc_html__('The license key has expired.', 'gravityview'),

				'verifying_license' => esc_html__('Verifying license&hellip;', 'gravityview'),
				'activate_license' => esc_html__('Activate License', 'gravityview'),
				'deactivate_license' => esc_html__('Deactivate License', 'gravityview'),
				'check_license' => esc_html__('Verify License', 'gravityview'),
			);

			return $new_strings;
		}

		public function setSections() {

			ob_start();

			// Set the default license in wp-config.php
			$default_license = defined( 'GRAVITYVIEW_LICENSE_KEY' ) ? GRAVITYVIEW_LICENSE_KEY : '';

			$fields = apply_filters( 'gravityview_settings_fields', array(
					array(
						'id'        => 'license',
						'type'      => 'edd_license',
						'remote_api_url' => 'https://gravityview.co',
						'author'	=> 'Katz Web Services, Inc.',
						'default'	=> array(
							'license' => $default_license,
							'status' => ''
						),
						'item_name'	=> 'GravityView',
						'version'	=> GravityView_Plugin::version,
						'mode'		=> 'plugin',
						'path'		=> GRAVITYVIEW_FILE,
						'title'     => __('License Key', 'gravityview'),
						'subtitle'  => __('Enter the license key that was sent to you on purchase. This enables plugin updates &amp; support.', 'gravityview'),
					),
					array(
						'id'        => 'support-email',
						'type'      => 'text',
						'validate'	=> 'email',
						'default'   => get_bloginfo( 'admin_email' ),
						'title'     => __('Support Email', 'gravityview'),
						'subtitle'  => __('In order to provide responses to your support requests, please provide your email address.', 'gravityview'),
					),
					array(
						'id'        => 'no-conflict-mode',
						'type'      => 'switch',
						'title'     => __('No-Conflict Mode', 'gravityview'),
						'subtitle'  => __('Set this to ON to prevent extraneous scripts and styles from being printed on GravityView admin pages, reducing conflicts with other plugins and themes.', 'gravityview'),
					)
			));

			// Extensions can tap in here.
			$extension_fields = apply_filters( 'gravityview_extension_fields', array() );

			// If there are extensions, add a section for them
			if( !empty( $extension_fields ) ) {
				array_unshift( $extension_fields, array(
					'title' => 'GravityView Extension Settings',
					'id'        => 'gravityview-extensions-header',
					'type'      => 'section',
					'indent'	=> false,
				));
			}

			$this->sections[] = array(
				'title'     => __('GravityView Settings', 'gravityview'),
				'icon'      => 'el-icon-home',
				'fields'    => array_merge( $fields, $extension_fields )
			);

			do_action( 'gravityview_settings_sections', $this );

		}

		/**
		 * Get a setting.
		 *
		 * @param  string $key     Option key to fetch
		 * @param  mixed $default Default if value at key is not set.
		 * @return mixed          The setting
		 */
		static public function getSetting($key, $default = NULL) {

			$instance = get_redux_instance('gravityview_settings');

			// Fix fatal error when loading a TinyMCE extension by making sure it's callable
			if( is_callable( array($instance, 'get') ) ) {
				return $instance->get($key, $default);
			}
		}

		public function setArguments() {

			$this->args = array(
				'opt_name'          => 'gravityview_settings',            // This is where your data is stored in the database and also becomes your global variable name.
				'display_name'      => 'GravityView',     // Name that appears at the top of your panel
				'display_version'   => GravityView_Plugin::version,  // Version that appears at the top of your panel
				'menu_type'         => 'submenu',                  //Specify if the admin menu should appear or not. Options: menu or submenu (Under appearance only)
				'allow_sub_menu'    => true,                    // Show the sections below the admin menu item or not
				'menu_title'        => __('Settings', 'gravityview'),
				'page_title'        => __('Settings', 'gravityview'),

				'async_typography'  => false,                    // Use a asynchronous font on the front end or font string
				'admin_bar'         => false,                    // Show the panel pages on the admin bar
				'global_variable'   => '',                      // Set a different name for your global variable other than the opt_name
				'dev_mode'          => false,                    // Show the time the page took to load, etc
				'customizer'        => false,                    // Enable basic customizer support

				// OPTIONAL -> Give you extra features
				'page_priority'     => null,                    // Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
				'page_parent'       => 'edit.php?post_type=gravityview',
				'page_permissions'  => apply_filters( 'gravityview_settings_capability' , 'manage_options' ),        // Permissions needed to access the options panel.
				'menu_icon'         => '',                      // Specify a custom URL to an icon
				'last_tab'          => '',                      // Force your panel to always open to a specific tab (by id)
				'page_icon'         => 'icon-themes',           // Icon displayed in the admin panel next to your menu_title
				'page_slug'         => 'settings',              // Page slug used to denote the panel
				'save_defaults'     => true,                    // On load save the defaults to DB before user clicks save or not
				'default_show'      => false,                   // If true, shows the default value next to each field that is not the default value.
				'default_mark'      => '',                      // What to print by the field's title if the value shown is default. Suggested: *
				'show_import_export' => false,                   // Shows the Import/Export panel when not used as a field.

				// HINTS
				'hints' => array(
					'icon'          => 'icon-question-sign',
					'icon_position' => 'right',
					'icon_color'    => 'lightgray',
					'icon_size'     => 'normal',
					'tip_style'     => array(
						'color'         => 'light',
						'shadow'        => true,
						'rounded'       => false,
						'style'         => '',
					),
					'tip_position'  => array(
						'my' => 'top left',
						'at' => 'bottom right',
					),
					'tip_effect'    => array(
						'show'          => array(
							'effect'        => 'slide',
							'duration'      => '500',
							'event'         => 'mouseover',
						),
						'hide'      => array(
							'effect'    => 'slide',
							'duration'  => '500',
							'event'     => 'click mouseleave',
						),
					),
				)
			);

			$this->args['share_icons'] = array(
				array(
					'url'   => 'https://twitter.com/Gravity_View',
					'title' => 'Follow us on Twitter',
					'icon'  => 'el-icon-twitter'
				),
				array(
					'url'   => 'https://www.facebook.com/GravityView',
					'title' => 'Like us on Facebook',
					'icon'  => 'el-icon-facebook'
				),
				array(
					'url'   => 'https://plus.google.com/115639371871185834833/about',
					'title' => __('Follow us on Google+', 'gravityview' ),
					'icon' => 'el-icon-googleplus',
				),
			);
		}

	}

	new GravityView_Settings;
}
