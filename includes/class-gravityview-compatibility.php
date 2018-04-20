<?php
/**
 * Handle issues with plugin and version compatibility
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.12
 */

/**
 * Handle GravityView compatibility notices and fallback shortcodes
 * @since 1.12
 */
class GravityView_Compatibility {

	/**
	 * @var GravityView_Compatibility
	 */
	static public $instance = null;

	/**
	 * @var bool Is Gravity Forms version valid and is Gravity Forms loaded?
	 */
	static public $valid_gravity_forms = false;

	/**
	 * @var bool Is the WordPress installation compatible?
	 */
	static public $valid_wordpress = false;

	/**
	 * @var bool Is the server's PHP version compatible?
	 */
	static public $valid_php = false;

	/**
	 * @var array Holder for notices to be displayed in frontend shortcodes if not valid GF
	 */
	static private $notices = array();

	function __construct() {

		self::$valid_gravity_forms = self::check_gravityforms();

		self::$valid_wordpress = self::check_wordpress();

		self::$valid_php = self::check_php();

		self::check_gf_directory();

		$this->add_hooks();
	}

	function add_hooks() {

		add_filter( 'gravityview/admin/notices', array( $this, 'insert_admin_notices' ) );

		$this->add_fallback_shortcode();
	}

	/**
	 * Add the compatibility notices to the other admin notices
	 * @param array $notices
	 *
	 * @return array
	 */
	function insert_admin_notices( $notices = array() ) {
		return array_merge( $notices, self::$notices );
	}

	/**
	 * @return GravityView_Compatibility
	 */
	public static function getInstance() {
		if( self::$instance ) {
			return self::$instance;
		}
		return new self;
	}

	/**
	 * Is everything compatible with this version of GravityView?
	 *
	 * @deprecated 1.19.4
	 * @see \GV\Plugin::is_compatible() accessible via gravityview()->plugin->is_compatible()
	 *
	 * @return bool
	 */
	public static function is_valid() {
		return gravityview()->plugin->is_compatible();
	}

	/**
	 * Is the version of WordPress compatible?
	 * @since 1.12
	 *
	 * @deprecated 1.19.4
	 * @see \GV\Plugin::is_compatible_wordpress() accessible via gravityview()->plugin->is_compatible_wordpress()
	 */
	private static function is_valid_wordpress() {
		return gravityview()->plugin->is_compatible_wordpress();
	}

	/**
	 * @since 1.12
	 *
	 * @deprecated 1.19.4
	 * @see \GV\Plugin::is_compatible_gravityforms() accessible via gravityview()->plugin->is_compatible_gravityforms()
	 *
	 * @return bool
	 */
	private static function is_valid_gravity_forms() {
		return gravityview()->plugin->is_compatible_gravityforms();
	}

	/**
	 * @since 1.12
	 *
	 * @deprecated 1.19.4
	 * @see \GV\Plugin::is_compatible_php() accessible via gravityview()->plugin->is_compatible_php()
	 *
	 * @return bool
	 */
	private static function is_valid_php() {
		return gravityview()->plugin->is_compatible_php();
	}

	/**
	 * @since 1.12
	 * @return bool
	 */
	private function add_fallback_shortcode() {

		// If Gravity Forms doesn't exist or is outdated, load the admin view class to
		// show the notice, but not load any post types or process shortcodes.
		// Without Gravity Forms, there is no GravityView. Beautiful, really.
		if( ! self::is_valid() ) {

			// If the plugin's not loaded, might as well hide the shortcode for people.
			add_shortcode( 'gravityview', array( $this, '_shortcode_gf_notice') );

		}
	}

	/**
	 * Get admin notices
	 * @since 1.12
	 * @return array
	 */
	public static function get_notices() {
		return self::$notices;
	}

	/**
	 * @since 1.9.2 in gravityview.php
	 * @since 1.12
	 *
	 * @param array $atts
	 * @param null $content
	 * @param string $shortcode
	 *
	 * @return null|string NULL returned if user can't activate plugins. Notice shown with a warning that GF isn't supported.
	 */
	public function _shortcode_gf_notice( $atts = array(), $content = null, $shortcode = 'gravityview' ) {

		if( ! GVCommon::has_cap( 'activate_plugins' ) ) {
			return null;
		}

		$notices = self::get_notices();

		$message = '<div style="border:1px solid red; padding: 15px;"><p style="text-align:center;"><em>' . esc_html__( 'You are seeing this notice because you are an administrator. Other users of the site will see nothing.', 'gravityview') . '</em></p>';
		foreach( (array)$notices as $notice ) {
			$message .= wpautop( $notice['message'] );
		}
		$message .= '</div>';

		return $message;

	}

