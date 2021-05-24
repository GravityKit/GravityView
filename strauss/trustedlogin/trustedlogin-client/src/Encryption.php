<?php
/**
 * Class Encryption
 *
 * @package GravityView\TrustedLogin\Client
 *
 * @copyright 2020 Katz Web Services, Inc.
 *
 * @license GPL-2.0-or-later
 * Modified by gravityview on 24-May-2021 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */
namespace GravityView\TrustedLogin;

// Exit if accessed directly
if ( ! defined('ABSPATH') ) {
	exit;
}

use \Exception;
use \WP_Error;
use \Sodium;

/**
 * The TrustedLogin all-in-one drop-in class.
 */
final class Encryption {

	/**
	 * @var Config $config
	 */
	private $config;

	/**
	 * @var Remote $remote
	 */
	private $remote;

	/**
	 * @var Logging
	 */
	private $logging;

	/**
	 * @var string $public_key_option Where the plugin should store the public key for encrypting data
	 * @since 0.5.0
	 */
	private $public_key_option;

	/**
	 * @var string Endpoint path to Vendor public key.
	 */
	private $vendor_public_key_endpoint = 'wp-json/trustedlogin/v1/public_key';

	/**
	 * Encryption constructor.
	 *
	 * @param Config $config
	 * @param Remote $remote
	 * @param Logging $logging
	 */
	public function __construct( Config $config, Remote $remote, Logging $logging ) {

		$this->config  = $config;
		$this->remote  = $remote;
		$this->logging = $logging;

		/**
		 * Filter: Sets the site option name for the Public Key for encryption functions
		 *
		 * @since 0.5.0
		 *
		 * @param string $public_key_option
		 * @param Config $config
		 */
		$this->public_key_option = apply_filters(
			'trustedlogin/' . $this->config->ns() . '/options/public_key',
			'tl_' . $this->config->ns() . '_public_key',
			$this->config
		);
	}

