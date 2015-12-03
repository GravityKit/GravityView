<?php
/**
 * @package GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      https://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 */

/**
 * Extend this class to create a GravityView extension that gets updates from GravityView.co
 *
 * @since 1.1
 *
 * @version 1.1.2 Fixed `/lib/` include path for EDDSL
 */
abstract class GravityView_Extension {

	/**
	 * @var string Name of the plugin in gravityview.co
	 */
	protected $_title = NULL;

	/**
	 * @var string Version number of the plugin
	 */
	protected $_version = NULL;

	/**
	 * @var int The ID of the download on gravityview.co
	 * @since 1.1
	 */
	protected $_item_id = NULL;

	/**
	 * @var string Translation textdomain
	 */
	protected $_text_domain = 'gravityview';

	/**
	 * @var string Minimum version of GravityView the Extension requires
	 */
	protected $_min_gravityview_version = '1.1.5';

	/**
	 * @var string Minimum version of GravityView the Extension requires
	 */
	protected $_min_php_version = '5.2.4';

	/**
	 * @var string The URL to fetch license info from. Do not change unless you know what you're doing.
	 */
	protected $_remote_update_url = 'https://gravityview.co';

	/**
	 * @var string Author of plugin, sent when fetching license info.
	 */
	protected $_author = 'Katz Web Services, Inc.';

	/**
	 * @var array Admin notices to display
	 */
	static private $admin_notices = array();

	/**
	 * @var bool Is the extension able to be run based on GV version and whether GV is activated
	 */
	static $is_compatible = true;

	function __construct() {

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'admin_init', array( $this, 'settings') );

		add_action( 'admin_notices', array( $this, 'admin_notice' ), 100 );

		add_action( 'gravityview/metaboxes/before_render', array( $this, 'add_metabox_tab' ) );

		if( false === $this->is_extension_supported() ) {
			return;
		}

		add_filter( 'gravityview_tooltips', array( $this, 'tooltips' ) );

		// Save the form configuration. Run at 14 so that View metadata is already saved (at 10)
		add_action( 'save_post', array( $this, 'save_post' ), 14 );

