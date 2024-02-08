<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\Extension class.
 *
 * An interface that most extensions would want to adhere to and inherit from.
 *
 * @deprecated 2.16.1
 *
 * @TODO Remove once all extensions have been updated to use Foundation.
 */
abstract class Extension {
	/**
	 * @var string Name of the plugin in gravitykit.com
	 */
	protected $_title = null;

	/**
	 * @var string Version number of the plugin
	 */
	protected $_version = null;

	/**
	 * @var int The ID of the download on gravitykit.com
	 * @since 1.1
	 */
	protected $_item_id = null;

	/**
	 * @var string Translation textdomain
	 */
	protected $_text_domain = 'gravityview';

	/**
	 * @var string Minimum version of GravityView the Extension requires
	 */
	protected $_min_gravityview_version = '2.0-dev';

	/**
	 * @var string Maximum version of GravityView the Extension requires, if any
	 */
	protected $_max_gravityview_version = null;

	/**
	 * @var string Minimum version of GravityView the Extension requires
	 */
	protected $_min_php_version = '5.6.4';

	/**
	 * @var string Author of plugin, sent when fetching license info.
	 */
	protected $_author = 'Katz Web Services, Inc.';

	/**
	 * @var string The path to the extension.
	 */
	protected $_path = '';

	/**
	 * @var array Admin notices to display
	 */
	protected static $admin_notices = array();

	/**
	 * @var boolean[] An array of extension compatibility.
	 * @since 2.0 This is an array of classes instead.
	 */
	public static $is_compatible = array();

	/**
	 * Generic initialization.
	 */
	public function __construct() {
		if ( false === $this->is_extension_supported() ) {
			return;
		}

		if ( ! $this->_path ) {
			$this->_path = __FILE__;
		}

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'admin_notices', array( $this, 'admin_notice' ), 100 );

		// Save the view configuration. Run at 14 so that View metadata is already saved (at 10)
		add_action( 'save_post', array( $this, 'save_post' ), 14 );

		add_action( 'gravityview/metaboxes/before_render', array( $this, 'add_metabox_tab' ) );

		add_filter( 'gravityview/metaboxes/tooltips', array( $this, 'tooltips' ) );