	/**
	 * @param $string
	 *
	 * @return string|WP_Error
	 */
	static public function hash( $string ) {

		if ( ! function_exists( 'sodium_crypto_generichash' ) ) {
			return new WP_Error( 'sodium_crypto_generichash_not_available', 'sodium_crypto_generichash not available' );
		}

		try {
			$hash_bin = sodium_crypto_generichash( $string, '', 16 );
			$hash     = sodium_bin2hex( $hash_bin );
		} catch ( \SodiumException $exception ) {
			return new WP_Error(
				'encryption_failed_generichash',
				sprintf( 'Error while generating hash: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		} catch ( \TypeError $exception ) {
			return new WP_Error(
				'encryption_failed_generichash_typeerror',
				sprintf( 'Error while generating hash: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		}

		return $hash;
	}

	/**
	 * Fetches the Public Key from local or db
	 *
	 * @since 0.5.0
	 *
	 * @return string|WP_Error  If found, it returns the publicKey, if not a WP_Error
	 */
	public function get_public_key() {

		// Already stored as transient
		$public_key = get_site_transient( $this->public_key_option );

		if ( $public_key ) {
			// Documented below
			return apply_filters( 'trustedlogin/' . $this->config->ns() . '/public_key', $public_key, $this->config );
		}

		// Fetch a key from Vendor site
		$remote_key = $this->get_remote_encryption_key();

		if ( is_wp_error( $remote_key ) ) {

			$this->logging->log( sprintf( '(%s) %s', $remote_key->get_error_code(), $remote_key->get_error_message() ), __METHOD__, 'notice' );

			return $remote_key;
		}

		// Store Vendor public key in the DB for ten minutes
		$saved = set_site_transient( $this->public_key_option, $remote_key, 60 * 10 );

		if ( ! $saved ) {
			$this->logging->log( 'Public key not saved after being fetched remotely.', __METHOD__, 'notice' );
		}

		/**
		 * Filter: Override the public key functions.
		 *
		 * @since 0.5.0
		 *
		 * @param string $public_key
		 * @param Config $config
		 */
		return apply_filters( 'trustedlogin/' . $this->config->ns() . '/public_key', $remote_key, $this->config );
	}

	/**
	 * Fetches the Public Key from the `TrustedLogin-vendor` plugin on support website.
	 *
	 * @since 0.5.0
	 *
	 * @return string|WP_Error  If successful, will return the Public Key string. Otherwise WP_Error on failure.
	 */
	private function get_remote_encryption_key() {

		$vendor_url = $this->config->get_setting( 'vendor/website' );

		/**
		 * @param string $key_endpoint Endpoint path on vendor (software vendor's) site
		 */
		$key_endpoint = apply_filters( 'trustedlogin/' . $this->config->ns() . '/vendor/public_key/endpoint', $this->vendor_public_key_endpoint );

		$url = trailingslashit( $vendor_url ) . $key_endpoint;

		$headers = array(
			'Accept'       => 'application/json',
			'Content-Type' => 'application/json',
		);

		$request_options = array(
			'method'      => 'GET',
			'timeout'     => 45,
			'httpversion' => '1.1',
			'headers'     => $headers
		);

		$response = wp_remote_request( $url, $request_options );

		$response_json = $this->remote->handle_response( $response, array( 'publicKey' ) );

		if ( is_wp_error( $response_json ) ) {

			if ( 'not_found' == $response_json->get_error_code() ){
				return new WP_Error( 'not_found', __( 'Encryption key could not be fetched, Vendor site returned 404.', 'trustedlogin' ) );
			}

			return $response_json;
		}

		return $response_json['publicKey'];
	}

	/**
	 * Encrypts a string using the Public Key provided by the plugin/theme developers' server.
	 *
	 * @since 0.5.0
	 * @uses \sodium_crypto_box_keypair_from_secretkey_and_publickey() to generate key.
	 * @uses \sodium_crypto_secretbox() to encrypt.
	 *
	 * @param string $data Data to encrypt.
	 * @param string $nonce The nonce generated for this encryption.
	 * @param string $alice_secret_key The key to use when generating the encryption key.
	 *
	 * @return string|WP_Error  Encrypted envelope or WP_Error on failure.
	 */
	public function encrypt( $data, $nonce, $alice_secret_key ) {

		if ( empty( $data ) ) {
			return new WP_Error( 'no_data', 'No data provided.' );
		}

		if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
			return new WP_Error( 'sodium_crypto_secretbox_not_available', 'lib_sodium not available' );
		}

		$bob_public_key = $this->get_public_key();

		if ( is_wp_error( $bob_public_key ) ) {
			return $bob_public_key;
		}

		try {

			$alice_to_bob_kp = sodium_crypto_box_keypair_from_secretkey_and_publickey( $alice_secret_key, \sodium_hex2bin( $bob_public_key ) );
			$encrypted       = sodium_crypto_box( $data, $nonce, $alice_to_bob_kp );

		} catch ( \SodiumException $e ) {
			return new WP_Error(
				'encryption_failed_cryptobox',
				sprintf( 'Error while encrypting the envelope: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		} catch ( \RangeException $e ) {
			return new WP_Error(
				'encryption_failed_cryptobox_rangeexception',
				sprintf( 'Error while encrypting the envelope: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		} catch ( \TypeError $e ) {
			return new WP_Error(
				'encryption_failed_cryptobox_typeerror',
				sprintf( 'Error while encrypting the envelope: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		}

		return base64_encode( $encrypted );
	}

	/**
	 * Gets and returns a random nonce.
	 *
	 * @since 0.5.0
	 *
	 * @return string|WP_Error  Nonce if created, otherwise WP_Error
	 */
	public function get_nonce() {

		if ( ! function_exists( 'random_bytes' ) ) {
			return new WP_Error( 'missing_function', 'No random_bytes function installed.' );
		}

		try {
			$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		} catch ( \Exception $e ) {
			return new WP_Error( 'encryption_failed_randombytes', sprintf( 'Unable to generate encryption nonce: %s (%s)', $e->getMessage(), $e->getCode() ) );
		}

		return $nonce;
	}

	/**
	 * Generate unique Client encryption keys.
	 *
	 * @since 0.5.0
	 *
	 * @uses sodium_crypto_box_keypair()
	 * @uses sodium_crypto_box_publickey()
	 * @uses sodium_crypto_box_secretkey()
	 *
	 * @return object|WP_Error $alice_keys or WP_Error if there's any issues.
	 *   $alice_keys = [
	 *      'publicKey'  =>  (string)  The public key.
	 *      'privatekey' =>  (string)  The private key.
	 *   ]
	 */
	public function generate_keys() {

		if ( ! function_exists( 'sodium_crypto_box_keypair' ) ) {
			return new WP_Error( 'sodium_crypto_secretbox_not_available', 'lib_sodium not available' );
		}

		// In our build Alice = Client & Bob = Vendor.
		$aliceKeypair = sodium_crypto_box_keypair();

		$alice_keys = array(
			'publicKey'  => sodium_crypto_box_publickey( $aliceKeypair ),
			'privateKey' => sodium_crypto_box_secretkey( $aliceKeypair )
		);

		return (object) $alice_keys;
	}
}
