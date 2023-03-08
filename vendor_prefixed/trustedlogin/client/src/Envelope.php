<?php
/**
 * Class Envelope
 *
 * @package GravityKit\GravityView\Foundation\ThirdParty\TrustedLogin\Client
 *
 * @copyright 2021 Katz Web Services, Inc.
 *
 * @license GPL-2.0-or-later
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

/**
 * The TrustedLogin all-in-one drop-in class.
 */
final class Envelope {

	/**
	 * @var Config $config
	 */
	private $config;

	/**
	 * @var Encryption
	 */
	private $encryption;

	/**
	 * @var string API key set in software.
	 */
	private $api_key;

	/**
	 * Envelope constructor.
	 *
	 * @param Config $config
	 * @param Encryption $encryption
	 */
	public function __construct( Config $config, Encryption $encryption ) {
		$this->config     = $config;
		$this->api_key = $this->config->get_setting( 'auth/api_key' );
		$this->encryption = $encryption;
	}

	/**
	 * @param string $secret_id
	 * @param string $site_identifier_hash
	 * @param string $access_key
	 *
	 * @return array|WP_Error
	 */
	public function get( $secret_id, $site_identifier_hash, $access_key = '' ) {

		if ( ! is_string( $secret_id ) ) {
			return new \WP_Error( 'secret_not_string', 'The secret ID must be a string:' . print_r( $secret_id, true ) );
		}

		if ( ! is_string( $site_identifier_hash ) ) {
			return new \WP_Error( 'site_identifier_not_string', 'The site identifier must be a string:' . print_r( $site_identifier_hash, true ) );
		}

		if ( ! is_string( $access_key ) ) {
			return new \WP_Error( 'access_key_not_string', 'The access key must be a string: ' . print_r( $access_key, true ) );
		}

		if ( ! function_exists( 'sodium_bin2hex' ) ) {
			return new \WP_Error( 'sodium_bin2hex_not_available', 'The sodium_bin2hex function is not available.' );
		}

		$e_keys = $this->encryption->generate_keys();

		if ( is_wp_error( $e_keys ) ){
			return $e_keys;
		}

		$nonce = $this->encryption->get_nonce();

		if ( is_wp_error( $nonce ) ){
			return $nonce;
		}

		$encrypted_identifier = $this->encryption->encrypt( $site_identifier_hash, $nonce, $e_keys->privateKey );

		if ( is_wp_error( $encrypted_identifier ) ) {
			return $encrypted_identifier;
		}

		/**
		 * Adds custom metadata to be synced via TrustedLogin
		 *
		 * WARNING: Metadata is transferred and stored in plain text, and **must not contain any sensitive or identifiable information**!
		 *
		 * @since 1.0.0
		 *
		 * @param array  $metadata
		 * @param Config $config Current TrustedLogin configuration
		 */
		$metadata = apply_filters( 'trustedlogin/' . $this->config->ns() . '/envelope/meta', array(), $this->config );

		return array(
			'secretId'   	  => $secret_id,
			'identifier' 	  => $encrypted_identifier,
			'siteUrl'    	  => get_site_url(),
			'publicKey'  	  => $this->api_key,
			'accessKey'  	  => $access_key,
			'wpUserId'   	  => get_current_user_id(),
			'expiresAt'       => $this->config->get_expiration_timestamp( null, true ),
			'version'    	  => Client::VERSION,
			'nonce'		 	  => \sodium_bin2hex( $nonce ),
			'clientPublicKey' => \sodium_bin2hex( $e_keys->publicKey ),
			'metaData'		  => $metadata,
		);
	}

}
