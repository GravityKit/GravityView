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

	private $EDD_SL_Plugin_Updater;

	/**
	 * @var GV_License_Handler
	 */
	static $instance;

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
	}

	function settings_edd_license_activation( $field, $echo ) {

		wp_enqueue_script( 'gv-admin-edd-license', GRAVITYVIEW_URL . 'assets/js/admin-edd-license.js', array( 'jquery' ) );

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
				'class' => 'button-primary',
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

		$submit = '';
		foreach ( $fields as $field ) {
			$field['type'] = 'button';
			$field['class'] = isset( $field['class'] ) ? $field['class'] . ' '. $class : $class;
			$field['style'] = 'margin-left: 10px;';
			$submit .= $this->Addon->settings_submit( $field, $echo );
		}

		return $submit;
	}

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

	function _get_edd_settings( $action = '', $license = null ) {

		// retrieve our license key from the DB
		$license_key = empty( $license ) ? trim( $this->Addon->get_app_setting( 'license_key' ) ) : $license;

		$settings = array(
			'version'   => self::version,
			'license'   => $license_key,
			'item_name' => self::name,
			'author'    => self::author,
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

			delete_transient( 'gravityview_' . esc_attr( $data['field_id'] ) . '_valid' );

			// Change status
			return array();
		}

		return $license_data;
	}

	function get_license_message( $license_data ) {

		if( empty( $license_data ) ) {
			$class = 'hide';
			$message = '';
		} else {

			$class = ! empty( $license_data->error ) ? 'error' : $license_data->license;

			$message = sprintf( '<p><strong>%s: %s</strong></p>', $this->strings('status'), $this->strings( $license_data->license ) );
		}

		return $this->generate_license_box( $message, $class );
	}

	/**
	 * Generate the status message box HTML based on the
	 * @param $message
	 * @param string $class
	 *
	 * @return string
	 */
	private function generate_license_box( $message, $class = '' ) {

		$template = '<div id="gv-edd-status" class="gv-edd-message inline %s">%s</div>';

		$output = sprintf( $template, esc_attr( $class ), $message );

		return $output;
	}

	/**
	 * @param array $array Prevent updating the data by setting an `update` key to false
	 *
	 * @return mixed|string|void
	 */
	public function license_call( $array = array() ) {

		$data = empty( $array ) ? $_POST['data'] : $array;

		if ( empty( $data['license'] ) ) {
			die( - 1 );
		}

		$license = rgget( 'license', $data );
		$license_data = $this->_license_get_remote_response( $data, $license );

		if ( empty( $license_data ) ) {
			if ( empty( $array ) ) {
				exit( json_encode( array() ) );
			} else { // Non-ajax call
				return json_encode( array() );
			}
		}

		$license_data->message = $this->get_license_message( $license_data );

		$json = json_encode( $license_data );

		$update_license = ( !isset( $data['update'] ) || !empty( $data['update'] ) );

		$is_check_action_button = ( 'check_license' === $data['edd_action'] && defined('DOING_AJAX') && DOING_AJAX );

		// Failed is the response from trying to de-activate a license and it didn't work.
		// This likely happened because people entered in a different key and clicked "Deactivate",
		// meaning to deactivate the original key. We don't want to save this response, since it is
		// most likely a mistake.
		if ( $license_data->license !== 'failed' && !$is_check_action_button && $update_license ) {

			if( !empty( $data['field_id'] ) ) {
				set_transient( 'gravityview_' . esc_attr( $data['field_id'] ) . '_valid', $license_data, DAY_IN_SECONDS );
			}

			$this->license_call_update_settings( $license_data, $data );

		}

		if ( empty( $array ) ) {
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

		$settings['license_key'] = trim( $data['license'] );
		$settings['license_key_status'] = $license_data->license;
		$settings['license_key_response'] = (array)$license_data;

		$this->Addon->update_app_settings( $settings );
	}

	/**
	 * Override the text used in the Redux Framework EDD field extension
	 * @param  array|null $status Status to get. If empty, get all strings.
	 * @return array          Modified array of content
	 */
	public function strings( $status = NULL ) {

		$strings = array(
			'status' => esc_html__('Status', 'gravityview'),
			'error' => esc_html__('There was an error processing the request.', 'gravityview'),
			'failed'  => esc_html__('Could not deactivate the license. The license key you attempted to deactivate may not be active or valid.', 'gravityview'),
			'site_inactive' => esc_html__('The license is valid, but it has not been activated for this site.', 'gravityview'),
			'no_activations_left' => esc_html__('Invalid: this license has reached its activation limit.', 'gravityview'),
			'deactivated' => esc_html__('The license has been deactivated.', 'gravityview'),
			'valid' => esc_html__('This license is valid.', 'gravityview'),
			'invalid' => esc_html__('This license is invalid.', 'gravityview'),
			'missing' => esc_html__('The license was not defined.', 'gravityview'),
			'revoked' => esc_html__('This license key has been revoked.', 'gravityview'),
			'expired' => sprintf( esc_html__('This license key has expired. %sRenew your license on the GravityView website%s', 'gravityview'), '<a href="">', '</a>' ),

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

	public function validate_license_key( $value, $field ) {

		// No license? No status.
		if( empty( $value ) ) {
			return NULL;
		}

		$response = $this->license_call(array(
			'license' => $this->Addon->get_app_setting( 'license_key' ),
			'edd_action' => 'check_license',
			'field_id' => $field['name'],
		));

		$response = is_string( $response ) ? json_decode( $response, true ) : $response;

		switch( $response['license'] ) {
			case 'valid':
				$return = true;
				break;
			case 'invalid':
				$return = false;
				//$this->Addon->set_field_error( $field, $response['message'] );
				break;
			default:
				//$this->Addon->set_field_error( $field, $response['message'] );
				$return = false;
		}

		return $return;
	}

}