	/**
	 * Is the version of PHP compatible?
	 *
	 * @since 1.12
	 * @since 1.19.2 Shows a notice if it's compatible with future PHP version requirements
	 *
	 * @return boolean
	 */
	public static function check_php() {

		if ( ! gravityview()->plugin->is_compatible_php() ) {

			self::$notices['php_version'] = array(
				'class' => 'error',
				'message' => sprintf( __( "%sGravityView requires PHP Version %s or newer.%s \n\nYou're using Version %s. Please ask your host to upgrade your server's PHP.", 'gravityview' ), '<h3>', GV_MIN_PHP_VERSION, "</h3>\n\n", '<span style="font-family: Consolas, Courier, monospace;">'.phpversion().'</span>' ),
				'cap' => 'manage_options',
				'dismiss' => 'php_version',
			);

			return false;
		}

		if ( ! gravityview()->plugin->is_compatible_future_php() ) {

			// Show the notice on every update. Yes, annoying, but not as annoying as a plugin breaking.
			$key = sprintf('php_%s_%s', GV_FUTURE_MIN_PHP_VERSION, GravityView_Plugin::version );

			self::$notices[ $key ] = array(
				'class' => 'error',
				'message' => sprintf( __( "%sGravityView will soon require PHP Version %s.%s \n\nYou're using Version %s. Please ask your host to upgrade your server's PHP.", 'gravityview' ), '<h3>', GV_FUTURE_MIN_PHP_VERSION, "</h3>\n\n", '<span style="font-family: Consolas, Courier, monospace;">'.phpversion().'</span>' ),
				'cap' => 'manage_options',
				'dismiss' => $key,
			);

		}

		return true;
	}

	/**
	 * Is WordPress compatible?
	 *
	 * @since 1.12
	 * @return boolean
	 */
	public static function check_wordpress() {
		global $wp_version;

		if ( ! gravityview()->plugin->is_compatible_wordpress() ) {

			self::$notices['wp_version'] = array(
				'class' => 'error',
				'message' => sprintf( __( "%sGravityView requires WordPress %s or newer.%s \n\nYou're using Version %s. Please upgrade your WordPress installation.", 'gravityview' ), '<h3>', GV_MIN_WP_VERSION, "</h3>\n\n", '<span style="font-family: Consolas, Courier, monospace;">'.$wp_version.'</span>' ),
			    'cap' => 'update_core',
				'dismiss' => 'wp_version',
			);

			return false;
		}

		return true;
	}


