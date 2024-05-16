<?php
/**
 * Handle issues with plugin and version compatibility
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.12
 */

/**
 * Handle GravityView compatibility notices and fallback shortcodes
 *
 * @since 1.12
 */
class GravityView_Compatibility {

	/**
	 * @var GravityView_Compatibility
	 */
	public static $instance = null;

	/**
	 * @var bool Is Gravity Forms version valid and is Gravity Forms loaded?
	 */
	public static $valid_gravity_forms = false;

	/**
	 * @var bool Is the WordPress installation compatible?
	 */
	public static $valid_wordpress = false;

	/**
	 * @var array Holder for notices to be displayed in frontend shortcodes if not valid GF
	 */
	private static $notices = array();

	function __construct() {

		self::$valid_gravity_forms = self::check_gravityforms();

		self::$valid_wordpress = self::check_wordpress();

		self::check_php();

		self::check_gf_directory();

		$this->add_hooks();
	}

	function add_hooks() {

		add_filter( 'gravityview/admin/notices', array( $this, 'insert_admin_notices' ) );

		$this->add_fallback_shortcode();
	}

	/**
	 * Add the compatibility notices to the other admin notices
	 *
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
		if ( self::$instance ) {
			return self::$instance;
		}
		return new self();
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
	 *
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
	 * @return bool
	 */
	private function add_fallback_shortcode() {

		// If Gravity Forms doesn't exist or is outdated, load the admin view class to
		// show the notice, but not load any post types or process shortcodes.
		// Without Gravity Forms, there is no GravityView. Beautiful, really.
		if ( ! gravityview()->plugin->is_compatible() ) {

			// If the plugin's not loaded, might as well hide the shortcode for people.
			add_shortcode( 'gravityview', array( $this, '_shortcode_gf_notice' ) );

		}
	}

