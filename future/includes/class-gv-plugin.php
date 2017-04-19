<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The GravityView WordPress plugin class.
 *
 * Contains functionality related to GravityView being
 * a WordPress plugin and doing WordPress pluginy things.
 *
 * Accessible via gravityview()->plugin
 */
final class Plugin {
	/**
	 * @var string The plugin version.
	 *
	 * @api
	 * @since future
	 */
	public $version = 'future';

	/**
	 * @var string Minimum WordPress version.
	 *
	 * GravityView requires at least this version of WordPress to function properly.
	 */
	private static $min_wp_version = '4.0';

	/**
	 * @var string Minimum Gravity Forms version.
	 *
	 * GravityView requires at least this version of Gravity Forms to function properly.
	 */
	private static $min_gf_version = '1.9.14';

	/**
	 * @var string Minimum PHP version.
	 *
	 * GravityView requires at least this version of PHP to function properly.
	 */
	private static $min_php_version = '5.3.0';

	/**
	 * @var string|bool Minimum future PHP version.
	 *
	 * GravityView will require this version of PHP soon. False if no future PHP version changes are planned.
	 */
	private static $future_min_php_version = false;

	/**
	 * @var string|bool Minimum future Gravity Forms version.
	 *
	 * GravityView will require this version of Gravity Forms soon. False if no future Gravity Forms version changes are planned.
	 */
	private static $future_min_gf_version = false;

	/**
	 * @var \GV\Plugin The \GV\Plugin static instance.
	 */
	private static $__instance = null;

	/**
	 * Get the global instance of \GV\Plugin.
	 *
	 * @return \GV\Plugin The global instance of GravityView Plugin.
	 */
	public static function get() {
		if ( ! self::$__instance instanceof self ) {
			self::$__instance = new self;
		}
		return self::$__instance;
	}

	/**
	 * Register hooks that are fired when the plugin is activated and deactivated.
	 *
	 * @return void
	 */
	public function register_activation_hooks() {
		register_activation_hook( $this->dir( 'gravityview.php' ), array( $this, 'activate' ) );
		register_deactivation_hook( $this->dir( 'gravityview.php' ), array( $this, 'deactivate' ) );
	}

	/**
	 * Plugin activation function.
	 *
	 * @internal
	 * @return void
	 */
	public function activate() {
		/** Register the gravityview post type upon WordPress core init. */
		require_once $this->dir( 'future/includes/class-gv-view.php' );
		View::register_post_type();

		/** Add the entry rewrite endpoint. */
		require_once $this->dir( 'future/includes/class-gv-entry.php' );
		Entry::add_rewrite_endpoint();

		/** Flush all URL rewrites. */
		flush_rewrite_rules();

		update_option( 'gv_version', \GravityView_Plugin::version );
	}

	/**
	 * Plugin deactivation function.
	 *
	 * @internal
	 * @return void
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Retrieve an absolute  path within the Gravity Forms plugin directory.
	 *
	 * @api
	 * @since future
	 *
	 * @param string $path Optional. Append this extra path component.
	 * @return string The absolute path to the plugin directory.
	 */
	public function dir( $path = '' ) {
		return GRAVITYVIEW_DIR . ltrim( $path, '/' );
	}

	/**
	 * Retrieve a URL within the Gravity Forms plugin directory.
	 *
	 * @api
	 * @since future
	 *
	 * @param string $path Optional. Extra path appended to the URL.
	 * @return string The URL to this plugin, with trailing slash.
	 */
	public function url( $path = '/' ) {
		return plugins_url( $path, $this->dir( 'gravityview.php' ) );
	}

	/**
	 * Is everything compatible with this version of GravityView?
	 *
	 * @api
	 * @since future
	 *
	 * @return bool
	 */
	public function is_compatible() {
		return
			$this->is_compatible_php()
			&& $this->is_compatible_wordpress()
			&& $this->is_compatible_gravityforms();
	}

	/**
	 * Is this version of GravityView compatible with the current version of PHP?
	 *
	 * @api
	 * @since future
	 *
	 * @return bool true if compatible, false otherwise.
	 */
	public function is_compatible_php() {
		return version_compare( $this->get_php_version(), self::$min_php_version, '>=' );
	}

	/**
	 * Is this version of GravityView compatible with the current version of WordPress?
	 *
	 * @api
	 * @since future
	 *
	 * @return bool true if compatible, false otherwise.
	 */
	public function is_compatible_wordpress() {
		return version_compare( $this->get_wordpress_version(), self::$min_wp_version, '>=' );
	}

	/**
	 * Is this version of GravityView compatible with the current version of Gravity Forms?
	 *
	 * @api
	 * @since future
	 *
	 * @return bool true if compatible, false otherwise (or not active/installed).
	 */
	public function is_compatible_gravityforms() {
		$version = $this->get_gravityforms_version();
		return $version ? version_compare( $version, self::$min_gf_version, '>=' ) : false;
	}

	/**
	 * Retrieve the current PHP version.
	 *
	 * Overridable with GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE during testing.
	 *
	 * @return string The version of PHP.
	 */
	private function get_php_version() {
		return ! empty( $GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] ) ?
			$GLOBALS['GRAVITYVIEW_TESTS_PHP_VERSION_OVERRIDE'] : phpversion();
	}

	/**
	 * Retrieve the current WordPress version.
	 *
	 * Overridable with GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE during testing.
	 *
	 * @return string The version of WordPress.
	 */
	private function get_wordpress_version() {
		return ! empty( $GLOBALS['GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE'] ) ?
			$GLOBALS['GRAVITYVIEW_TESTS_WP_VERSION_OVERRIDE'] : $GLOBALS['wp_version'];
	}

	/**
	 * Retrieve the current Gravity Forms version.
	 *
	 * Overridable with GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE during testing.
	 *
	 * @return string|null The version of Gravity Forms or null if inactive.
	 */
	private function get_gravityforms_version() {
		if ( ! class_exists( '\GFCommon' ) || ! empty( $GLOBALS['GRAVITYVIEW_TESTS_GF_INACTIVE_OVERRIDE'] ) ) {
			gravityview()->log->error( 'Gravity Forms is inactive or not installed.' );
			return null;
		}

		return ! empty( $GLOBALS['GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE'] ) ?
			$GLOBALS['GRAVITYVIEW_TESTS_GF_VERSION_OVERRIDE'] : \GFCommon::$version;
	}

	private function __clone() { }

	private function __wakeup() { }
}
