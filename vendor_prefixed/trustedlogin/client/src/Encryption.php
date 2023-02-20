<?php
/**
 * Class Encryption
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
use \Sodium;

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
	 * @var string $vendor_public_key_option Where the plugin should store the public key for encrypting data
	 * @since 1.0.0
	 */
	private $vendor_public_key_option;

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
		 * @since 1.0.0
		 *
		 * @param string $vendor_public_key_option
		 * @param Config $config
		 */
		$this->vendor_public_key_option = apply_filters(
			'trustedlogin/' . $this->config->ns() . '/options/vendor_public_key',
			'tl_' . $this->config->ns() . '_vendor_public_key',
			$this->config
		);
	}

	/**
	 * Generates a random hash 64 characters long.
	 *
	 * If random_bytes() and openssl_random_pseudo_bytes() don't exist, returns WP_Error with code generate_hash_failed.
	 *
	 * If random_bytes() does not exist and openssl_random_pseudo_bytes() is unable to return a strong result,
	 * returns a WP_Error with code `openssl_not_strong_crypto`.
	 *
	 * @uses random_bytes
	 * @uses openssl_random_pseudo_bytes Only used if random_bytes() does not exist.
	 *
	 * @param Logging The logging object to use
	 *
	 * @return string|WP_Error 64-character random hash or a WP_Error object explaining what went wrong. See docblock.
	 */
	static public function get_random_hash( $logging ) {

		$byte_length = 64;

		$hash = false;

		if ( function_exists( 'random_bytes' ) ) {
			try {
				$bytes = random_bytes( $byte_length );
				$hash  = bin2hex( $bytes );
			} catch ( \TypeError $e ) {
				$logging->log( $e->getMessage(), __METHOD__, 'error' );
			} catch ( \Error $e ) {
				$logging->log( $e->getMessage(), __METHOD__, 'error' );
			} catch ( \Exception $e ) {
				$logging->log( $e->getMessage(), __METHOD__, 'error' );
			}
		} else {
			$logging->log( 'This site does not have the random_bytes() function.', __METHOD__, 'debug' );
		}

		if ( $hash ) {
			return $hash;
		}

		if ( ! function_exists( 'openssl_random_pseudo_bytes' ) ) {
			return new \WP_Error( 'generate_hash_failed', 'Could not generate a secure hash with random_bytes or openssl.' );
		}

		$crypto_strong = false;
		$hash          = openssl_random_pseudo_bytes( $byte_length, $crypto_strong );

		if ( ! $crypto_strong ) {
			return new \WP_Error( 'openssl_not_strong_crypto', 'Site could not generate a secure hash with OpenSSL.' );
		}

		return $hash;
	}

	/**
	 * @param $string
	 *
	 * @return string|WP_Error
	 */
	static public function hash( $string, $length = 16 ) {

		if ( ! function_exists( 'sodium_crypto_generichash' ) ) {
			return new \WP_Error( 'sodium_crypto_generichash_not_available', 'sodium_crypto_generichash not available' );
		}

		try {
			$hash_bin = sodium_crypto_generichash( $string, '', (int) $length );
			$hash     = sodium_bin2hex( $hash_bin );
		} catch ( \TypeError $e ) {
			return new \WP_Error(
				'encryption_failed_generichash_typeerror',
				sprintf( 'Error while generating hash: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		} catch ( \Error $e ) {
			return new \WP_Error(
				'encryption_failed_generichash_error',
				sprintf( 'Error while generating hash: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		} catch ( \SodiumException $e ) {
			return new \WP_Error(
				'encryption_failed_generichash_sodium',
				sprintf( 'Error while generating hash: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'encryption_failed_generichash',
				sprintf( 'Error while generating hash: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		}

		return $hash;
	}

	/**
	 * Fetches the Public Key from local or db
	 *
	 * @since 1.0.0
	 *
	 * @return string|WP_Error  If found, it returns the publicKey, if not a WP_Error
	 */
	public function get_vendor_public_key() {

		// Already stored as transient
		$public_key = get_site_transient( $this->vendor_public_key_option );

		if ( $public_key ) {
			// Documented below
			return apply_filters( 'trustedlogin/' . $this->config->ns() . '/vendor_public_key', $public_key, $this->config );
		}

		// Fetch a key from Vendor site
		$remote_key = $this->get_remote_encryption_key();

		if ( is_wp_error( $remote_key ) ) {

			$this->logging->log( sprintf( '(%s) %s', $remote_key->get_error_code(), $remote_key->get_error_message() ), __METHOD__, 'error' );

			return $remote_key;
		}

		// Attempt to store Vendor public key in the DB for ten minutes (may be overridden by caching plugins)
		$saved = set_site_transient( $this->vendor_public_key_option, $remote_key, 60 * 10 );

		if ( ! $saved ) {
			$this->logging->log( 'Public key not saved after being fetched remotely.', __METHOD__, 'warning' );
		}

		/**
		 * Filter: Override the public key functions.
		 *
		 * @since 1.0.0
		 *
		 * @param string $vendor_public_key
		 * @param Config $config
		 */
		return apply_filters( 'trustedlogin/' . $this->config->ns() . '/vendor_public_key', $remote_key, $this->config );
	}

	/**
	 * Fetches the Public Key from the `TrustedLogin-vendor` plugin on support website.
	 *
	 * @since 1.0.0
	 *
	 * @return string|WP_Error  If successful, will return the Public Key string. Otherwise WP_Error on failure.
	 */
	private function get_remote_encryption_key() {

		$vendor_website = $this->config->get_setting( 'vendor/website', '' );

		/**
		 * @param string $public_key_website Root URL of the website from where the vendor's public key is fetched. May be different than the vendor/website configuration setting.
		 * @since 1.3.2
		 */
		$public_key_website = apply_filters( 'trustedlogin/' . $this->config->ns() . '/vendor/public_key/website', $vendor_website );

		/**
		 * @param string $key_endpoint Endpoint path on vendor (software vendor's) site
		 */
		$public_key_endpoint = apply_filters( 'trustedlogin/' . $this->config->ns() . '/vendor/public_key/endpoint', $this->vendor_public_key_endpoint );

		$url = trailingslashit( $public_key_website ) . $public_key_endpoint;

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
				return new \WP_Error( 'not_found', __( 'Encryption key could not be fetched, Vendor site returned 404.', 'gk-gravityview' ) );
			}

			return $response_json;
		}

		return $response_json['publicKey'];
	}

	/**
	 * Encrypts a string using the Public Key provided by the plugin/theme developers' server.
	 *
	 * @since 1.0.0
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
			return new \WP_Error( 'no_data', 'No data provided.' );
		}

		if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
			return new \WP_Error( 'sodium_crypto_secretbox_not_available', 'lib_sodium not available' );
		}

		$bob_public_key = $this->get_vendor_public_key();

		if ( is_wp_error( $bob_public_key ) ) {
			return $bob_public_key;
		}

		try {

			$alice_to_bob_kp = sodium_crypto_box_keypair_from_secretkey_and_publickey( $alice_secret_key, \sodium_hex2bin( $bob_public_key ) );
			$encrypted       = sodium_crypto_box( $data, $nonce, $alice_to_bob_kp );

		} catch ( \SodiumException $e ) {
			return new \WP_Error(
				'encryption_failed_cryptobox',
				sprintf( 'Error while encrypting the envelope: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		} catch ( \RangeException $e ) {
			return new \WP_Error(
				'encryption_failed_cryptobox_rangeexception',
				sprintf( 'Error while encrypting the envelope: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		} catch ( \TypeError $e ) {
			return new \WP_Error(
				'encryption_failed_cryptobox_typeerror',
				sprintf( 'Error while encrypting the envelope: %s (%s)', $e->getMessage(), $e->getCode() )
			);
		}

		return base64_encode( $encrypted );
	}

	/**
	 * Gets and returns a random nonce.
	 *
	 * @since 1.0.0
	 *
	 * @return string|WP_Error  Nonce if created, otherwise WP_Error
	 */
	public function get_nonce() {

		if ( ! function_exists( 'random_bytes' ) ) {
			return new \WP_Error( 'missing_function', 'No random_bytes function installed.' );
		}

		try {
			$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'encryption_failed_randombytes', sprintf( 'Unable to generate encryption nonce: %s (%s)', $e->getMessage(), $e->getCode() ) );
		}

		return $nonce;
	}

	/**
	 * Generate unique Client encryption keys.
	 *
	 * @since 1.0.0
	 *
	 * @uses sodium_crypto_box_keypair()
	 * @uses sodium_crypto_box_publickey()
	 * @uses sodium_crypto_box_secretkey()
	 *
	 * @return object|WP_Error $alice_keys or WP_Error if there's any issues.
	 *   $alice_keys = [
	 *      'publicKey'  =>  (string)  The public key.
	 *      'privateKey' =>  (string)  The private key.
	 *   ]
	 */
	public function generate_keys() {

		if ( ! function_exists( 'sodium_crypto_box_keypair' ) ) {
			return new \WP_Error( 'sodium_crypto_secretbox_not_available', 'lib_sodium not available' );
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
