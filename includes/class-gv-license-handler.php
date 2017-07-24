<?php

class GV_License_Handler {

	/**
	 * @var GravityView_Settings
	 */
	private $Addon;

	const name = 'GravityView';

	const author = 'Katz Web Services, Inc.';
	
	const url = 'https://gravityview.co';
	
	const version = GravityView_Plugin::version;

	/**
	 * Post ID on gravityview.co
	 * @since 1.15
	 */
	const item_id = 17;

	/**
	 * Name of the transient used to store license status for GV
	 * @since 1.17
	 */
	const status_transient_key = 'gravityview_edd-activate_valid';

	/**
	 * @var string Key used to store active GravityView/Gravity Forms plugin data
	 * @since 1.15
	 */
	const related_plugins_key = 'gravityview_related_plugins';

	/** @var EDD_SL_Plugin_Updater */
	private $EDD_SL_Plugin_Updater;

	/**
	 * @var GV_License_Handler
	 */
	public static $instance;

	/**
	 * @param GravityView_Settings $GFAddOn
	 *
	 * @return GV_License_Handler
	 */
	public static function get_instance( GravityView_Settings $GFAddOn ) {
		if( empty( self::$instance ) ) {
			self::$instance = new self( $GFAddOn );
		}
		return self::$instance;
	}
	
	private function __construct( GravityView_Settings $GFAddOn ) {

		$this->Addon = $GFAddOn;

		$this->add_hooks();
	}

	private function add_hooks() {
		add_action( 'admin_init', array( $this, 'setup_edd' ), 0 );
		add_action( 'wp_ajax_gravityview_license', array( $this, 'license_call' ) );
		add_action( 'admin_init', array( $this, 'refresh_license_status' ) );
		add_action( 'admin_init', array( $this, 'check_license' ) );
		add_action( 'update_option_active_plugins', array( $this, 'flush_related_plugins_transient' ) );
		add_action( 'update_option_active_sitewide_plugins', array( $this, 'flush_related_plugins_transient' ) );
	}

	/**
	 * When a plugin is activated or deactivated, delete the cached extensions/plugins used by get_related_plugins_and_extensions()
	 *
	 * @see get_related_plugins_and_extensions()
	 * @since 1.15
	 */
	public function flush_related_plugins_transient() {
		if ( function_exists( 'delete_site_transient' ) ) {
			delete_site_transient( self::related_plugins_key );
		}
	}

