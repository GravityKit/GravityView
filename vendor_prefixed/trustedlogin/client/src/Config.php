<?php
/**
 * Class Config
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

use ArrayAccess;
use \Exception;
use \WP_Error;

final class Config {

	/**
	 * @var string[] These namespaces cannot be used, lest they result in confusion.
	 */
	private static $reserved_namespaces = array( 'trustedlogin', 'client', 'vendor', 'admin', 'wordpress' );

	/**
	 * @var array Default settings values
	 * @link https://www.trustedlogin.com/configuration/ Read the configuration settings documentation
	 * @since 1.0.0
	 */
	private $default_settings = array(
		'auth' => array(
			'api_key' => null,
			'license_key' => null,
		),
		'caps' => array(
			'add' => array(),
			'remove' => array(),
		),
		'decay' => WEEK_IN_SECONDS,
		'logging' => array(
			'enabled' => false,
			'directory' => null,
			'threshold' => 'notice',
			'options' => array(
				'extension'      => 'log',
				'dateFormat'     => 'Y-m-d G:i:s.u',
				'filename'       => null, // Overridden in Logging.php
				'flushFrequency' => false,
				'logFormat'      => false,
				'appendContext'  => true,
			),
		),
		'menu' => array(
			'slug' => null,
			'title' => null,
			'priority' => null,
			'icon_url' => '',
			'position' => null,
		),
		'paths' => array(
			'css' => null,
			'js'  => null, // Default is defined in get_default_settings()
		),
		'reassign_posts' => true,
		'require_ssl' => true,
		'role' => 'editor',
		'vendor' => array(
			'namespace' => null,
			'title' => null,
			'email' => null,
			'website' => null,
			'support_url' => null,
			'display_name' => null,
			'logo_url' => null,
			'about_live_access_url' => null,
		),
		'webhook_url' => null,
	);

	/**
	 * @var array $settings Configuration array after parsed and validated
	 * @since 1.0.0
	 */
	private $settings = array();

	/**
	 * Config constructor.
	 *
	 * @param array $settings
	 *
	 * @throws \Exception
	 */
	public function __construct( array $settings = array() ) {

		if ( empty( $settings ) ) {
			throw new Exception( 'Developer: TrustedLogin requires a configuration array. See https://trustedlogin.com/configuration/ for more information.', 400 );
		}

		$this->settings = $settings;
	}


	/**
	 * @return true|\WP_Error[]
	 * @throws \Exception
	 *
	 */
	public function validate() {

		if ( in_array( __NAMESPACE__, array( 'ReplaceMe', 'ReplaceMe\GravityView\TrustedLogin' ) ) && ! defined('TL_DOING_TESTS') ) {
			throw new Exception( 'Developer: make sure to change the namespace for the TrustedLogin class. See https://trustedlogin.com/configuration/ for more information.', 501 );
		}

		$errors = array();

		if ( ! isset( $this->settings['auth']['api_key'] ) ) {
			$errors[] = new \WP_Error( 'missing_configuration', 'You need to set an API key. Get yours at https://app.trustedlogin.com' );
		}

		if ( isset( $this->settings['vendor']['website'] ) && 'https://www.example.com' === $this->settings['vendor']['website'] && ! defined('TL_DOING_TESTS') ) {
			$errors[] = new \WP_Error( 'missing_configuration', 'You need to configure the "website" URL to point to the URL where the Vendor plugin is installed.' );
		}

		foreach ( array( 'namespace', 'title', 'website', 'support_url', 'email' ) as $required_vendor_field ) {
			if ( ! isset( $this->settings['vendor'][ $required_vendor_field ] ) ) {
				$errors[] = new \WP_Error( 'missing_configuration', sprintf( 'Missing required configuration: `vendor/%s`', $required_vendor_field ) );
			}
		}

		if ( isset( $this->settings['decay'] ) ) {
			if ( ! is_int( $this->settings['decay'] ) ) {
				$errors[] = new \WP_Error( 'invalid_configuration', 'Decay must be an integer (number of seconds).' );
			} elseif ( $this->settings['decay'] > MONTH_IN_SECONDS ) {
				$errors[] = new \WP_Error( 'invalid_configuration', 'Decay must be less than or equal to 30 days.' );
			} elseif ( $this->settings['decay'] < DAY_IN_SECONDS ) {
				$errors[] = new \WP_Error( 'invalid_configuration', 'Decay must be greater than 1 day.' );
			}
		}

		if ( isset( $this->settings['vendor']['namespace'] ) ) {

			/**
			 * This seems like a reasonable max limit on the ns length.
			 * @see https://developer.wordpress.org/reference/functions/set_transient/#more-information
			 */
			if ( strlen( $this->settings['vendor']['namespace'] ) > 96 ) {
				$errors[] = new \WP_Error( 'invalid_configuration', 'Namespace length must be shorter than 96 characters.' );
			}

			if ( in_array( strtolower( $this->settings['vendor']['namespace'] ), self::$reserved_namespaces, true ) ) {
				$errors[] = new \WP_Error( 'invalid_configuration', 'The defined namespace is reserved.' );
			}
		}

		if ( isset( $this->settings['vendor'][ 'email' ] ) && ! filter_var( $this->settings['vendor'][ 'email' ], FILTER_VALIDATE_EMAIL ) ) {
			$errors[] = new \WP_Error( 'invalid_configuration', 'An invalid `vendor/email` setting was passed to the TrustedLogin Client.' );
		}

		// TODO: Add ns collision check?

		foreach ( array( 'webhook_url', 'vendor/support_url', 'vendor/website' ) as $settings_key ) {
			$value = $this->get_setting( $settings_key, '', $this->settings );
			$url   = wp_kses_bad_protocol( $value, array( 'http', 'https' ) );
			if ( $value && ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				$errors[] = new \WP_Error(
					'invalid_configuration',
					sprintf( 'An invalid `%s` setting was passed to the TrustedLogin Client: %s',
						$settings_key,
						print_r( $this->get_setting( $settings_key, null, $this->settings ), true )
					)
				);
			}
		}

		$added_caps = $this->get_setting( 'caps/add', array(), $this->settings );

		foreach ( SupportRole::$prevented_caps as $invalid_cap ) {
			if ( array_key_exists( $invalid_cap, $added_caps ) ) {
				$errors[] = new \WP_Error( 'invalid_configuration', 'TrustedLogin users cannot be allowed to: ' . $invalid_cap );
			}
		}

		if ( $errors ) {
			$error_text = array();
			foreach ( $errors as $error ) {
				if ( is_wp_error( $error ) ) {
					$error_text[] = $error->get_error_message();
				}
			}

			$exception_text = 'Invalid TrustedLogin Configuration. Learn more at https://www.trustedlogin.com/configuration/';
			$exception_text .= "\n- " . implode( "\n- ", $error_text );

			throw new Exception( $exception_text, 406 );
		}

		return true;
	}

	/**
	 * Returns a timestamp that is the current time + decay time setting
	 *
	 * Note: This is a server timestamp, not a WordPress timestamp
	 *
	 * @param int $decay_time If passed, override the `decay` setting
	 * @param bool $gmt Whether to use server time (false) or GMT time (true). Default: false.
	 *
	 * @return int|false Timestamp in seconds. Default is WEEK_IN_SECONDS from creation (`time()` + 604800). False if no expiration.
	 */
	public function get_expiration_timestamp( $decay_time = null, $gmt = false ) {

		if ( is_null( $decay_time ) ) {
			$decay_time = $this->get_setting( 'decay' );
		}

		if ( 0 === $decay_time ) {
			return false;
		}

		$time = current_time( 'timestamp', $gmt );

		return $time + (int) $decay_time;
	}

	/**
	 * Returns the display name for the vendor; otherwise, the title
	 *
	 * @return string
	 */
	public function get_display_name() {
		return $this->get_setting( 'vendor/display_name', $this->get_setting( 'vendor/title', '' ) );
	}

	/**
	 * Validate and initialize settings array passed to the Client contructor
	 *
	 * @param array|string $config Configuration array or JSON-encoded configuration array
	 *
	 * @return bool|WP_Error[] true: Initialization succeeded; array of WP_Error objects if there are any issues.
	 */
	protected function parse_settings( $config ) {

		if ( is_string( $config ) ) {
			$config = json_decode( $config, true );
		}

		if ( ! is_array( $config ) || empty( $config ) ) {
			return array( new \WP_Error( 'empty_configuration', 'Configuration array cannot be empty. See https://www.trustedlogin.com/configuration/ for more information.' ) );
		}

		$defaults = $this->get_default_settings();

		$filtered_config = array_filter( $config, array( $this, 'is_not_null' ) );

		return shortcode_atts( $defaults, $filtered_config );
	}

	/**
	 * Filter out null input values
	 *
	 * @internal Used for parsing settings
	 *
	 * @param mixed $input Input to test against.
	 *
	 * @return bool True: not null. False: null
	 */
	public function is_not_null( $input ) {
		return ! is_null( $input );
	}

	/**
	 * Gets the default settings for the Client and define dynamic defaults (like paths/css and paths/js)
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of default settings.
	 */
	public function get_default_settings() {

		$default_settings = $this->default_settings;

		$default_settings['paths']['css'] = plugin_dir_url( __FILE__ ) . 'assets/trustedlogin.css';
		$default_settings['paths']['js']  = plugin_dir_url( __FILE__ ) . 'assets/trustedlogin.js';

		return $default_settings;
	}

	/**
	 * @return string Vendor namespace, sanitized with dashes
	 */
	public function ns() {

		static $namespace;

		if ( ! $namespace ) {
			$ns = $this->get_setting( 'vendor/namespace' );

			$namespace = sanitize_title_with_dashes( $ns );
		}

		return $namespace;
	}

	/**
	 * Helper Function: Get a specific setting or return a default value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The setting to fetch, nested results are delimited with forward slashes (eg vendor/name => settings['vendor']['name'])
	 * @param mixed $default - if no setting found or settings not init, return this value.
	 * @param array $settings Pass an array to fetch value for instead of using the default settings array
	 *
	 * @return string|array
	 */
	public function get_setting( $key, $default = null, $settings = array() ) {

		if ( empty( $settings ) ) {
			$settings = $this->settings;
		}

		if ( is_null( $default ) ) {
			$default = $this->get_multi_array_value( $this->get_default_settings(), $key );
		}

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return $default;
		}

		return $this->get_multi_array_value( $settings, $key, $default );
	}

	/**
	 * Gets a specific property value within a multidimensional array.
	 *
	 * @param array $array The array to search in.
	 * @param string $name The name of the property to find.
	 * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to null.
	 *
	 * @return null|string|mixed The value
	 */
	private function get_multi_array_value( $array, $name, $default = null ) {

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		$names = explode( '/', $name );
		$val   = $array;
		foreach ( $names as $current_name ) {
			$val = $this->get_array_value( $val, $current_name, $default );
		}

		return $val;
	}

	/**
	 * Get a specific property of an array without needing to check if that property exists.
	 *
	 * Provide a default value if you want to return a specific value if the property is not set.
	 *
	 * @param array $array Array from which the property's value should be retrieved.
	 * @param string $prop Name of the property to be retrieved.
	 * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to null.
	 *
	 * @return null|string|mixed The value
	 */
	private function get_array_value( $array, $prop, $default = null ) {
		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		if ( isset( $array[ $prop ] ) ) {
			$value = $array[ $prop ];
		} else {
			$value = $default;
		}

		$value_is_zero = 0 === $value;

		return ( empty( $value ) && ! $value_is_zero ) && $default !== null ? $default : $value;
	}

	/**
	 * Checks whether SSL requirements are met.
	 *
	 * @since 1.0.0
	 *
	 * @return bool  Whether the vendor-defined SSL requirements are met.
	 */
	public function meets_ssl_requirement() {

		$return = true;

		if ( $this->get_setting( 'require_ssl', true ) && ! is_ssl() ) {
			$return = false;
		}

		/**
		 * @internal Do not rely on this!!!!
		 * @todo Remove this
		 * @param bool $return Does this site meet the SSL requirement?
		 */
		return apply_filters( 'trustedlogin/' . $this->ns() . '/meets_ssl_requirement', $return );
	}

}
