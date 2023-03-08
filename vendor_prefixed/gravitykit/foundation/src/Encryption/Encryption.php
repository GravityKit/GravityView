<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Encryption;

use Exception;

/**
 * This class provides basic data encryption functionality.
 */
class Encryption {
	const DEFAULT_NONCE = 'bc5d92ffc6c54ff8d865a1e6f3361f48d0a84a2b145be34e'; // 24-bit value stored as a hex string

	/**
	 * @since 1.0.0
	 *
	 * @var Encryption Class instance.
	 */
	private static $_instance;

	/**
	 * @since 1.0.0
	 *
	 * @var string Secret key used to encrypt license key.
	 */
	private $_secret_key;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $secret_key (optional) Secret key to be used for encryption. Default: wp_salt() value.
	 *
	 * @return void
	 */
	private function __construct( $secret_key = '' ) {
		if ( ! $secret_key ) {
			$secret_key = wp_salt();
		}

		if ( strlen( $secret_key ) < SODIUM_CRYPTO_SECRETBOX_KEYBYTES ) {
			$secret_key = hash_hmac( 'sha256', $secret_key, self::DEFAULT_NONCE );
		}

		if ( strlen( $secret_key ) > SODIUM_CRYPTO_SECRETBOX_KEYBYTES ) {
			$secret_key = mb_substr( $secret_key, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES, '8bit' );
		}

		$this->_secret_key = $secret_key;
	}

	/**
	 * Returns class instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $secret_key (optional) Secret key to be used for encryption. Default: wp_salt() value.
	 *
	 * @return Encryption
	 */
	public static function get_instance( $secret_key = '' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $secret_key );
		}

		return self::$_instance;
	}

	/**
	 * Encrypts data.
	 *
	 * Note: This is for basic internal use and is not intended for highly-sensitive applications.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $data             Data to encrypt.
	 * @param bool        $use_random_nonce (optional) Whether to use random nonce. Default: true.
	 * @param string|null $custom_nonce     (optional) Custom IV value to use. Default: null.
	 *
	 * @return false|mixed|string
	 */
	public function encrypt( $data, $use_random_nonce = true, $custom_nonce = null ) {
		try {
			if ( ! $use_random_nonce ) {
				$nonce = $custom_nonce ? $custom_nonce : sodium_hex2bin( self::DEFAULT_NONCE );
			} else {
				$nonce = $this->get_random_nonce();
			}
		} catch ( Exception $e ) {
			return false;
		}

		if ( strlen( $nonce ) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			$nonce = hash_hmac( 'sha256', $nonce, self::DEFAULT_NONCE );
		}

		if ( strlen( $nonce ) > SODIUM_CRYPTO_SECRETBOX_KEYBYTES ) {
			$nonce = mb_substr( $nonce, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit' );
		}

		try {
			$encrypted = sodium_crypto_secretbox( $data, $nonce, $this->_secret_key );
			$encrypted = sodium_bin2base64( $nonce . $encrypted, SODIUM_BASE64_VARIANT_ORIGINAL );
			if ( extension_loaded( 'sodium' ) || extension_loaded( 'libsodium' ) ) {
				sodium_memzero( $nonce );
			}
		} catch ( Exception $e ) {
			return false;
		}

		return $encrypted;
	}

	/**
	 * Decrypts data.
	 *
	 * Note: This is for internal use and is not intended for highly-sensitive applications.
	 *
	 * @since 1.0.0
	 *
	 * @param string $data Data to encrypt.
	 *
	 * @return string|null
	 */
	public function decrypt( $data ) {
		try {
			$encrypted = sodium_base642bin( $data, SODIUM_BASE64_VARIANT_ORIGINAL );
		} catch ( Exception $e ) {
			return null;
		}

		$nonce     = mb_substr( $encrypted, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit' );
		$encrypted = mb_substr( $encrypted, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit' );

		try {
			$decrypted = sodium_crypto_secretbox_open( $encrypted, $nonce, $this->_secret_key );
		} catch ( Exception $e ) {
			return null;
		}

		return $decrypted !== false ? $decrypted : null;
	}

	/**
	 * Generates a quick one-way hash of data.
	 *
	 * Note: This is for internal use and is not intended for highly-sensitive applications.
	 *
	 * @since 1.0.0
	 *
	 * @param string $data The data to create a hash of.
	 *
	 * @return string The hash.
	 */
	public function hash( $data ) {
		return hash_hmac( 'sha256', $data, self::DEFAULT_NONCE );
	}

	/**
	 * Returns a random 24-byte nonce.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception
	 *
	 * @return string
	 */
	public function get_random_nonce() {
		return random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
	}
}
