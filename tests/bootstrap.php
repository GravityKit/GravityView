<?php
/**
 * GravityView Unit Tests Bootstrap
 *
 * @since 1.9
 */
class GV_Unit_Tests_Bootstrap {
	/**
	 * @var \GV_Unit_Tests_Bootstrap $instance
	 */
	protected static $instance = null;

	public $wp_tests_dir;

	public $tests_dir;

	public $plugin_dir;

	public $gf_plugin_dir;

	/**
	 * @var int
	 */
	private $form_id = 0;

	/**
	 * @var array GF Form array
	 */
	private $form = array();

	/**
	 * @var int
	 */
	private $entry_id = 0;

	/**
	 * @var array GF Entry array
	 */
	private $entry = array();

	/**
	 * Setup the unit testing environment
	 *
	 * @since 1.9
	 */
	public function __construct() {
		ini_set( 'display_errors', 'on' );
		error_reporting( E_ALL );

		$this->tests_dir     = dirname( __FILE__ );
		$this->plugin_dir    = dirname( $this->tests_dir );
		$this->wp_tests_dir  = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/tmp/wordpress-tests-lib';
		$this->gf_plugin_dir = getenv( 'GF_PLUGIN_DIR' ) ? getenv( 'GF_PLUGIN_DIR' ) : '/tmp/gravityforms';

		if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
			define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $this->plugin_dir . '/vendor/yoast/phpunit-polyfills' );
		}

		// load test function so tests_add_filter() is available
		require_once $this->wp_tests_dir . '/includes/functions.php';

		// stub remote HTTP calls
		tests_add_filter( 'pre_http_request', array( $this, 'mock_http' ), 10, 3 );

		// mock response for Foundation's product check
		tests_add_filter( 'gravityview/tests/mock_http', function ( $args, $url ) {
			return preg_match( '/edd-api\/products/', $url ) ? '' : $args;
		}, 10, 2 );

		// In WordPress 4.0 this is not being set, so let's just set it to localhost
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		// load GV
		tests_add_filter( 'muplugins_loaded', array( $this, 'load' ) );

		// Log debug if passed to `phpunit` like: `phpunit --debug --verbose`
		if( in_array( '--debug', (array)$_SERVER['argv'], true ) && in_array( '--verbose', (array)$_SERVER['argv'], true ) ) {
			tests_add_filter( 'gravityview_log_error', array( $this, 'test_print_log'), 10, 2 );
			tests_add_filter( 'gravityview_log_debug', array( $this, 'test_print_log_backtrace' ), 10, 2 );
		}

		// load the WP testing environment
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		require_once $this->tests_dir . '/GV_UnitTestCase.php';
		require_once $this->tests_dir . '/GV_RESTUnitTestCase.php';
		require_once $this->tests_dir . '/gravityforms-factory.php';
		require_once $this->tests_dir . '/gravityview-generators.php';
		require_once $this->tests_dir . '/gravityview-factory.php';
		require_once $this->tests_dir . '/factory.php';

		// set up GravityView
		$this->install();
	}

	/**
	 * Alias of test_print_log, with backtrace
	 * @since 1.21.6
	 * @param $message
	 * @param $data
	 */
	public function test_print_log_backtrace( $message, $data = null ) {
		$this->test_print_log( $message, $data, true );
	}

	public function test_print_log(  $message = '', $data = null, $backtrace = false  ) {
		$error = array(
			'message' => $message,
			'data' => $data,
		);

		if( $backtrace ) {
			$error['backtrace'] = function_exists('wp_debug_backtrace_summary') ? wp_debug_backtrace_summary( null, 3 ) : '';
		}

		fwrite( STDERR, print_r( $error, true ) );
		fflush( STDERR );
	}

	/**
	 * Load GravityView
	 *
	 * @since 1.9
	 */
	public function load() {
		require_once $this->gf_plugin_dir . '/gravityforms.php';
		require_once( GFCommon::get_base_path() . '/form_display.php' );
		require_once( GFCommon::get_base_path() . '/tooltips.php' );

		/** Enable the REST API */
		add_action( 'gravityview/settings/defaults', function( $defaults ) {
			$defaults['rest_api'] = 1;
			return $defaults;
		} );

		require_once $this->plugin_dir . '/gravityview.php';

		/* Remove temporary tables which causes problems with GF */
		remove_all_filters( 'query', 10 );

		add_filter( 'gravityview/query/class', 'gravityview_joins_patch_query' );
		function gravityview_joins_patch_query() {
			if ( class_exists( 'GF_Query' ) ) {
				require_once dirname( __FILE__ ) . '/class-gf-query.php';
				return '\GF_Patched_Query';
			}
		}

		// set up Gravity Forms database
		if ( function_exists( 'gf_upgrade' ) ) {
			gf_upgrade()->maybe_upgrade();
		} else {
			@GFForms::setup( true );
		}

		$this->create_stubs();
	}

	/**
	 * @return array
	 */
	public function get_form() {
		return $this->form;
	}

	/**
	 * @return array
	 */
	public function get_entry() {
		return $this->entry;
	}

	/**
	 * @return int
	 */
	public function get_entry_id() {
		return $this->entry_id;
	}

	/**
	 * @return int
	 */
	public function get_form_id() {
		return $this->form_id;
	}

	/**
	 * Generate some placeholder values to test against
	 */
	private function create_stubs() {

		add_role( 'zero', "No Capabilities", array() );

		$this->form_id = GFAPI::add_form( array(
			'title' => 'This is the form title',
			'fields' => array(
				new GF_Field_Text(array(
					'id' => 1,
					'label' => 'Label for field one (text)',
					'choices' => array(),
					'inputs' => '',
				)),
				new GF_Field_Hidden(array(
					'id' => 2,
					'label' => 'Label for field two (hidden)',
					'choices' => array(),
					'inputs' => '',
				)),
				new GF_Field_Number(array(
					'id' => 3,
					'label' => 'Label for field three (number)',
					'choices' => array(),
					'inputs' => '',
				))
			),
		));

		$this->form = GFAPI::get_form( $this->form_id );

		$entry_array = array(
			'form_id' => $this->form_id,
			'1' => 'Value for field one',
			'2' => 'Value for field two',
			'3' => '3.33333',
			'ip' => '127.0.0.1',
			'source_url' => 'http://example.com/wordpress/?gf_page=preview&id=16',
			'source_id' => 16,
			'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.78.2 (KHTML, like Gecko) Version/7.0.6 Safari/537.78.2',
			'payment_status' => 'Processing',
			'payment_date' => '2014-08-29 20:55:06',
			'payment_amount' => '0.01',
			'transaction_id' => 'asdfpaoj442gpoagfadf',
			'created_by' => 1,
			'status' => 'active',
			'date_created' => '2014-08-29 18:25:39',
		);

		$this->entry_id = GFAPI::add_entry( $entry_array );

		$this->entry = GFAPI::get_entry( $this->entry_id );

	}

	/**
	 * Setup all Gravity View's files
	 *
	 * @since 1.9
	 */
	public function install() {
		$GV = \GV\Plugin::get();

		$GV->include_legacy_frontend();
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

	/**
	 * Block all HTTP calls unless specifically mocked.
	 */
	public function mock_http( $override, $args, $url ) {
		if ( $response = apply_filters( 'gravityview/tests/mock_http', $args, $url ) ) {
			return $response;
		}
		return new WP_Error( 'HTTP calls denied in test mode. Use gravityview/tests/mock_http to filter.' );
	}

}

GV_Unit_Tests_Bootstrap::instance();


/* Clean up the GF Database when we're done */
register_shutdown_function( 'gravityview_shutdown' );

/* Shutdown function wasn't working when referenced via array( $this, 'shutdown' ) from the object */
function gravityview_shutdown() {
	RGFormsModel::drop_tables();
}