	/**
	 * Check if Gravity Forms plugin is active and show notice if not.
	 *
	 * @since 1.12
	 *
	 * @access public
	 * @return boolean True: checks have been passed; GV is fine to run; False: checks have failed, don't continue loading
	 */
	public static function check_gravityforms() {

		// Bypass other checks: if the class exists
		if( class_exists( 'GFCommon' ) ) {

			// Does the version meet future requirements?
			if( true === version_compare( GFCommon::$version, GV_FUTURE_MIN_GF_VERSION, ">=" ) ) {
				return true;
			}

			// Does it meet minimum requirements?
			$meets_minimum = gravityview()->plugin->is_compatible_gravityforms();

			if( $meets_minimum ) {
				/* translators: first placeholder is the future required version of Gravity Forms. The second placeholder is the current version of Gravity Forms. */
				$title = __( 'In the future, GravityView will require Gravity Forms Version %s or newer.', 'gravityview' );
				$version = GV_FUTURE_MIN_GF_VERSION;
				$class = 'notice-warning';
			} else {
				/* translators: the placeholder is the required version of Gravity Forms. */
				$title = __( 'GravityView requires Gravity Forms Version %s or newer.', 'gravityview' );
				$version = GV_MIN_GF_VERSION;
				$class = 'error';
			}

			$message = '<h3>' . esc_html( sprintf( $title, $version ) ) . '</h3>';

			/* translators: the placeholder is the current version of Gravity Forms. */
			$message .= '<p>' . sprintf( esc_html__( "You're using Version %s. Please update your Gravity Forms or purchase a license.", 'gravityview' ), '<span style="font-family: Consolas, Courier, monospace;">'.GFCommon::$version.'</span>' ) . '</p>';

			/* translators: In this context, "get" means purchase */
			$message .= '<p><a href="https://gravityview.co/gravityforms/" class="button button-secondary button-large button-hero">' . esc_html__( 'Get the Latest Gravity Forms', 'gravityview' ) . '</a></p>';


			// Show the notice even if the future version requirements aren't met
			self::$notices['gf_version'] = array(
				'class' => $class,
				'message' => $message,
				'cap' => 'update_plugins',
				'dismiss' => 'gf_version_' . $version,
			);

			// Return false if the plugin is not compatible, true if meets minimum
			return $meets_minimum;
		}

		$gf_status = self::get_plugin_status( 'gravityforms/gravityforms.php' );

		/**
		 * The plugin is activated and yet somehow GFCommon didn't get picked up...
		 * OR
		 * It's the Network Admin and we just don't know whether the sites have GF activated themselves.
		 */
		if( true === $gf_status || is_network_admin() ) {
			return true;
		}

		// If GFCommon doesn't exist, assume GF not active
		$return = false;

		switch( $gf_status ) {
			case 'inactive':

				// Required for multisite
				if( ! function_exists('wp_create_nonce') ) {
					require_once ABSPATH . WPINC . '/pluggable.php';
				}

				// Otherwise, throws an error on activation & deactivation "Use of undefined constant LOGGED_IN_COOKIE"
				if( is_multisite() ) {
					wp_cookie_constants();
				}

				$return = false;

				$button = function_exists('is_network_admin') && is_network_admin() ? '<strong><a href="#gravity-forms">' : '<strong><a href="'. wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=gravityforms/gravityforms.php' ), 'activate-plugin_gravityforms/gravityforms.php') . '" class="button button-large">';

				self::$notices['gf_inactive'] = array(
					'class' => 'error',
					'message' => sprintf( __( '%sGravityView requires Gravity Forms to be active. %sActivate Gravity Forms%s to use the GravityView plugin.', 'gravityview' ), '<h3>', "</h3>\n\n". $button, '</a></strong>' ),
					'cap' => 'activate_plugins',
					'dismiss' => 'gf_inactive',
				);

				break;
			default:
				self::$notices['gf_installed'] = array(
					'class' => 'error',
					'message' => sprintf( __( '%sGravityView requires Gravity Forms to be installed in order to run properly. %sGet Gravity Forms%s - starting at $59%s%s', 'gravityview' ), '<h3>', "</h3>\n\n".'<a href="https://gravityview.co/gravityforms/" class="button button-secondary button-large button-hero">' , '<em>', '</em>', '</a>'),
					'cap' => 'install_plugins',
					'dismiss' => 'gf_installed',
				);
				break;
		}

		return $return;
	}

	/**
	 * Check for potential conflicts and let users know about common issues.
	 *
	 * @return void
	 */
	private static function check_gf_directory() {

		if( class_exists( 'GFDirectory' ) ) {
			self::$notices['gf_directory'] = array(
				'class' => 'error is-dismissible',
				'title' => __('Potential Conflict', 'gravityview' ),
				'message' => __( 'GravityView and Gravity Forms Directory are both active. This may cause problems. If you experience issues, disable the Gravity Forms Directory plugin.', 'gravityview' ),
				'dismiss' => 'gf_directory',
				'cap' => 'activate_plugins',
			);
		}

	}

	/**
	 * Check if specified plugin is active, inactive or not installed
	 *
	 * @access public
	 * @static
	 * @param string $location (default: '')
	 * @return boolean|string True: plugin is active; False: plugin file doesn't exist at path; 'inactive' it's inactive
	 */
	public static function get_plugin_status( $location = '' ) {

		if( ! function_exists('is_plugin_active') ) {
			include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if( is_network_admin() && is_plugin_active_for_network( $location ) ) {
			return true;
		}

		if( !is_network_admin() && is_plugin_active( $location ) ) {
			return true;
		}

		if(
			!file_exists( trailingslashit( WP_PLUGIN_DIR ) . $location ) &&
			!file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . $location )
		) {
			return false;
		}

		return 'inactive';
	}

}

GravityView_Compatibility::getInstance();
