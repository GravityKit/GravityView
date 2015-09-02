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

		// load the WP testing environment
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		// set up Gravity Forms database
		GFForms::setup( true );

		// set up Gravity View
		$this->install();

		// clean up Gravity Forms database when finished
		register_shutdown_function( array( $this, 'shutdown') );
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
	 * Setup all Gravity View's files
	 *
	 * @since 1.9
	 */
	public function install() {
		$GV = GravityView_Plugin::getInstance();
		$GV->frontend_actions();
	}

	/**
	 * Run clean up when PHP finishes executing
	 *
	 * @since 1.9
	 */
	public function shutdown() {
		RGFormsModel::drop_tables();
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