	/**
	 * Get admin notices
	 *
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
	 * @param array  $atts
	 * @param null   $content
	 * @param string $shortcode
	 *
	 * @return null|string NULL returned if user can't activate plugins. Notice shown with a warning that GF isn't supported.
	 */
	public function _shortcode_gf_notice( $atts = array(), $content = null, $shortcode = 'gravityview' ) {

		if ( ! GVCommon::has_cap( 'activate_plugins' ) ) {
			return null;
		}

		$notices = self::get_notices();

		$message = '<div style="border:1px solid red; padding: 15px;"><p style="text-align:center;"><em>' . esc_html__( 'You are seeing this notice because you are an administrator. Other users of the site will see nothing.', 'gk-gravityview' ) . '</em></p>';
		foreach ( (array) $notices as $notice ) {
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
	 * @return void
	 */
	public static function check_php() {
		if ( ! gravityview()->plugin->is_compatible_future_php() ) {

			// Show the notice on every update. Yes, annoying, but not as annoying as a plugin breaking.
			$key = sprintf( 'php_%s_%s', GV_FUTURE_MIN_PHP_VERSION, GV_PLUGIN_VERSION );

			self::$notices[ $key ] = array(
				'class'   => 'error',
				'message' => sprintf( __( "%1\$sGravityView will soon require PHP Version %2\$s.%3\$s \n\nYou're using Version %4\$s. Please ask your host to upgrade your server's PHP.", 'gk-gravityview' ), '<h3>', GV_FUTURE_MIN_PHP_VERSION, "</h3>\n\n", '<span style="font-family: Consolas, Courier, monospace;">' . phpversion() . '</span>' ),
				'cap'     => 'manage_options',
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

		if ( gravityview()->plugin->is_compatible_future_wordpress() ) {
			return true;
		}

		if ( ! gravityview()->plugin->is_compatible_wordpress() ) {

			self::$notices['wp_version'] = array(
				'class'   => 'error',
				'message' => sprintf( __( "%1\$sGravityView requires WordPress %2\$s or newer.%3\$s \n\nYou're using Version %4\$s. Please upgrade your WordPress installation.", 'gk-gravityview' ), '<h3>', GV_MIN_WP_VERSION, "</h3>\n\n", '<span style="font-family: Consolas, Courier, monospace;">' . $wp_version . '</span>' ),
				'cap'     => 'update_core',
				'dismiss' => 'wp_version',
			);

			return false;
		}

		// Show the notice on every update. Yes, annoying, but not as annoying as a plugin breaking.
		$key = sprintf( 'wp_%s_%s', GV_FUTURE_MIN_WP_VERSION, GV_PLUGIN_VERSION );

		self::$notices[ $key ] = array(
			'class'   => 'notice-warning',
			'message' => sprintf( __( "%1\$sGravityView will soon require WordPress %2\$s%3\$s \n\nYou're using Version %4\$s. Please upgrade your WordPress installation.", 'gk-gravityview' ), '<h3>', GV_FUTURE_MIN_WP_VERSION, "</h3>\n\n", '<span style="font-family: Consolas, Courier, monospace;">' . $wp_version . '</span>' ),
			'cap'     => 'update_core',
			'dismiss' => $key,
		);

		return true;
	}


	/**
	 * Check if Gravity Forms plugin is active and show notice if not.
	 *
	 * @since 1.12
	 *
	 * @return boolean True: checks have been passed; GV is fine to run; False: checks have failed, don't continue loading
	 */
	public static function check_gravityforms() {

		// Bypass other checks: if the class exists
		if ( class_exists( 'GFCommon' ) ) {

			// Does the version meet future requirements?
			if ( true === gravityview()->plugin->is_compatible_future_gravityforms() ) {
				return true;
			}

			// Does it meet minimum requirements?
			$meets_minimum = gravityview()->plugin->is_compatible_gravityforms();

			if ( $meets_minimum ) {
				/* translators: first placeholder is the future required version of Gravity Forms. The second placeholder is the current version of Gravity Forms. */
				$title   = __( 'In the future, GravityView will require Gravity Forms Version %s or newer.', 'gk-gravityview' );
				$version = GV_FUTURE_MIN_GF_VERSION;
				$class   = 'notice-warning';
			} else {
				/* translators: the placeholder is the required version of Gravity Forms. */
				$title   = __( 'GravityView requires Gravity Forms Version %s or newer.', 'gk-gravityview' );
				$version = GV_MIN_GF_VERSION;
				$class   = 'error';
			}

			$message = '<h3>' . esc_html( sprintf( $title, $version ) ) . '</h3>';

			/* translators: the placeholder is the current version of Gravity Forms. */
			$message .= '<p>' . sprintf( esc_html__( "You're using Version %s. Please update your Gravity Forms or purchase a license.", 'gk-gravityview' ), '<span style="font-family: Consolas, Courier, monospace;">' . GFCommon::$version . '</span>' ) . '</p>';

			/* translators: In this context, "get" means purchase */
			$message .= '<p><a href="https://www.gravitykit.com/gravityforms/" class="button button-secondary button-large button-hero">' . esc_html__( 'Get the Latest Gravity Forms', 'gk-gravityview' ) . '</a></p>';

			// Show the notice even if the future version requirements aren't met
			self::$notices['gf_version'] = array(
				'class'   => $class,
				'message' => $message,
				'cap'     => 'update_plugins',
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
		if ( true === $gf_status || is_network_admin() ) {
			return true;
		}

		// If GFCommon doesn't exist, assume GF not active
		$return = false;

		switch ( $gf_status ) {
			case 'inactive':
				// Required for multisite
				if ( ! function_exists( 'wp_create_nonce' ) ) {
					require_once ABSPATH . WPINC . '/pluggable.php';
				}

				// Otherwise, throws an error on activation & deactivation "Use of undefined constant LOGGED_IN_COOKIE"
				if ( is_multisite() ) {
					wp_cookie_constants();
				}

				$return = false;

				$button = function_exists( 'is_network_admin' ) && is_network_admin() ? '<strong><a href="#gravity-forms">' : '<strong><a href="' . wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=gravityforms/gravityforms.php' ), 'activate-plugin_gravityforms/gravityforms.php' ) . '" class="button button-large">';

				self::$notices['gf_inactive'] = array(
					'class'   => 'error',
					'message' => sprintf( __( '%1$sGravityView requires Gravity Forms to be active. %2$sActivate Gravity Forms%3$s to use the GravityView plugin.', 'gk-gravityview' ), '<h3>', "</h3>\n\n" . $button, '</a></strong>' ),
					'cap'     => 'activate_plugins',
					'dismiss' => 'gf_inactive',
				);

				break;
			default:
				self::$notices['gf_installed'] = array(
					'class'   => 'error',
					'message' => sprintf( __( '%1$sGravityView requires Gravity Forms to be installed in order to run properly. %2$sGet Gravity Forms%3$s - starting at $59%4$s%5$s', 'gk-gravityview' ), '<h3>', "</h3>\n\n" . '<a href="https://www.gravitykit.com/gravityforms/" class="button button-secondary button-large button-hero">', '<em>', '</em>', '</a>' ),
					'cap'     => 'install_plugins',
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

		if ( class_exists( 'GFDirectory' ) ) {
			self::$notices['gf_directory'] = array(
				'class'   => 'error is-dismissible',
				'title'   => __( 'Potential Conflict', 'gk-gravityview' ),
				'message' => __( 'GravityView and Gravity Forms Directory are both active. This may cause problems. If you experience issues, disable the Gravity Forms Directory plugin.', 'gk-gravityview' ),
				'dismiss' => 'gf_directory',
				'cap'     => 'activate_plugins',
			);
		}
	}

	/**
	 * Check if specified plugin is active, inactive or not installed
	 *
	 * @param string $location (default: '')
	 *
	 * @return boolean|string True: plugin is active; False: plugin file doesn't exist at path; 'inactive' it's inactive
	 */
	public static function get_plugin_status( $location = '' ) {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		if ( is_network_admin() && is_plugin_active_for_network( $location ) ) {
			return true;
		}

		if ( ! is_network_admin() && is_plugin_active( $location ) ) {
			return true;
		}

		if (
			! file_exists( trailingslashit( WP_PLUGIN_DIR ) . $location ) &&
			! file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . $location )
		) {
			return false;
		}

		return 'inactive';
	}

	/**
	 * Displays a notice on the All Views or New View page when the compatibility requirements for the plugin are not met.
	 *
	 * @since 2.19.7
	 *
	 * @return void
	 */
	public static function override_post_pages_when_compatibility_fails() {
		global $pagenow;

		if ( ! in_array( $pagenow, array( 'post.php', 'edit.php', 'post-new.php' ) ) ) {
			return;
		}

		$display_notices = function ( $hook_data ) {
			global $post;

			if ( ! $post instanceof \WP_Post || 'gravityview' !== $post->post_type ) {
				return $hook_data;
			}

			// We only care about GravityView notices :)
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'network_admin_notices' );

			new GravityView_Admin_Notices();

			/**
			 * Make GravityView notices non-dismissible and display them to all users.
			 *
			 * @param array $notices Array of notices to display.
			 */
			add_filter( 'gravityview/admin/notices', function ( $notices ) {
				$compat_notices = GravityView_Compatibility::get_notices();

				foreach ( $compat_notices as &$notice ) {
					unset( $notice['dismiss'] ); // Make sure the notice is always displayed and is not dismissible.
					unset( $notice['cap'] ); // Display the notice to everyone.
				}

				return array_merge( $notices, $compat_notices );
			} );

			// Hide the "Screen Options" tab.
			add_filter( 'screen_options_show_screen', '__return_false' );

			// Render the wrapper for the page, which will include the notices.
			require_once ABSPATH . 'wp-admin/admin-header.php';
			require_once ABSPATH . 'wp-admin/admin-footer.php';

			exit;
		};

		add_filter( 'bulk_post_updated_messages', $display_notices ); // Fired on All Views page.

		/**
		 * Fired on New View and Edit View pages.
		 * Without this in place, other notices, the Post Title, and the Publish metabox will continue to be displayed.
		 */
		add_filter( 'replace_editor', $display_notices );
	}
}

GravityView_Compatibility::getInstance();
