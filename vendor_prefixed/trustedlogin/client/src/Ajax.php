<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\ThirdParty\TrustedLogin;

// Exit if accessed directly
if ( ! defined('ABSPATH') ) {
	exit;
}

use \Exception;
use \WP_Error;
use \WP_User;
use \WP_Admin_Bar;

final class Ajax {

	/**
	 * @var \GravityKit\GravityView\Foundation\ThirdParty\TrustedLogin\Config
	 */
	private $config;

	/**
	 * @var null|\GravityKit\GravityView\Foundation\ThirdParty\TrustedLogin\Logging $logging
	 */
	private $logging;

	/**
	 * Cron constructor.
	 *
	 * @param Config $config
	 * @param Logging|null $logging
	 */
	public function __construct( Config $config, Logging $logging ) {
		$this->config  = $config;
		$this->logging = $logging;
	}

	/**
	 *
	 */
	public function init() {
		add_action( 'wp_ajax_tl_' . $this->config->ns() . '_gen_support', array( $this, 'ajax_generate_support' ) );
	}

	/**
	 * AJAX handler for maybe generating a Support User
	 *
	 * @since 1.0.0
	 *
	 * @return void Sends a JSON success or error message based on what happens
	 */
	public function ajax_generate_support() {

		if ( empty( $_POST['vendor'] ) ) {

			$this->logging->log( 'Vendor not defined in TrustedLogin configuration.', __METHOD__, 'critical' );

			wp_send_json_error( array( 'message' => 'Vendor not defined in TrustedLogin configuration.' ) );
		}

		// There are multiple TrustedLogin instances, and this is not the one being called.
		// This should not occur, since the AJAX action is namespaced.
		if ( $this->config->ns() !== $_POST['vendor'] ) {

			$this->logging->log( 'Vendor does not match TrustedLogin configuration.', __METHOD__, 'critical' );

			wp_send_json_error( array( 'message' => 'Vendor does not match.' ) );
			return;
		}

		if ( empty( $_POST['_nonce'] ) ) {
			wp_send_json_error( array( 'message' => 'Nonce not sent in the request.' ) );
		}

		if ( ! check_ajax_referer( 'tl_nonce-' . get_current_user_id(), '_nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Verification issue: Request could not be verified. Please reload the page.' ) );
		}

		if ( ! current_user_can( 'create_users' ) ) {

			$this->logging->log( 'Current user does not have `create_users` capability when trying to grant access.', __METHOD__, 'error' );

			wp_send_json_error( array( 'message' => 'You do not have the ability to create users.' ) );
		}

		$client = new Client( $this->config, false );

		$response = $client->grant_access();

		if ( is_wp_error( $response ) ) {

			$error_data = $response->get_error_data();
			$error_code = isset( $error_data['error_code'] ) ? $error_data['error_code'] : 500;

			wp_send_json_error( array( 'message' => $response->get_error_message() ), $error_code );
		}

		wp_send_json_success( $response, 201 );
	}

}
