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
 * @since 1.11.3
 */

/**
 * Handle GravityView compatibility notices and fallback shortcodes
 * @since 1.11.3
 */
class GravityView_Compatibility {

	/**
	 * @var GravityView_Compatibility
	 */
	static public $instance;

	/**
	 * @var bool Is Gravity Forms version valid and is Gravity Forms loaded?
	 */
	static public $valid_gravity_forms = false;

	/**
	 * @var bool Is the WordPress installation compatible?
	 */
	static public $valid_wordpress = false;

	/**
	 * @var array Holder for notices to be displayed in frontend shortcodes if not valid GF
	 */
	static private $notices = array();

	function __construct() {

		self::$valid_gravity_forms = self::check_gravityforms();

		self::$valid_wordpress = self::check_wordpress();

		$this->add_fallback_shortcode();
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
	 * @return bool
	 */
	static function is_valid() {
		return self::is_valid_gravity_forms() && self::is_valid_wordpress();
	}

	/**
	 * Is the version of WordPress compatible?
	 * @since 1.11.3
	 */
	static function is_valid_wordpress() {
		return self::$valid_wordpress;
	}

	/**
	 * @since 1.11.3
	 * @return bool
	 */
	static function is_valid_gravity_forms() {
		return self::$valid_gravity_forms;
	}

	/**
	 * @since 1.11.3
	 * @return bool
	 */
	function add_fallback_shortcode() {

		// If Gravity Forms doesn't exist or is outdated, load the admin view class to
		// show the notice, but not load any post types or process shortcodes.
		// Without Gravity Forms, there is no GravityView. Beautiful, really.
		if( ! self::is_valid() ) {

			// If the plugin's not loaded, might as well hide the shortcode for people.
			add_shortcode( 'gravityview', array( $this, '_shortcode_gf_notice'), 10, 3 );

		}
	}

	/**
	 * Get admin notices
	 * @since 1.11.3
	 * @return array
	 */
	public static function get_notices() {
		return self::$notices;
	}

	/**
	 * @since 1.9.2 in gravityview.php
	 * @since 1.11.3
	 *
	 * @param array $atts
	 * @param null $content
	 * @param string $shortcode
	 *
	 * @return null|string NULL returned if user can't manage options. Notice shown with a warning that GF isn't supported.
	 */
	public function _shortcode_gf_notice( $atts = array(), $content = null, $shortcode = 'gravityview' ) {

		if( ! current_user_can('manage_options') ) {
			return null;
		}

		$notices = self::get_notices();

		$message = '<div style="border:1px solid #ccc; padding: 15px;"><p><em>' . esc_html__( 'You are seeing this notice because you are an administrator. Other users of the site will see nothing.', 'gravityview') . '</em></p>';
		foreach( (array)$notices as $notice ) {
			$message .= wpautop( $notice['message'] );
		}
		$message .= '</div>';

		return $message;

	}

	/**
	 * Is WordPress compatible?
	 *
	 * @since 1.11.3
	 * @return boolean
	 */
	public static function check_wordpress() {
		global $wp_version;

		if( version_compare( $wp_version, GV_MIN_WP_VERSION ) <= 0 ) {

			self::$notices['wp_version'] = array(
				'class' => 'error',
				'message' => sprintf( __( "%sGravityView requires WordPress %s or newer.%s \n\nYou're using Version %s. Please upgrade your WordPress installation.", 'gravityview' ), '<h3>', GV_MIN_WP_VERSION, "</h3>\n\n", '<span style="font-family: Consolas, Courier, monospace;">'.$wp_version.'</span>' )
			);

			return false;
		}

		return true;
	}


	/**
	 * Check if Gravity Forms plugin is active and show notice if not.
	 *
	 * @since 1.11.3
	 *
	 * @access public
	 * @return boolean True: checks have been passed; GV is fine to run; False: checks have failed, don't continue loading
	 */
	public static function check_gravityforms() {

		// Bypass other checks: if the class exists
		if( class_exists( 'GFCommon' ) ) {

			// and the version's right, we're good.
			if( true === version_compare( GFCommon::$version, GV_MIN_GF_VERSION, ">=" ) ) {
				return true;
			}

			// Or the version's wrong
			self::$notices['gf_version'] = array(
				'class' => 'error',
				'message' => sprintf( __( "%sGravityView requires Gravity Forms Version %s or newer.%s \n\nYou're using Version %s. Please update your Gravity Forms or purchase a license. %sGet Gravity Forms%s - starting at $39%s%s", 'gravityview' ), '<h3>', GV_MIN_GF_VERSION, "</h3>\n\n", '<span style="font-family: Consolas, Courier, monospace;">'.GFCommon::$version.'</span>', "\n\n".'<a href="http://katz.si/gravityforms" class="button button-secondary button-large button-hero">' , '<em>', '</em>', '</a>')
			);

			return false;
		}

		$gf_status = self::get_plugin_status( 'gravityforms/gravityforms.php' );

		// If GFCommon doesn't exist, assume GF not active
		$return = false;

		switch( $gf_status ) {
			case 'inactive':
				$return = false;
				self::$notices['gf_inactive'] = array( 'class' => 'error', 'message' => sprintf( __( '%sGravityView requires Gravity Forms to be active. %sActivate Gravity Forms%s to use the GravityView plugin.', 'gravityview' ), '<h3>', "</h3>\n\n".'<strong><a href="'. wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=gravityforms/gravityforms.php' ), 'activate-plugin_gravityforms/gravityforms.php') . '" class="button button-large">', '</a></strong>' ) );
				break;
			default:
				/**
				 * The plugin is activated and yet somehow GFCommon didn't get picked up...
				 */
				if( $gf_status === true ) {
					$return = true;
				} else {
					self::$notices['gf_installed'] = array( 'class' => 'error', 'message' => sprintf( __( '%sGravityView requires Gravity Forms to be installed in order to run properly. %sGet Gravity Forms%s - starting at $39%s%s', 'gravityview' ), '<h3>', "</h3>\n\n".'<a href="http://katz.si/gravityforms" class="button button-secondary button-large button-hero">' , '<em>', '</em>', '</a>') );
				}
				break;
		}

		return $return;
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

		if( is_plugin_active( $location ) ) {
			return true;
		}

		if(
			!file_exists( trailingslashit( WP_PLUGIN_DIR ) . $location ) &&
			!file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . $location )
		) {
			return false;
		}

		if( is_plugin_inactive( $location ) ) {
			return 'inactive';
		}
	}

}

GravityView_Compatibility::getInstance();
