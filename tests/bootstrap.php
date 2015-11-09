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

		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : $this->plugin_dir . '/tmp/wordpress-tests-lib';

		// load test function so tests_add_filter() is available
		require_once $this->wp_tests_dir . '/includes/functions.php';

		// load GV
		tests_add_filter( 'muplugins_loaded', array( $this, 'load' ) );

		tests_add_filter( 'gravityview_log_error', array( $this, 'test_print_log'), 10, 3 );

		// Log debug if passed to `phpunit` like: `phpunit --debug --verbose`
		if( in_array( '--debug', (array)$_SERVER['argv'], true ) && in_array( '--verbose', (array)$_SERVER['argv'], true ) ) {
			tests_add_filter( 'gravityview_log_debug', array( $this, 'test_print_log' ), 10, 3 );
		}

		// load the WP testing environment
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		require_once $this->tests_dir . '/GV_UnitTestCase.php';

		require_once $this->tests_dir . '/factory.php';

		// set up GravityView
		$this->install();
	}

	public function test_print_log(  $message = '', $data = null  ) {
		$error = array(
			'message' => $message,
			'data' => $data,
			'backtrace' => function_exists('wp_debug_backtrace_summary') ? wp_debug_backtrace_summary( null, 3 ) : '',
		);
		fwrite(STDERR, print_r( $error, true ) );
	}

	/**
	 * Load GravityView
	 *
	 * @since 1.9
	 */
	public function load() {
		require_once $this->plugin_dir . '/tmp/gravityforms/gravityforms.php';

		$this->load_rest_api();

		require_once $this->plugin_dir . '/gravityview.php';

		/* Remove temporary tables which causes problems with GF */
		remove_all_filters( 'query', 10 );

		// set up Gravity Forms database
		@GFForms::setup( true );

		$this->create_stubs();
	}

	/**
	 * Fetch the REST API files
	 * @since 1.15.1
	 */
	private function load_rest_api() {

		if( ! defined( 'REST_API_VERSION' ) ) {

			define( 'REST_API_VERSION', '2.0' );

			/** Compatibility shims for PHP functions */
			include_once( $this->plugin_dir . '/tmp/api-core/wp-includes/compat.php' );

			/** WP_HTTP_Response class */
			require_once( $this->plugin_dir . '/tmp/api-core/wp-includes/class-wp-http-response.php' );

			/** Main API functions */
			include_once( $this->plugin_dir . '/tmp/api-core/wp-includes/functions.php' );

			/** WP_REST_Server class */
			include_once( $this->plugin_dir . '/tmp/api-core/wp-includes/rest-api/class-wp-rest-server.php' );

			/** WP_HTTP_Response class */
			include_once( $this->plugin_dir . '/tmp/api-core/wp-includes/class-wp-http-response.php' );

			/** WP_REST_Response class */
			include_once( $this->plugin_dir . '/tmp/api-core/wp-includes/rest-api/class-wp-rest-response.php' );

			/** WP_REST_Request class */
			require_once( $this->plugin_dir . '/tmp/api-core/wp-includes/rest-api/class-wp-rest-request.php' );

			/** REST functions */
			include_once( $this->plugin_dir . '/tmp/api-core/wp-includes/rest-api/rest-functions.php' );

			/** REST filters */
			include_once( $this->plugin_dir . '/tmp/api-core/wp-includes/filters.php' );

			/**
			 * Determines if the rewrite rules should be flushed.
			 *
			 * @since 4.4.0
			 */
			function rest_api_maybe_flush_rewrites() {
				$version = get_option( 'rest_api_plugin_version', null );

				if ( empty( $version ) || REST_API_VERSION !== $version ) {
					flush_rewrite_rules();
					update_option( 'rest_api_plugin_version', REST_API_VERSION );
				}
			}

			add_action( 'init', 'rest_api_maybe_flush_rewrites', 999 );

			/**
			 * Registers routes and flush the rewrite rules on activation.
			 *
			 * @since 4.4.0
			 *
			 * @param bool $network_wide ?
			 */
			function rest_api_activation( $network_wide ) {
				if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
					$mu_blogs = wp_get_sites();

					foreach ( $mu_blogs as $mu_blog ) {
						switch_to_blog( $mu_blog['blog_id'] );

						rest_api_register_rewrites();
						update_option( 'rest_api_plugin_version', null );
					}

					restore_current_blog();
				} else {
					rest_api_register_rewrites();
					update_option( 'rest_api_plugin_version', null );
				}
			}

			register_activation_hook( __FILE__, 'rest_api_activation' );

			/**
			 * Flushes the rewrite rules on deactivation.
			 *
			 * @since 4.4.0
			 *
			 * @param bool $network_wide ?
			 */
			function rest_api_deactivation( $network_wide ) {
				if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {

					$mu_blogs = wp_get_sites();

					foreach ( $mu_blogs as $mu_blog ) {
						switch_to_blog( $mu_blog['blog_id'] );
						delete_option( 'rest_api_plugin_version' );
					}

					restore_current_blog();
				} else {
					delete_option( 'rest_api_plugin_version' );
				}
			}

			register_deactivation_hook( __FILE__, 'rest_api_deactivation' );
		}
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
		$GV = GravityView_Plugin::getInstance();
		$GV->frontend_actions();
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


/* Clean up the GF Database when we're done */
register_shutdown_function( 'gravityview_shutdown' );

/* Shutdown function wasn't working when referenced via array( $this, 'shutdown' ) from the object */
function gravityview_shutdown() {
	RGFormsModel::drop_tables();
}
