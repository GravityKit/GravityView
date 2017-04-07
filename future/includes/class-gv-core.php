<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The core GravityView API.
 *
 * Returned by the wrapper gravityview() global function, exposes
 * all the required public functionality and classes, sets up global
 * state depending on current request context, etc.
 */
final class Core {
	/**
	 * @var \GV\Core The \GV\Core static instance.
	 */
	private static $__instance = null;

	/**
	 * @var \GV\Plugin The WordPress plugin context.
	 *
	 * @api
	 * @since future
	 */
	public $plugin;

	/**
	 * @var \GV\Request The global request.
	 *
	 * @api
	 * @since future
	 */
	public $request;

	/**
	 * @var \GV\Logger;
	 *
	 * @api
	 * @since future
	 */
	public $log;

	/**
	 * Get the global instance of \GV\Core.
	 *
	 * @return \GV\Core The global instance of GravityView Core.
	 */
	public static function get() {
		if ( ! self::$__instance instanceof self ) {
			self::$__instance = new self;
		}
		return self::$__instance;
	}

	/**
	 * Very early initialization.
	 *
	 * Activation handlers, rewrites, post type registration.
	 */
	public static function bootstrap() {
		require_once dirname( __FILE__ ) . '/class-gv-plugin.php';
		Plugin::get()->register_activation_hooks();
	}

	/**
	 * Bootstrap.
	 *
	 * @return void
	 */
	private function __construct() {
		self::$__instance = $this;
		$this->init();
	}

	/**
	 * Early initialization.
	 *
	 * Loads dependencies, sets up the object, adds hooks, etc.
	 *
	 * @return void
	 */
	private function init() {
		$this->plugin = Plugin::get();

		/** Enable logging. */
		require_once $this->plugin->dir( 'future/includes/class-gv-logger.php' );
		$this->log = new WP_Action_Logger();

		/**
		 * Stop all further functionality from loading if the WordPress
		 * plugin is incompatible with the current environment.
		 */
		if ( ! $this->plugin->is_compatible() ) {
			return;
		}

		/** Templating. */
		require_once $this->plugin->dir( 'future/includes/class-gv-template.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-template-view.php' );

		/** Register the gravityview post type upon WordPress core init. */
		require_once $this->plugin->dir( 'future/includes/class-gv-view.php' );
		add_action( 'init', array( '\GV\View', 'register_post_type' ) );

		/** The Contexts. */
		require_once $this->plugin->dir( 'future/includes/class-gv-context.php' );

		/** The Settings. */
		require_once $this->plugin->dir( 'future/includes/class-gv-settings.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-settings-view.php' );

		/** Add rewrite endpoint for single-entry URLs. */
		require_once $this->plugin->dir( 'future/includes/class-gv-entry.php' );
		add_action( 'init', array( '\GV\Entry', 'add_rewrite_endpoint' ) );

		/** Shortcodes */
		require_once $this->plugin->dir( 'future/includes/class-gv-shortcode.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-shortcode-gravityview.php' );
		// add_action( 'init', array( '\GV\Shortcodes\gravityview', 'add' ) ); // @todo uncomment when original is stubbed

		/** Our Source generic and beloved source and form backend implementations. */
		require_once $this->plugin->dir( 'future/includes/class-gv-source.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-source-internal.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-form.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-form-gravityforms.php' );

		/** Our Entry generic and beloved entry backend implementations. */
		require_once $this->plugin->dir( 'future/includes/class-gv-entry.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-entry-gravityforms.php' );

		/** Our Field generic. */
		require_once $this->plugin->dir( 'future/includes/class-gv-field.php' );

		/** Get the collections ready. */
		require_once $this->plugin->dir( 'future/includes/class-gv-collection.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-collection-form.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-collection-field.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-collection-entry.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-collection-view.php' );

		/** The sorting, filtering and paging classes. */
		require_once $this->plugin->dir( 'future/includes/class-gv-collection-entry-filter.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-collection-entry-sort.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-collection-entry-offset.php' );

		/** Initialize the current request. For now we assume a default WordPress frontent context. */
		require_once $this->plugin->dir( 'future/includes/class-gv-request.php' );

		/**
		 * Use this for global state tracking in the old code.
		 *
		 * We're in a tricky situation now, where we're putting our
		 *  Frontend_Request to work. But the old code is relying on
		 *  it to keep track of views state and whatnot. Ugh.
		 *
		 * More importantly GravityView_View_Data is resetting it every
		 *  time the class instantiates! This conflicts with adding filters,
		 *  actions, and other global initialization for the real request.
		 *
		 * Let's give them a Dummy_Request to work with. They're using it
		 *  as a container for views either way. And for the is_admin()
		 *  function, which will be available once GravityView_View_Data
		 *  is removed.
		 */
		$this->request = new Dummy_Request();

		if ( ! $this->request->is_admin() ) {
			/** The main frontend request. */
			new Frontend_Request();
		}

		define( 'GRAVITYVIEW_FUTURE_CORE_LOADED', true );
	}

	private function __clone() { }

	private function __wakeup() { }

	public function __get( $key ) {
		switch ( $key ) {
			case 'views':
				return $this->request->views;
		}
	}

	public function __set( $key, $value ) {
		switch ( $key ) {
			case 'views':
				gravityview()->log->error( __CLASS__ . '::$views is an immutable reference to ' . __CLASS__ . '::$request::$views.' );
				return;
		}
	}
}
