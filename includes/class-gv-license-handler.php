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

		$this->setup_edd();
		
		$this->add_hooks();
	}

	private function add_hooks() {
		add_action( 'wp_ajax_gravityview_license', array( $this, 'license_call' ) );
		add_action( 'admin_init', array( $this, 'refresh_license_status' ) );
	}

	/**
	 * When the status transient expires (or is deleted on activation), re-check the status
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	public function refresh_license_status() {

		// Only perform on GravityView pages
		if( ! gravityview_is_admin_page() ) {
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
			'field_id' => 'refresh_license_status', // Required to set the `status_transient_key` transient
		);

		$license_call = GravityView_Settings::get_instance()->get_license_handler()->license_call( $data );

		do_action( 'gravityview_log_debug', __METHOD__ . ': Refreshed the license.', $license_call );
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
	 * @since 1.7.4
	 * @return void
	 */
	private function setup_edd() {

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
			return array();
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// Not JSON
		if ( empty( $license_data ) ) {

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
		$return .= '<span class="gv-license-details" aria-live="polite" aria-busy="false">';
		$return .= '<h3>' . esc_html__( 'License Details:', 'gravityview' ) . '</h3>';

		if( in_array( rgar( $response, 'license' ), array( 'invalid', 'deactivated' ) ) ) {
			$return .= $this->strings( $response['license'], $response );
		} elseif( ! empty( $response['license_name'] ) ) {

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
			$details = array(
				'license'     => sprintf( esc_html__( 'License level: %s', 'gravityview' ), esc_html( $response['license_name'] ), esc_html( $response['license_limit'] ) ),
				'licensed_to' => sprintf( esc_html_x( 'Licensed to: %1$s (%2$s)', '1: Customer name; 2: Customer email', 'gravityview' ), esc_html__( $response['customer_name'], 'gravityview' ), esc_html__( $response['customer_email'], 'gravityview' ) ) . $login_link,
				'activations' => sprintf( esc_html__( 'Activations: %d of %s sites', 'gravityview' ), intval( $response['site_count'] ), esc_html( $response['license_limit'] ) ) . $local_text,
				'expires'     => sprintf( esc_html__( 'Renew on: %s', 'gravityview' ), date_i18n( get_option( 'date_format' ), strtotime( $response['expires'] ) - DAY_IN_SECONDS ) ),
				'upgrade'     => $this->get_upgrade_html( $response['upgrades'] ),
			);

			if ( ! empty( $response['error'] ) && 'expired' === $response['error'] ) {
				unset( $details['upgrade'] );
				$details['expires'] = '<div class="error inline"><p>' . $this->strings( 'expired', $response ) . '</p></div>';
			}

			$return .= '<ul><li>' . implode( '</li><li>', array_filter( $details ) ) . '</li></ul>';
		}

		$return .= '</span>';

		return $return;
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
		if( ! $has_cap ) {
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

			// Failed is the response from trying to de-activate a license and it didn't work.
			// This likely happened because people entered in a different key and clicked "Deactivate",
			// meaning to deactivate the original key. We don't want to save this response, since it is
			// most likely a mistake.
			if ( $license_data->license !== 'failed' && ! $is_check_action_button && $update_license ) {

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
		$renew_license_url = ( ! empty( $license_data ) && !empty( $license_data->license_key ) ) ? sprintf( 'https://gravityview.co/checkout/?download_id=17&edd_license_key=%s&utm_source=admin_notice&utm_medium=admin&utm_content=expired&utm_campaign=Activation&force_login=1', $license_data->license_key ) : 'https://gravityview.co/account/';
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