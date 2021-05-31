<?php
/**
 * Class OptionKeys
 *
 * @package GravityView\TrustedLogin\Client
 *
 * @copyright 2020 Katz Web Services, Inc.
 *
 * @license GPL-2.0-or-later
 * Modified by gravityview on 31-May-2021 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */
namespace GravityView\TrustedLogin;

use \GravityView\TrustedLogin\Config;

// Exit if accessed directly
if ( ! defined('ABSPATH') ) {
	exit;
}

final class OptionKeys {

	/**
	 * @var Config $config
	 */
	private $config;

	/**
	 * @var string $identifier_meta_key The namespaced setting name for storing the unique identifier hash in user meta
	 * @example tl_{vendor/namespace}_id
	 * @since 0.7.0
	 */
	private $identifier_meta_key;

	/**
	 * @var int $expires_meta_key The namespaced setting name for storing the timestamp the user expires
	 * @example tl_{vendor/namespace}_expires
	 * @since 0.7.0
	 */
	private $expires_meta_key;

	/**
	 * @var int $created_by_meta_key The ID of the user who created the TrustedLogin access
	 * @since 0.9.7
	 */
	private $created_by_meta_key;


	/**
	 * @var string $sharable_access_key_option Where the plugin should store the shareable access key
	 * @since 0.9.2
	 * @access public
	 */
	private $sharable_access_key_option;


	public function __construct( Config $config ) {

		$this->config = $config;

	}

	public function init() {

		$namespace = $this->config->ns();



		/**
		 * Filter: Sets the site option name for the Shareable accessKey if it's used
		 *
		 * @since 0.9.2
		 *
		 * @param string $sharable_accesskey_option
		 * @param Config $config
		 */
		$this->sharable_access_key_option = apply_filters(
			'trustedlogin/' . $namespace . '/options/sharable_accesskey',
			'tl_' . $namespace . '_sharable_accesskey',
			$this->config
		);
	}

	/**
	 * Magic Method: Instead of throwing an error when a variable isn't set, return null.
	 * @param  string      $name Key for the data retrieval.
	 * @return mixed|null    The stored data.
	 */
	public function __get( $name ) {
		if( isset( $this->{$name} ) ) {
			return $this->{$name};
		} else {
			return NULL;
		}
	}

}