	/**
	 * Check the GravityView license information
	 *
	 * @since 1.19.3
	 *
	 * @param bool $force Whether to force checking license, even if AJAX
	 *
	 * @return void
	 */
	public function check_license() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return; // Don't fire when saving settings or AJAX
		}

		if ( ! apply_filters( 'gv_send_site_data', true ) ) {
			return;
		}

		// Send checkins once per week
		$last_checked = get_option( 'gv_last_checkin', false );

		if ( is_numeric( $last_checked ) && $last_checked > strtotime( '-1 week', current_time( 'timestamp' ) ) ) {
			return; // checked within a week
		}

		$status = get_transient( 'gv_license_check' );

		// Run the license check a maximum of once per day, and not on GV website
		if ( false === $status && site_url() !== self::url ) {

			// Call the custom API.
			$response = wp_remote_post( self::url, array(
				'timeout'   => 15,
			    'sslverify' => false,
			    'body'      =>  array(
				    'edd_action' => 'check_license',
				    'license'    => trim( $this->Addon->get_app_setting( 'license_key' ) ),
				    'item_name'  => self::name,
				    'url'        => home_url(),
				    'site_data'  => $this->get_site_data(),
			    ),
			));

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {

				// Connection failed, try again in three hours
				set_transient( 'gv_license_check', 1, 3 * HOUR_IN_SECONDS );

				return;
			}

			set_transient( 'gv_license_check', 1, DAY_IN_SECONDS );

			update_option( 'gv_last_checkin', current_time( 'timestamp' ) );
		}
	}

	/**
	 * When the status transient expires (or is deleted on activation), re-check the status
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	public function refresh_license_status() {

		if ( defined('DOING_AJAX') && DOING_AJAX ) {
			return;
		}

		// The transient is fresh; don't fetch.
		if( $status = get_transient( self::status_transient_key ) ) {
			return;
		}

		$data = array(
			'edd_action' => 'check_license',
			'license' => trim( $this->Addon->get_app_setting( 'license_key' ) ),
			'update' => true,
			'format' => 'object',
			'all_caps' => true,
			'field_id' => 'refresh_license_status', // Required to set the `status_transient_key` transient
		);

		$license_call = GravityView_Settings::get_instance()->get_license_handler()->license_call( $data );

		do_action( 'gravityview_log_debug', __METHOD__ . ': Refreshed the license.', $license_call );
	}

	/**
	 * Retrieves site data (plugin versions, integrations, etc) to be sent along with the license check.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @return array
	 */
	public function get_site_data() {

		$data = array();

		$theme_data = wp_get_theme();
		$theme      = $theme_data->Name . ' ' . $theme_data->Version;

		$data['gv_version']  = GravityView_Plugin::version;
		$data['php_version']  = phpversion();
		$data['wp_version']   = get_bloginfo( 'version' );
		$data['gf_version']  = GFForms::$version;
		$data['server']       = isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '';
		$data['multisite']    = is_multisite();
		$data['theme']        = $theme;
		$data['url']          = home_url();
		$data['license_key']  = GravityView_Settings::get_instance()->get_app_setting( 'license_key' );
		$data['beta']         = GravityView_Settings::get_instance()->get_app_setting( 'beta' );

		// View Data
		$gravityview_posts = get_posts('numberposts=-1&post_type=gravityview&post_status=publish&order=ASC');

		if ( ! empty( $gravityview_posts ) ) {
			$first = array_shift( $gravityview_posts );
			$latest = array_pop( $gravityview_posts );
			$data['view_count'] = count( $gravityview_posts );
			$data['view_first'] = $first->post_date;
			$data['view_latest'] = $latest->post_date;
		}

		// Form counts
		if ( class_exists( 'GFFormsModel' ) ) {
			$form_data = GFFormsModel::get_form_count();
			$data['forms_total'] = rgar( $form_data, 'total', 0 );
			$data['forms_active'] = rgar( $form_data, 'active', 0 );
			$data['forms_inactive'] = rgar( $form_data, 'inactive', 0 );
			$data['forms_trash'] = rgar( $form_data, 'inactive', 0 );
		}

		// Retrieve current plugin information
		if( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$data['integrations']     = self::get_related_plugins_and_extensions();
		$data['active_plugins']   = get_option( 'active_plugins', array() );
		$data['inactive_plugins'] = array();
		$data['locale']           = get_locale();

		// Validate request on the GV server
		$data['hash']             = 'gv_version.url.locale:' . sha1( $data['gv_version'] . $data['url'] . $data['locale'] );

		return $data;
	}

	/**
	 * Get active GravityView Extensions and Gravity Forms Add-ons to help debug issues.
	 *
	 * @since 1.15
	 * @return string List of active extensions related to GravityView or Gravity Forms, separated by HTML line breaks
	 */
	static public function get_related_plugins_and_extensions( $implode = '<br />' ) {

		if ( ! function_exists( 'wp_get_active_and_valid_plugins' ) ) {
			return 'Running < WP 3.0';
		}

		$extensions = get_site_transient( self::related_plugins_key );

		if ( empty( $extensions ) ) {

			$active_plugins = wp_get_active_and_valid_plugins();
			$extensions = array();
			foreach ( $active_plugins as $active_plugin ) {

				// Match gravityview, gravity-forms, gravityforms, gravitate
				if ( ! preg_match( '/(gravityview|gravity-?forms|gravitate)/ism', $active_plugin ) ) {
					continue;
				}

				$plugin_data = get_plugin_data( $active_plugin );

				$extensions[] = sprintf( '%s %s', $plugin_data['Name'], $plugin_data['Version'] );
			}

			if( ! empty( $extensions ) ) {
				set_site_transient( self::related_plugins_key, $extensions, HOUR_IN_SECONDS );
			} else {
				return 'There was an error fetching related plugins.';
			}
		}

		return $implode ? implode( $implode, $extensions ) : $extensions;
	}

	function settings_edd_license_activation( $field, $echo ) {

		$script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'gv-admin-edd-license', GRAVITYVIEW_URL . 'assets/js/admin-edd-license' . $script_debug . '.js', array( 'jquery' ) );

		$status = trim( $this->Addon->get_app_setting( 'license_key_status' ) );
		$key = trim( $this->Addon->get_app_setting( 'license_key' ) );

		if( !empty( $key ) ) {
			$response = $this->Addon->get_app_setting( 'license_key_response' );
			$response = is_array( $response ) ? (object) $response : json_decode( $response );
		} else {
			$response = array();
		}

		wp_localize_script( 'gv-admin-edd-license', 'GVGlobals', array(
			'license_box' => $this->get_license_message( $response )
		));


		$fields = array(
			array(
				'name'  => 'edd-activate',
				'value' => __('Activate License', 'gravityview'),
				'data-pending_text' => __('Verifying license&hellip;', 'gravityview'),
				'data-edd_action' => 'activate_license',
				'class' => 'button-primary',
			),
			array(
				'name'  => 'edd-deactivate',
				'value' => __('Deactivate License', 'gravityview'),
				'data-pending_text' => __('Deactivating license&hellip;', 'gravityview'),
				'data-edd_action' => 'deactivate_license',
				'class' => ( empty( $status ) ? 'button-primary hide' : 'button-primary' ),
			),
			array(
				'name'  => 'edd-check',
				'value' => __('Check License', 'gravityview'),
				'data-pending_text' => __('Verifying license&hellip;', 'gravityview'),
				'title' => 'Check the license before saving it',
				'data-edd_action' => 'check_license',
				'class' => 'button-secondary',
			),
		);


		$class = 'button gv-edd-action';

		$class .= ( !empty( $key ) && $status !== 'valid' ) ? '' : ' hide';

		$disabled_attribute = GVCommon::has_cap( 'gravityview_edit_settings' ) ? false : 'disabled';

		$submit = '<div class="gv-edd-button-wrapper">';
		foreach ( $fields as $field ) {
			$field['type'] = 'button';
			$field['class'] = isset( $field['class'] ) ? $field['class'] . ' '. $class : $class;
			$field['style'] = 'margin-left: 10px;';
			if( $disabled_attribute ) {
				$field['disabled'] = $disabled_attribute;
			}
			$submit .= $this->Addon->settings_submit( $field, $echo );
		}
		$submit .= '</div>';

		return $submit;
	}

	/**
	 * Include the EDD plugin updater class, if not exists
	 *
	 * @since 1.7.4
	 * @since 1.21.5.3 Changed visibility of method to public
	 *
	 * @return void
	 */
	public function setup_edd() {

		if( !class_exists('EDD_SL_Plugin_Updater') ) {
			require_once( GRAVITYVIEW_DIR . 'includes/lib/EDD_SL_Plugin_Updater.php');
		}

		// setup the updater
		$this->EDD_SL_Plugin_Updater = new EDD_SL_Plugin_Updater(
			self::url,
			GRAVITYVIEW_FILE,
			$this->_get_edd_settings()
		);

	}

	/**
	 * Generate the array of settings passed to the EDD license call
	 *
	 * @since 1.7.4
	 *
	 * @param string $action The action to send to edd, such as `check_license`
	 * @param string $license The license key to have passed to EDD
	 *
	 * @return array
	 */
	function _get_edd_settings( $action = '', $license = '' ) {

		// retrieve our license key from the DB
		$license_key = empty( $license ) ? trim( $this->Addon->get_app_setting( 'license_key' ) ) : $license;

		$settings = array(
			'version'   => self::version,
			'license'   => $license_key,
			'item_name' => self::name,
			'item_id'   => self::item_id,
			'author'    => self::author,
			'language'  => get_locale(),
			'url'       => home_url(),
		    'beta'      => $this->Addon->get_app_setting( 'beta' ),
		);

		if( !empty( $action ) ) {
			$settings['edd_action'] = esc_attr( $action );
		}

		$settings = array_map( 'urlencode', $settings );

		return $settings;
	}

	/**
	 * Perform the call
	 * @return array|WP_Error
	 */
	private function _license_get_remote_response( $data, $license = '' ) {

		$api_params = $this->_get_edd_settings( $data['edd_action'], $license );

		$url = add_query_arg( $api_params, self::url );

		$response = wp_remote_get( $url, array(
			'timeout'   => 15,
			'sslverify' => false,
		));

		if ( is_wp_error( $response ) ) {

			do_action( 'gravityview_log_error', 'WP_Error response from license check. API params:', $api_params );

			return array();
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// Not JSON
		if ( empty( $license_data ) ) {

			do_action( 'gravityview_log_error', 'Empty license data response from license check', compact( 'response', 'url', 'api_params', 'data' ) );

			delete_transient( self::status_transient_key );

			// Change status
			return array();
		}

		// Store the license key inside the data array
		$license_data->license_key = $license;

		return $license_data;
	}

	/**
	 * Generate the status message displayed in the license field
	 *
	 * @since 1.7.4
	 * @param $license_data
	 *
	 * @return string
	 */
	function get_license_message( $license_data ) {

		if( empty( $license_data ) ) {
			$message = '';
		} else {

			if( ! empty( $license_data->error ) ) {
				$class = 'error';
				$string_key = $license_data->error;
			} else {
				$class = $license_data->license;
				$string_key = $license_data->license;
			}

			$message = sprintf( '<p><strong>%s: %s</strong></p>', $this->strings('status'), $this->strings( $string_key, $license_data ) );

			$message = $this->generate_license_box( $message, $class );
		}

		return $message;
	}

	/**
	 * Generate the status message box HTML based on the current status
	 *
	 * @since 1.7.4
	 * @param $message
	 * @param string $class
	 *
	 * @return string
	 */
	private function generate_license_box( $message, $class = '' ) {

		$template = '<div id="gv-edd-status" aria-live="polite" aria-busy="false" class="gv-edd-message inline %s">%s</div>';

		$output = sprintf( $template, esc_attr( $class ), $message );

		return $output;
	}

	/**
	 * Allow pure HTML in settings fields
	 *
	 * @since 1.17
	 *
	 * @param array $response License response
	 *
	 * @return string `html` key of the $field
	 */
	public function license_details( $response = array() ) {

		$response = (array) $response;

		$return = '';
		$wrapper = '<span class="gv-license-details" aria-live="polite" aria-busy="false">%s</span>';

		if( ! empty( $response['license_key'] ) ) {

			$return .= '<h3>' . esc_html__( 'License Details:', 'gravityview' ) . '</h3>';

			if ( in_array( rgar( $response, 'license' ), array( 'invalid', 'deactivated' ) ) ) {
				$return .= $this->strings( $response['license'], $response );
			} elseif ( ! empty( $response['license_name'] ) ) {

				$response_keys = array(
					'license_name'   => '',
					'license_limit'  => '',
					'customer_name'  => '',
					'customer_email' => '',
					'site_count'     => '',
					'expires'        => '',
					'upgrades'       => ''
				);

				// Make sure all the keys are set
				$response = wp_parse_args( $response, $response_keys );

				$login_link = sprintf( '<a href="%s" class="howto" rel="external">%s</a>', esc_url( sprintf( 'https://gravityview.co/wp-login.php?username=%s', $response['customer_email'] ) ), esc_html__( 'Access your GravityView account', 'gravityview' ) );
				$local_text = ( ! empty( $response['is_local'] ) ? '<span class="howto">' . __( 'This development site does not count toward license activation limits', 'gravityview' ) . '</span>' : '' );
				$details    = array(
					'license'     => sprintf( esc_html__( 'License level: %s', 'gravityview' ), esc_html( $response['license_name'] ), esc_html( $response['license_limit'] ) ),
					'licensed_to' => sprintf( esc_html_x( 'Licensed to: %1$s (%2$s)', '1: Customer name; 2: Customer email', 'gravityview' ), esc_html__( $response['customer_name'], 'gravityview' ), esc_html__( $response['customer_email'], 'gravityview' ) ) . $login_link,
					'activations' => sprintf( esc_html__( 'Activations: %d of %s sites', 'gravityview' ), intval( $response['site_count'] ), esc_html( $response['license_limit'] ) ) . $local_text,
					'expires'     => 'lifetime' === $response['expires'] ? '' : sprintf( esc_html__( 'Renew on: %s', 'gravityview' ), date_i18n( get_option( 'date_format' ), strtotime( $response['expires'] ) - DAY_IN_SECONDS ) ),
					'upgrade'     => $this->get_upgrade_html( $response['upgrades'] ),
				);

				if ( ! empty( $response['error'] ) && 'expired' === $response['error'] ) {
					unset( $details['upgrade'] );
					$details['expires'] = '<div class="error inline"><p>' . $this->strings( 'expired', $response ) . '</p></div>';
				}

				$return .= '<ul><li>' . implode( '</li><li>', array_filter( $details ) ) . '</li></ul>';
			}
		}

		return sprintf( $wrapper, $return );
	}

	/**
	 * Display possible upgrades for a license
	 *
	 * @since 1.17
	 *
	 * @param array $upgrades Array of upgrade paths, returned from the GV website
	 *
	 * @return string HTML list of upgrades available for the current license
	 */
	function get_upgrade_html( $upgrades ) {

		$output = '';

		if( ! empty( $upgrades ) ) {

			$locale_parts = explode( '_', get_locale() );

			$is_english = ( 'en' === $locale_parts[0] );

			$output .= '<h4>' . esc_html__( 'Upgrades available:', 'gravityview' ) . '</h4>';

			$output .= '<ul class="ul-disc">';

			foreach ( $upgrades as $upgrade_id => $upgrade ) {

				$upgrade = (object) $upgrade;

				$anchor_text = sprintf( esc_html_x( 'Upgrade to %1$s for %2$s', '1: GravityView upgrade name, 2: Cost of upgrade', 'gravityview' ), esc_attr( $upgrade->name ), esc_attr( $upgrade->price ) );

				if( $is_english && isset( $upgrade->description ) ) {
					$message = esc_html( $upgrade->description );
				} else {
					switch( $upgrade->price_id ) {
						// Interstellar
						case 1:
						default:
							$message = esc_html__( 'Get access to Extensions', 'gravityview' );
							break;
						// Galactic
						case 2:
							$message = esc_html__( 'Get access to Entry Importer and other Premium plugins', 'gravityview' );
							break;
					}
				}

				$output .= sprintf( '<li><a href="%s">%s</a><span class="howto">%s</span></li>', esc_url( add_query_arg( array( 'utm_source' => 'settings', 'utm_medium' => 'admin', 'utm_content' => 'license-details', 'utm_campaign' => 'Upgrades' ), $upgrade->url ) ), $anchor_text, $message );
			}
			$output .= '</ul>';
		}

		return $output;
	}

	/**
	 * Perform the call to EDD based on the AJAX call or passed data
	 *
	 * @since 1.7.4
	 *
	 * @param array $array {
	 * @type string $license The license key
	 * @type string $edd_action The EDD action to perform, like `check_license`
	 * @type string $field_id The ID of the field to check
	 * @type boolean $update Whether to update plugin settings. Prevent updating the data by setting an `update` key to false
	 * @type string $format If `object`, return the object of the license data. Else, return the JSON-encoded object
	 * }
	 *
	 * @return mixed|string|void
	 */
	public function license_call( $array = array() ) {

		$is_ajax = ( defined('DOING_AJAX') && DOING_AJAX );
		$data = empty( $array ) ? $_POST['data'] : $array;
		$has_cap = GVCommon::has_cap( 'gravityview_edit_settings' );

		if ( $is_ajax && empty( $data['license'] ) ) {
			die( - 1 );
		}

		// If the user isn't allowed to edit settings, show an error message
		if( ! $has_cap && empty( $data['all_caps'] ) ) {
			$license_data = new stdClass();
			$license_data->error = 'capability';
			$license_data->message = $this->get_license_message( $license_data );
			$json = json_encode( $license_data );
		} else {

			$license      = esc_attr( rgget( 'license', $data ) );
			$license_data = $this->_license_get_remote_response( $data, $license );

			// Empty is returned when there's an error.
			if ( empty( $license_data ) ) {
				if ( $is_ajax ) {
					exit( json_encode( array() ) );
				} else { // Non-ajax call
					return json_encode( array() );
				}
			}

			$license_data->details = $this->license_details( $license_data );
			$license_data->message = $this->get_license_message( $license_data );

			$json = json_encode( $license_data );

			$update_license = ( ! isset( $data['update'] ) || ! empty( $data['update'] ) );

			$is_check_action_button = ( 'check_license' === $data['edd_action'] && defined( 'DOING_AJAX' ) && DOING_AJAX );

			if( $is_check_action_button ) {
				delete_transient( self::status_transient_key );
			}
			// Failed is the response from trying to de-activate a license and it didn't work.
			// This likely happened because people entered in a different key and clicked "Deactivate",
			// meaning to deactivate the original key. We don't want to save this response, since it is
			// most likely a mistake.
			else if ( $license_data->license !== 'failed' && $update_license ) {

				if ( ! empty( $data['field_id'] ) ) {
					set_transient( self::status_transient_key, $license_data, DAY_IN_SECONDS );
				}

				$this->license_call_update_settings( $license_data, $data );
			}
		} // End $has_cap

		if ( $is_ajax ) {
			exit( $json );
		} else { // Non-ajax call
			return ( rgget('format', $data ) === 'object' ) ? $license_data : $json;
		}
	}

	/**
	 * Update the license after fetching it
	 * @param object $license_data
	 * @return void
	 */
	private function license_call_update_settings( $license_data, $data ) {

		// Update option with passed data license
		$settings = $this->Addon->get_app_settings();

        $settings['license_key'] = $license_data->license_key = trim( $data['license'] );
		$settings['license_key_status'] = $license_data->license;
		$settings['license_key_response'] = (array)$license_data;

		$this->Addon->update_app_settings( $settings );
	}

	/**
	 * URL to direct license renewal, or if license key is not set, then just the account page
	 * @since 1.13.1
	 * @param  object|null $license_data Object with license data
	 * @return string Renewal or account URL
	 */
	private function get_license_renewal_url( $license_data ) {

		$license_data = is_array( $license_data ) ? (object)$license_data : $license_data;

		if( ! empty( $license_data->renewal_url ) ) {
			$renew_license_url = $license_data->renewal_url;
		} elseif( ! empty( $license_data->license_key ) ) {
			$renew_license_url = sprintf( 'https://gravityview.co/checkout/?download_id=17&edd_license_key=%s', $license_data->license_key );
		} else {
			$renew_license_url = 'https://gravityview.co/account/';
		}

		$renew_license_url = add_query_arg( wp_parse_args( 'utm_source=admin_notice&utm_medium=admin&utm_content=expired&utm_campaign=Activation&force_login=1' ), $renew_license_url );

		return $renew_license_url;
	}

	/**
	 * Override the text used in the GravityView EDD license Javascript
	 *
	 * @param  array|null $status Status to get. If empty, get all strings.
	 * @param  object|null $license_data Object with license data
	 * @return array          Modified array of content
	 */
	public function strings( $status = NULL, $license_data = null ) {


		$strings = array(
			'status' => esc_html__('Status', 'gravityview'),
			'error' => esc_html__('There was an error processing the request.', 'gravityview'),
			'failed'  => esc_html__('Could not deactivate the license. The license key you attempted to deactivate may not be active or valid.', 'gravityview'),
			'site_inactive' => esc_html__('The license key is valid, but it has not been activated for this site.', 'gravityview'),
			'inactive' => esc_html__('The license key is valid, but it has not been activated for this site.', 'gravityview'),
			'no_activations_left' => esc_html__('Invalid: this license has reached its activation limit.', 'gravityview') . ' ' . sprintf( esc_html__('You can manage license activations %son your GravityView account page%s.', 'gravityview'), '<a href="https://gravityview.co/account/#licenses">', '</a>' ),
			'deactivated' => esc_html__('The license has been deactivated.', 'gravityview'),
			'valid' => esc_html__('The license key is valid and active.', 'gravityview'),
			'invalid' => esc_html__('The license key entered is invalid.', 'gravityview'),
			'missing' => esc_html__('Invalid license key.', 'gravityview'),
			'revoked' => esc_html__('This license key has been revoked.', 'gravityview'),
			'expired' => sprintf( esc_html__('This license key has expired. %sRenew your license on the GravityView website%s to receive updates and support.', 'gravityview'), '<a href="'. esc_url( $this->get_license_renewal_url( $license_data ) ) .'">', '</a>' ),
			'capability' => esc_html__( 'You don\'t have the ability to edit plugin settings.', 'gravityview' ),

			'verifying_license' => esc_html__('Verifying license&hellip;', 'gravityview'),
			'activate_license' => esc_html__('Activate License', 'gravityview'),
			'deactivate_license' => esc_html__('Deactivate License', 'gravityview'),
			'check_license' => esc_html__('Verify License', 'gravityview'),
		);

		if( empty( $status ) ) {
			return $strings;
		}

		if( isset( $strings[ $status ] ) ) {
			return $strings[ $status ];
		}

		return NULL;
	}

}