		$this->add_hooks();
	}

	/**
	 * Load translations for the extension
	 *
	 * 1. Check  `wp-content/languages/gravityview/` folder and load using `load_textdomain()`
	 * 2. Check  `wp-content/plugins/gravityview/languages/` folder for `gravityview-[locale].mo` file and load using `load_textdomain()`
	 * 3. Load default file using `load_plugin_textdomain()` from `wp-content/plugins/gravityview/languages/`
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		if ( empty( $this->_text_domain ) ) {
			gravityview()->log->debug( 'Extension translation cannot be loaded; the `_text_domain` variable is not defined', array( 'data' => $this ) );
			return;
		}

		// Backward compat for Ratings & Reviews / Maps
		$path = isset( $this->_path ) ? $this->_path : ( isset( $this->plugin_file ) ? $this->plugin_file : '' );

		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( $path ) ) . '/languages/';

		$locale = get_locale();

		if ( function_exists( 'get_user_locale' ) && is_admin() ) {
			$locale = get_user_locale();
		}

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', $locale, $this->_text_domain );

		$mofile = sprintf( '%1$s-%2$s.mo', $this->_text_domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->_text_domain . '/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/[plugin-dir]/ folder
			load_textdomain( $this->_text_domain, $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/[plugin-dir]/languages/ folder
			load_textdomain( $this->_text_domain, $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( $this->_text_domain, false, $lang_dir );
		}
	}

	/**
	 * Extensions should override this hook to add their hooks.
	 *
	 * @return void
	 */
	public function add_hooks() { }

	/**
	 * Save extra view configuration.
	 *
	 * @param  int $post_id Post ID
	 * @return void
	 */
	public function save_post( $post_id ) { }

	/**
	 * Add tooltips for the extension.
	 *
	 * Add a tooltip with an array using the `title` and `value` keys. The `title` key is the H6 tag value of the tooltip; it's the headline. The `value` is the tooltip content, and can contain any HTML.
	 *
	 * The tooltip key must be `gv_{name_of_setting}`. If the name of the setting is "example_extension_setting", the code would be:
	 *
	 * <code>
	 * $tooltips['gv_example_extension_setting'] = array(
	 *  'title' => 'About Example Extension Setting',
	 *  'value' => 'When you do [x] with [y], [z] happens.'
	 * );
	 * </code>
	 *
	 * @param  array $tooltips Existing GV tooltips, with `title` and `value` keys
	 * @return array Modified tooltips
	 */
	public function tooltips( $tooltips = array() ) {
		return $tooltips;
	}

	/**
	 * Add a tab to GravityView Edit View tabbed metabox. By overriding this method, you will add a tab to View settings
	 *
	 * @since 1.8 (Extension version 1.0.7)
	 * @see https://gist.github.com/zackkatz/6cc381bcf54849f2ed41 For example of adding a metabox
	 *
	 * @return array Array of metabox
	 */
	protected function tab_settings() {
		// When overriding, return array with expected keys
		return array();
	}

	/**
	 * If Extension overrides tab_settings() and passes its own tab, add it to the tabbed settings metabox
	 *
	 * @since 1.8 (Extension version 1.0.7)
	 *
	 * @return void
	 */
	public function add_metabox_tab() {
		$tab_settings = $this->tab_settings();

		// Don't add a tab if it's empty.
		if ( empty( $tab_settings ) ) {
			return;
		}

		$tab_defaults = array(
			'id'            => '',
			'title'         => '',
			'callback'      => '',
			'icon-class'    => '',
			'file'          => '',
			'callback_args' => '',
			'context'       => 'side',
			'priority'      => 'default',
		);

		$tab = wp_parse_args( $tab_settings, $tab_defaults );

		// Force the screen to be GravityView
		$tab['screen'] = 'gravityview';

		if ( class_exists( 'GravityView_Metabox_Tab' ) ) {
			$metabox = new \GravityView_Metabox_Tab( $tab['id'], $tab['title'], $tab['file'], $tab['icon-class'], $tab['callback'], $tab['callback_args'] );
			\GravityView_Metabox_Tabs::add( $metabox );
		} else {
			add_meta_box( 'gravityview_' . $tab['id'], $tab['title'], $tab['callback'], $tab['screen'], $tab['context'], $tab['priority'] );
		}
	}

	/**
	 * Is this extension even compatible?
	 *
	 * @return boolean|null Is or is not. Null if unknown yet.
	 */
	public static function is_compatible() {
		return Utils::get( self::$is_compatible, get_called_class(), null );
	}

	/**
	 * Check whether the extension is supported:
	 *
	 * - Checks if GravityView and Gravity Forms exist
	 * - Checks GravityView and Gravity Forms version numbers
	 * - Checks PHP version numbers
	 * - Sets self::$is_compatible[__CLASS__] to boolean value
	 *
	 * @return boolean Is the extension supported?
	 */
	protected function is_extension_supported() {

		self::$is_compatible = is_array( self::$is_compatible ) ? self::$is_compatible : array( get_called_class() => (bool) self::$is_compatible );

		if ( ! function_exists( 'gravityview' ) ) {
			$message = sprintf( __( 'Could not activate the %s Extension; GravityView is not active.', 'gk-gravityview' ), esc_html( $this->_title ) );
		} elseif ( false === version_compare( Plugin::$version, $this->_min_gravityview_version, '>=' ) ) {
			$message = sprintf( __( 'The %1$s Extension requires GravityView Version %2$s or newer.', 'gk-gravityview' ), esc_html( $this->_title ), '<tt>' . $this->_min_gravityview_version . '</tt>' );
		} elseif ( isset( $this->_min_php_version ) && false === version_compare( phpversion(), $this->_min_php_version, '>=' ) ) {
			$message = sprintf( __( 'The %1$s Extension requires PHP Version %2$s or newer. Please ask your host to upgrade your server\'s PHP.', 'gk-gravityview' ), esc_html( $this->_title ), '<tt>' . $this->_min_php_version . '</tt>' );
		} elseif ( ! empty( $this->_max_gravityview_version ) && false === version_compare( $this->_max_gravityview_version, Plugin::$version, '>' ) ) {
			$message = sprintf( __( 'The %s Extension is not compatible with this version of GravityView. Please update the Extension to the latest version.', 'gk-gravityview' ), esc_html( $this->_title ) );
		} else {
			$message                                   = '';
			self::$is_compatible[ get_called_class() ] = gravityview()->plugin->is_compatible();
		}

		if ( ! empty( $message ) ) {
			self::add_notice( $message );
			self::$is_compatible[ get_called_class() ] = false;
			gravityview()->log->error( '{message}', array( 'message' => $message ) );
		}

		return self::is_compatible();
	}

	/**
	 * Add a notice to be displayed in the admin.
	 *
	 * @param array $notice Array with `class` and `message` keys. The message is not escaped.
	 *
	 * @return void
	 */
	public static function add_notice( $notice = array() ) {

		if ( is_array( $notice ) && empty( $notice['message'] ) ) {
			gravityview()->log->error( 'Notice not set', array( 'data' => $notice ) );
			return;
		} elseif ( is_string( $notice ) ) {
			$notice = array( 'message' => $notice );
		}

		$notice['class'] = empty( $notice['class'] ) ? 'error' : $notice['class'];

		self::$admin_notices [] = $notice;
	}

	/**
	 * Outputs the admin notices generated by the all plugins
	 *
	 * @return void
	 */
	public function admin_notice() {
		if ( empty( self::$admin_notices ) ) {
			return;
		}

		foreach ( self::$admin_notices as $key => $notice ) {
			echo '<div id="message" class="' . esc_attr( $notice['class'] ) . '">';
			echo wpautop( $notice['message'] );
			echo '<div class="clear"></div>';
			echo '</div>';
		}

		// reset the notices handler
		self::$admin_notices = array();
	}
}
