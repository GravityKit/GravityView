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
	 * @since 2.0
	 */
	public static $version = GV_PLUGIN_VERSION;

	/**
	 * @var string Minimum WordPress version.
	 *
	 * GravityView requires at least this version of WordPress to function properly.
	 */
	private static $min_wp_version = GV_MIN_WP_VERSION;

	/**
	 * @var string Minimum Gravity Forms version.
	 *
	 * GravityView requires at least this version of Gravity Forms to function properly.
	 */
	public static $min_gf_version = GV_MIN_GF_VERSION;

	/**
	 * @var string Minimum PHP version.
	 *
	 * GravityView requires at least this version of PHP to function properly.
	 */
	private static $min_php_version = GV_MIN_PHP_VERSION;

	/**
	 * @var string|bool Minimum future PHP version.
	 *
	 * GravityView will require this version of PHP soon. False if no future PHP version changes are planned.
	 */
	private static $future_min_php_version = GV_FUTURE_MIN_PHP_VERSION;

	/**
	 * @var string|bool Minimum future Gravity Forms version.
	 *
	 * GravityView will require this version of Gravity Forms soon. False if no future Gravity Forms version changes are planned.
	 */
	private static $future_min_gf_version = GV_FUTURE_MIN_GF_VERSION;

	/**
	 * @var \GV\Plugin The \GV\Plugin static instance.
	 */
	private static $__instance = null;

	/**
	 * @var \GV\Addon_Settings The plugin "addon" settings.
	 *
	 * @api
	 * @since 2.0
	 */
	public $settings;

	/**
	 * @var string The GFQuery functionality identifier.
	 */
	const FEATURE_GFQUERY = 'gfquery';

	/**
	 * @var string The joins functionality identifier.
	 */
	const FEATURE_JOINS = 'joins';

	/**
	 * @var string The unions functionality identifier.
	 */
	const FEATURE_UNIONS = 'unions';

	/**
	 * @var string The REST API functionality identifier.
	 */
	const FEATURE_REST  = 'rest_api';

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


	private function __construct() {
		/**
		 * Load translations.
		 */
		add_action( 'init', array( $this, 'load_textdomain' ) );

		/**
		 * Load some frontend-related legacy files.
		 */
		add_action( 'gravityview/loaded', array( $this, 'include_legacy_frontend' ) );

		/**
		 * GFAddOn-backed settings, licensing.
		 */
		add_action( 'plugins_loaded', array( $this, 'load_license_settings' ) );
	}

	public function load_license_settings() {
		require_once $this->dir( 'future/includes/class-gv-license-handler.php' );
		require_once $this->dir( 'future/includes/class-gv-settings-addon.php' );
		if ( class_exists( '\GV\Addon_Settings' ) ) {
			$this->settings = new Addon_Settings();
			include_once $this->dir( 'includes/class-gravityview-settings.php' );
		} else {
			gravityview()->log->notice( '\GV\Addon_Settings not loaded. Missing \GFAddOn.' );
		}
	}
	
	/**
	 * Check whether GravityView is network activated.
	 *
	 * @return bool Whether it's network activated or not.
	 */
	public static function is_network_activated() {
		return is_multisite() && ( function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( 'gravityview/gravityview.php' ) );
	}

	/**
	 * Include more legacy stuff.
	 *
	 * @param boolean $force Whether to force the includes.
	 *
	 * @return void
	 */
	public function include_legacy_frontend( $force = false ) {

		if ( gravityview()->request->is_admin() && ! $force ) {
			return;
		}

		include_once $this->dir( 'includes/class-gravityview-image.php' );
		include_once $this->dir( 'includes/class-template.php' );
		include_once $this->dir( 'includes/class-api.php' );
		include_once $this->dir( 'includes/class-frontend-views.php' );
		include_once $this->dir( 'includes/class-gravityview-change-entry-creator.php' );

		/**
		 * @action `gravityview_include_frontend_actions` Triggered after all GravityView frontend files are loaded
		 *
		 * @deprecated Use `gravityview/loaded` along with \GV\Request::is_admin(), etc.
		 *
		 * Nice place to insert extensions' frontend stuff
		 */
		do_action( 'gravityview_include_frontend_actions' );
	}

	/**
	 * Load more legacy core files.
	 *
	 * @return void
	 */
	public function include_legacy_core() {

		if ( ! class_exists( '\GravityView_Extension' ) ) {
			include_once $this->dir( 'includes/class-gravityview-extension.php' );
		}

		// Load fields
		include_once $this->dir( 'includes/fields/class-gravityview-fields.php' );
		include_once $this->dir( 'includes/fields/class-gravityview-field.php' );

		// Load all field files automatically
		foreach ( glob( $this->dir( 'includes/fields/class-gravityview-field*.php' ) ) as $gv_field_filename ) {
			include_once $gv_field_filename;
		}

		include_once $this->dir( 'includes/class-gravityview-entry-approval-status.php' );
		include_once $this->dir( 'includes/class-gravityview-entry-approval.php' );

		include_once $this->dir( 'includes/class-gravityview-entry-notes.php' );
		include_once $this->dir( 'includes/load-plugin-and-theme-hooks.php' );

		// Load Extensions
		// @todo: Convert to a scan of the directory or a method where this all lives
		include_once $this->dir( 'includes/extensions/edit-entry/class-edit-entry.php' );
		include_once $this->dir( 'includes/extensions/delete-entry/class-delete-entry.php' );
		include_once $this->dir( 'includes/extensions/entry-notes/class-gravityview-field-notes.php' );

		// Load WordPress Widgets
		include_once $this->dir( 'includes/wordpress-widgets/register-wordpress-widgets.php' );

		// Load GravityView Widgets
		include_once $this->dir( 'includes/widgets/register-gravityview-widgets.php' );

		// Add oEmbed
		include_once $this->dir( 'includes/class-api.php' );
		include_once $this->dir( 'includes/class-oembed.php' );

		// Add logging
		include_once $this->dir( 'includes/class-gravityview-logging.php' );

		include_once $this->dir( 'includes/class-ajax.php' );
		include_once $this->dir( 'includes/class-gravityview-html-elements.php' );
		include_once $this->dir( 'includes/class-frontend-views.php' );
		include_once $this->dir( 'includes/class-gravityview-admin-bar.php' );
		include_once $this->dir( 'includes/class-gravityview-entry-list.php' );
		include_once $this->dir( 'includes/class-gravityview-merge-tags.php'); /** @since 1.8.4 */
		include_once $this->dir( 'includes/class-data.php' );
		include_once $this->dir( 'includes/class-gravityview-shortcode.php' );
		include_once $this->dir( 'includes/class-gravityview-entry-link-shortcode.php' );
		include_once $this->dir( 'includes/class-gvlogic-shortcode.php' );
		include_once $this->dir( 'includes/presets/register-default-templates.php' );

		if ( class_exists( '\GFFormsModel' ) ) {
			include_once $this->dir( 'includes/class-gravityview-gfformsmodel.php' );
		}
	}

	/**
	 * Load the translations.
	 *
	 * Order of look-ups:
	 *
	 * 1. /wp-content/languages/plugins/gravityview-{locale}.mo (loaded by WordPress Core)
	 * 2. /wp-content/mu-plugins/gravityview-{locale}.mo
	 * 3. /wp-content/mu-plugins/languages/gravityview-{locale}.mo
	 * 4. /wp-content/plugins/gravityview/languages/gravityview-{locale}.mo
	 *
	 * @return void
	 */
	public function load_textdomain() {

		$domain = 'gravityview';

		// 1. /wp-content/languages/plugins/gravityview-{locale}.mo (loaded by WordPress Core)
		if ( is_textdomain_loaded( $domain ) ) {
			return;
		}

		// 2. /wp-content/languages/plugins/gravityview-{locale}.mo
		// 3. /wp-content/mu-plugins/plugins/languages/gravityview-{locale}.mo
		$loaded = load_muplugin_textdomain( $domain, '/languages/' );

		if ( $loaded ) {
			return;
		}

		// 4. /wp-content/plugins/gravityview/languages/gravityview-{locale}.mo
		$loaded = load_plugin_textdomain( $domain, false, $this->relpath( '/languages/' ) );

		if ( $loaded ) {
			return;
		}

		// Pre-4.6 loading
		// TODO: Remove when GV minimum version is WordPress 4.6.0
		$locale = apply_filters( 'plugin_locale', ( ( function_exists('get_user_locale') && is_admin() ) ? get_user_locale() : get_locale() ), 'gravityview' );

		$loaded = load_textdomain( 'gravityview', sprintf( '%s/%s-%s.mo', $this->dir('languages'), $domain, $locale ) );

		if( $loaded ) {
			return;
		}

		gravityview()->log->error( sprintf( 'Unable to load textdomain for %s locale.', $locale ) );
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
		gravityview();

		if ( ! $this->is_compatible() ) {
			return;
		}

		/** Register the gravityview post type upon WordPress core init. */
		require_once $this->dir( 'future/includes/class-gv-view.php' );
		View::register_post_type();

		/** Add the entry rewrite endpoint. */
		require_once $this->dir( 'future/includes/class-gv-entry.php' );
		Entry::add_rewrite_endpoint();

		/** Flush all URL rewrites. */
		flush_rewrite_rules();

		update_option( 'gv_version', self::$version );

		/** Add the transient to redirect to configuration page. */
		set_transient( '_gv_activation_redirect', true, 60 );

		/** Clear settings transient. */
		delete_transient( 'gravityview_edd-activate_valid' );

		\GravityView_Roles_Capabilities::get_instance()->add_caps();
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
	 * Retrieve an absolute path within the GravityView plugin directory.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @param string $path Optional. Append this extra path component.
	 * @return string The absolute path to the plugin directory.
	 */
	public function dir( $path = '' ) {
		return wp_normalize_path( GRAVITYVIEW_DIR . ltrim( $path, '/' ) );
	}

	/**
	 * Retrieve a relative path to the GravityView plugin directory from the WordPress plugin directory
	 *
	 * @api
	 * @since 2.2.3
	 *
	 * @param string $path Optional. Append this extra path component.
	 * @return string The relative path to the plugin directory from the plugin directory.
	 */
	public function relpath( $path = '' ) {

		$dirname = trailingslashit( dirname( plugin_basename( GRAVITYVIEW_FILE ) ) );

		return wp_normalize_path( $dirname . ltrim( $path, '/' ) );
	}

	/**
	 * Retrieve a URL within the GravityView plugin directory.
	 *
	 * @api
	 * @since 2.0
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
	 * @since 2.0
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
	 * @since 2.0
	 *
	 * @return bool true if compatible, false otherwise.
	 */
	public function is_compatible_php() {
		return version_compare( $this->get_php_version(), self::$min_php_version, '>=' );
	}

	/**
	 * Is this version of GravityView compatible with the future required version of PHP?
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return bool true if compatible, false otherwise.
	 */
	public function is_compatible_future_php() {
		return version_compare( $this->get_php_version(), self::$future_min_php_version, '>=' );
	}

	/**
	 * Is this version of GravityView compatible with the current version of WordPress?
	 *
	 * @api
	 * @since 2.0
	 *
	 * @param string $version Version to check against; otherwise uses GV_MIN_WP_VERSION
	 *
	 * @return bool true if compatible, false otherwise.
	 */
	public function is_compatible_wordpress( $version = null ) {

		if( ! $version ) {
			$version = self::$min_wp_version;
		}

		return version_compare( $this->get_wordpress_version(), $version, '>=' );
	}

	/**
	 * Is this version of GravityView compatible with the current version of Gravity Forms?
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return bool true if compatible, false otherwise (or not active/installed).
	 */
	public function is_compatible_gravityforms() {
		$version = $this->get_gravityforms_version();
		return $version ? version_compare( $version, self::$min_gf_version, '>=' ) : false;
	}

	/**
	 * Is this version of GravityView compatible with the future version of Gravity Forms?
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return bool true if compatible, false otherwise (or not active/installed).
	 */
	public function is_compatible_future_gravityforms() {
		$version = $this->get_gravityforms_version();
		return $version ? version_compare( $version, self::$future_min_gf_version, '>=' ) : false;
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

	/**
	 * Feature support detection.
	 *
	 * @param string $feature Feature name. Check FEATURE_* class constants.
	 *
	 * @return boolean
	 */
	public function supports( $feature ) {
		if ( ! is_null( $supports = apply_filters( "gravityview/plugin/feature/$feature", null ) ) ) {
			return $supports;
		}

		switch ( $feature ):
				case self::FEATURE_GFQUERY:
					return class_exists( '\GF_Query' );
				case self::FEATURE_JOINS:
				case self::FEATURE_UNIONS:
					return apply_filters( 'gravityview/query/class', false ) === '\GF_Patched_Query';
				case self::FEATURE_REST:
					return class_exists( '\WP_REST_Controller' );
			default:
				return false;
		endswitch;
	}

	/**
	 * Delete GravityView Views, settings, roles, caps, etc.
	 *
	 * @return void
	 */
	public function uninstall() {
		global $wpdb;

		$suppress = $wpdb->suppress_errors();

		/**
		 * Posts.
		 */
		$items = get_posts( array(
			'post_type' => 'gravityview',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields' => 'ids'
		) );

		foreach ( $items as $item ) {
			wp_delete_post( $item, true );
		}

		/**
		 * Meta.
		 */
		$tables = array();

		if ( version_compare( \GravityView_GFFormsModel::get_database_version(), '2.3-dev-1', '>=' ) ) {
			$tables []= \GFFormsModel::get_entry_meta_table_name();
		}
		$tables []= \GFFormsModel::get_lead_meta_table_name();

		foreach ( $tables as $meta_table ) {
			$sql = "
				DELETE FROM $meta_table
				WHERE (
					`meta_key` = 'is_approved'
				);
			";
			$wpdb->query( $sql );
		}

		/**
		 * Notes.
		 */
		$tables = array();

		if ( version_compare( \GravityView_GFFormsModel::get_database_version(), '2.3-dev-1', '>=' ) && method_exists( 'GFFormsModel', 'get_entry_notes_table_name' ) ) {
			$tables[] = \GFFormsModel::get_entry_notes_table_name();
		}

		$tables[] = \GFFormsModel::get_lead_notes_table_name();

		$disapproved = __('Disapproved the Entry for GravityView', 'gravityview');
		$approved = __('Approved the Entry for GravityView', 'gravityview');

		$suppress = $wpdb->suppress_errors();
		foreach ( $tables as $notes_table ) {
			$sql = $wpdb->prepare( "
				DELETE FROM $notes_table
				WHERE (
					`note_type` = 'gravityview' OR
					`value` = %s OR
					`value` = %s
				);
			", $approved, $disapproved );
			$wpdb->query( $sql );
		}

		$wpdb->suppress_errors( $suppress );

		/**
		 * Capabilities.
		 */
		\GravityView_Roles_Capabilities::get_instance()->remove_caps();

		/**
		 * Options.
		 */
		delete_option( 'gravityview_cache_blacklist' );
		delete_option( 'gv_version_upgraded_from' );
		delete_transient( 'gravityview_edd-activate_valid' );
		delete_transient( 'gravityview_edd-deactivate_valid' );
		delete_transient( 'gravityview_dismissed_notices' );
		delete_site_transient( 'gravityview_related_plugins' );
	}

	private function __clone() { }

	private function __wakeup() { }
}
