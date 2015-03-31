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
				'value' => 'Activate License',
				'data-pending_text' => __('Verifying license&hellip;', 'gravityview'),
				'data-edd_action' => 'activate_license',
				'class' => ( !empty( $key ) && $status !== 'valid' ) ? '' : 'hide',
			),
			array(
				'name'  => 'edd-deactivate',
				'value' => 'Deactivate License',
				'data-pending_text' => __('Deactivating license&hellip;', 'gravityview'),
				'data-edd_action' => 'deactivate_license',
				'class' => ( !empty( $key ) && $status === 'valid' ) ? '' : 'hide',
			),
			array(
				'name'  => 'edd-check',
				'value' => 'Check License',
				'data-pending_text' => __('Verifying license&hellip;', 'gravityview'),
				'data-edd_action' => 'check_license',
				'class' => 'hide',
			),
		);

		$class = 'button button-secondary gv-edd-action';

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

		require_once( GRAVITYVIEW_DIR . 'includes/lib/EDD_SL_Plugin_Updater.php');

		// setup the updater
		$this->EDD_SL_Plugin_Updater = new EDD_SL_Plugin_Updater(
			self::url,
			GRAVITYVIEW_FILE,
			$this->_get_edd_settings()
		);

	}

	function _get_edd_settings( $action = '' ) {

		// retrieve our license key from the DB
		$license_key = trim( $this->Addon->get_app_setting( 'license_key' ) );

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
	private function _license_get_remote_response( $data ) {

		$api_params = $this->_get_edd_settings( $data['edd_action'] );

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

			delete_transient( 'redux_edd_license_' . esc_attr( $data['field_id'] ) . '_valid' );

			// Change status
			return array();
		}

		return $license_data;
	}

	function get_license_message( $license_data ) {

		if( empty( $license_data ) ) {
			$class = 'hide';
			$message = '';
		} else if ( ! empty( $license_data->error ) ) {
			$message = $this->strings( $license_data->error );
			$class = 'error';
		} else {
			$message = sprintf( '<p><strong>%s: %s</strong></p>', $this->strings('status'), $this->strings( $license_data->license ) );
			$class = $license_data->license;
		}

		return $this->generate_license_box( $message, $class );
	}

	private function generate_license_box( $message, $class = '' ) {

		$template = '<div id="gv-edd-status" class="gv-edd-message %s">%s</div>';

		$output = sprintf( $template, esc_attr( $class ), $message );

		return $output;
	}

	function license_call( $array = array() ) {
		global $wp_version;

		$data = empty( $array ) ? $_POST['data'] : $array;

		if ( empty( $data['license'] ) ) {
			die( - 1 );
		}

		$license_data = $this->_license_get_remote_response( $data );

		if ( empty( $license_data ) ) {
			if ( empty( $array ) ) {
				exit( json_encode( array() ) );
			} else { // Non-ajax call
				return json_encode( array() );
			}
		}

		$license_data->message = $this->get_license_message( $license_data );

		$json = json_encode( $license_data );

		// Failed is the response from trying to de-activate a license and it didn't work.
		// This likely happened because people entered in a different key and clicked "Deactivate",
		// meaning to deactivate the original key. We don't want to save this response, since it is
		// most likely a mistake.
		if ( $license_data->license !== 'failed' ) {

			set_transient( 'redux_edd_license_' . esc_attr( $data['field_id'] ) . '_valid', $license_data, DAY_IN_SECONDS );

			// Update option with passed data license
			$settings = $this->Addon->get_app_settings();

			$settings['license_key'] = trim( $data['license'] );
			$settings['license_key_status'] = $license_data->license;
			$settings['license_key_response'] = $json;

			$this->Addon->update_app_settings( $settings );
		}

		if ( empty( $array ) ) {
			exit( $json );
		} else { // Non-ajax call
			return $json;
		}
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

		$status = $this->Addon->get_app_setting( 'license_key_status' );
		$response = $this->Addon->get_app_setting( 'license_key_response' );

		$response = is_string( $response ) ? json_decode( $response, true ) : $response;

		switch( $status ) {
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