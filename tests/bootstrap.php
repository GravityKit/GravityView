<?php
/**
 * GravityView Unit Tests Bootstrap
 *
 * @since 1.9
 */
class GV_Unit_Tests_Bootstrap {

	/** @var \GV_Unit_Tests_Bootstrap instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	/**
	 * Setup the unit testing environment
	 *
	 * @since 1.9
	 */
	public function __construct() {
		ini_set( 'display_errors', 'on' );
		error_reporting( E_ALL );

		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : $this->plugin_dir . '/tmp/wordpress-tests-lib';

		// load test function so tests_add_filter() is available
		require_once $this->wp_tests_dir . '/includes/functions.php';

		// load GV
		tests_add_filter( 'muplugins_loaded', array( $this, 'load' ) );

		// install GV
		tests_add_filter( 'setup_theme', array( $this, 'install' ) );

		// load the WP testing environment
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		// load GV testing framework
		$this->includes();
	}

	/**
	 * Load GravityView
	 *
	 * @since 1.9
	 */
	public function load() {
		require_once $this->plugin_dir . '/tmp/gravityforms/gravityforms.php';
		require_once $this->plugin_dir . '/gravityview.php';
	}

	/**
	 * Install WooCommerce after the test environment and WC have been loaded
	 *
	 * @since 1.9
	 */
	public function install() {

		// clean existing install first
		define( 'WP_UNINSTALL_PLUGIN', true );
		include $this->plugin_dir . '/uninstall.php';

		// @todo Install the plugin

		// reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374
		$GLOBALS['wp_roles']->reinit();

		echo esc_html( 'Installing GravityView...' . PHP_EOL );
	}

	/**
	 * Load GravityView specific test cases
	 *
	 * @since 1.9
	 */
	public function includes() {

	}

	/**
	 * Get the single class instance
	 *
	 * @since 1.9
	 * @return GV_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

GV_Unit_Tests_Bootstrap::instance();