		$this->add_hooks();

	}

	/**
	 * Add a tab to GravityView Edit View tabbed metabox. By overriding this method, you will add a tab to View settings
	 *
	 * @since 1.8 (Extension version 1.0.7)
	 *
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
	function add_metabox_tab() {

		$tab_settings = $this->tab_settings();

		// Don't add a tab if it's empty.
		if( empty( $tab_settings ) ) {
			return;
		}

		$tab_defaults = array(
			'id' => '',
			'title' => '',
			'callback' => '',
			'icon-class' => '',
			'file' => '',
			'callback_args' => '',
			'context' => 'side',
			'priority' => 'default',
		);

		$tab = wp_parse_args( $tab_settings, $tab_defaults );

		// Force the screen to be GravityView
		$tab['screen'] = 'gravityview';

		if( class_exists('GravityView_Metabox_Tab') ) {

			$metabox = new GravityView_Metabox_Tab( $tab['id'], $tab['title'], $tab['file'], $tab['icon-class'], $tab['callback'], $tab['callback_args'] );

			GravityView_Metabox_Tabs::add( $metabox );

		} else {

			add_meta_box( 'gravityview_'.$tab['id'], $tab['title'], $tab['callback'], $tab['screen'], $tab['context'], $tab['priority'] );

		}
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

		if( empty( $this->_text_domain ) ) {
			do_action( 'gravityview_log_debug', __METHOD__ . ': Extension translation cannot be loaded; the `_text_domain` variable is not defined', $this );
			return;
		}

		// Backward compat for Ratings & Reviews / Maps
		$path = isset( $this->_path ) ? $this->_path : ( isset( $this->plugin_file ) ? $this->plugin_file : '' );

		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( $path ) ) . '/languages/';

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale',  get_locale(), $this->_text_domain );

		$mofile = sprintf( '%1$s-%2$s.mo', $this->_text_domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->_text_domain . '/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/[plugin-dir]/ folder
			load_textdomain( $this->_text_domain, $mofile_global );
		}
		elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/[plugin-dir]/languages/ folder
			load_textdomain( $this->_text_domain, $mofile_local );
		}
		else {
			// Load the default language files
			load_plugin_textdomain( $this->_text_domain, false, $lang_dir );
		}
	}

	/**
	 * Get license information from GravityView
	 *
	 * @since 1.8 (Extension version 1.0.7)
	 * @return bool|array False: GravityView_Settings class does not exist. Array: array of GV license data.
	 */
	protected function get_license() {

		if( !class_exists( 'GravityView_Settings' ) ) {
			return false;
		}

		$license = GravityView_Settings::getSetting('license');

		return $license;
	}

	/**
	 * Register the updater for the Extension using GravityView license information
	 *
	 * @return void
	 */
	public function settings() {

		// If doing ajax, get outta here
		if( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )  {
			return;
		}

		if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {

			$file_path = plugin_dir_path( __FILE__ ) . 'lib/EDD_SL_Plugin_Updater.php';

			// This file may be in the lib/ directory already
			if( ! file_exists( $file_path ) ) {
				$file_path = plugin_dir_path( __FILE__ ) . '/EDD_SL_Plugin_Updater.php';
			}

			include_once $file_path;
		}

		$license = $this->get_license();

		// Don't update if invalid license.
		if( false === $license || empty( $license['status'] ) || strtolower( $license['status'] ) !== 'valid' ) { return; }

		new EDD_SL_Plugin_Updater(
			$this->_remote_update_url,
			$this->_path,
			array(
            	'version'	=> $this->_version, // current version number
            	'license'	=> $license['license'],
	            'item_id'   => $this->_item_id, // The ID of the download on _remote_update_url
            	'item_name' => $this->_title,  // name of this plugin
            	'author' 	=> strip_tags( $this->_author )  // author of this plugin
          	)
        );
	}

	/**
	 * Outputs the admin notices generated by the plugin
	 *
	 * @return void
	 */
	public function admin_notice() {

		if( empty( self::$admin_notices ) ) {
			return;
		}

		foreach( self::$admin_notices as $key => $notice ) {

			echo '<div id="message" class="'. esc_attr( $notice['class'] ).'">';
			echo wpautop( $notice['message'] );
			echo '<div class="clear"></div>';
			echo '</div>';
		}

		//reset the notices handler
		self::$admin_notices = array();
	}

	/**
	 * Add a notice to be displayed in the admin.
	 * @param array $notice Array with `class` and `message` keys. The message is not escaped.
	 */
	public static function add_notice( $notice = array() ) {

		if( is_array( $notice ) && !isset( $notice['message'] ) ) {
			do_action( 'gravityview_log_error', __CLASS__.'[add_notice] Notice not set', $notice );
			return;
		} else if( is_string( $notice ) ) {
			$notice = array( 'message' => $notice );
		}

		$notice['class'] = empty( $notice['class'] ) ? 'error' : $notice['class'];

		self::$admin_notices[] = $notice;
	}

	/**
	 * Extensions should override this hook to add their hooks instead of
	 */
	public function add_hooks() { }

	/**
	 * Store the filter settings in the `_gravityview_filters` post meta
	 * @param  int $post_id Post ID
	 * @return void
	 */
	public function save_post( $post_id ) {}

	/**
	 * Add tooltips for the extension.
	 *
	 * Add a tooltip with an array using the `title` and `value` keys. The `title` key is the H6 tag value of the tooltip; it's the headline. The `value` is the tooltip content, and can contain any HTML.
	 *
	 * The tooltip key must be `gv_{name_of_setting}`. If the name of the setting is "example_extension_setting", the code would be:
	 *
	 * <code>
	 * $tooltips['gv_example_extension_setting'] = array(
	 * 	'title'	=> 'About Example Extension Setting',
	 *  'value'	=> 'When you do [x] with [y], [z] happens.'
	 * );
	 * </code>
	 *
	 * @param  array  $tooltips Existing GV tooltips, with `title` and `value` keys
	 * @return array           Modified tooltips
	 */
	public function tooltips( $tooltips = array() ) {

		return $tooltips;

	}

	/**
	 * Check whether the extension is supported:
	 *
	 * - Checks if GravityView and Gravity Forms exist
	 * - Checks GravityView and Gravity Forms version numbers
	 * - Checks PHP version numbers
	 * - Sets self::$is_compatible to boolean value
	 *
	 * @uses GravityView_Admin::check_gravityforms()
	 * @return boolean Is the extension supported?
	 */
	protected function is_extension_supported() {

		self::$is_compatible = true;

		$message = '';

		if( !class_exists( 'GravityView_Plugin' ) ) {

			$message = sprintf( __('Could not activate the %s Extension; GravityView is not active.', 'gravityview'), $this->_title );

		} else if( false === version_compare(GravityView_Plugin::version, $this->_min_gravityview_version , ">=") ) {

			$message = sprintf( __('The %s Extension requires GravityView Version %s or newer.', 'gravityview' ), $this->_title, '<tt>'.$this->_min_gravityview_version.'</tt>' );

		} else if( isset( $this->_min_php_version ) && false === version_compare( phpversion(), $this->_min_php_version , ">=") ) {

			$message = sprintf( __('The %s Extension requires PHP Version %s or newer. Please ask your host to upgrade your server\'s PHP.', 'gravityview' ), $this->_title, '<tt>'.$this->_min_php_version.'</tt>' );

		} else {

			self::$is_compatible = GravityView_Compatibility::is_valid();

		}

		if ( ! empty( $message ) ) {

			self::add_notice( $message );

			do_action( 'gravityview_log_error', __METHOD__. ' ' . $message );

			self::$is_compatible = false;
		}

		return self::$is_compatible;
	}

}
