<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) )
	die();

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
	 * @var \GV\Frontend_Request The current request.
	 *
	 * @api
	 * @since future
	 */
	public $request;

	/**
	 * Get the global instance of \GV\Core.
	 *
	 * @return \GV\Core The global instance of GravityView Core.
	 */
	public static function get() {
		if ( ! self::$__instance instanceof self )
			self::$__instance = new self;
		return self::$__instance;
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
		require_once dirname( __FILE__ ) . '/class-gv-plugin.php';
		$this->plugin = Plugin::get();

		/**
		 * Stop all further functionality from loading if the WordPress
		 * plugin is incompatible with the current environment.
		 *
		 * @todo Output incompatibility notices.
		 */
		if ( ! $this->plugin->is_compatible() ) {
			return;
		}

		/** Register the gravityview post type upon WordPress core init. */
		require_once $this->plugin->dir( 'future/includes/class-gv-view.php' );
		add_action( 'init', array( '\GV\View', 'register_post_type' ) );

		/** Add rewrite endpoint for single-entry URLs. */
		require_once $this->plugin->dir( 'future/includes/class-gv-entry.php' );
		add_action( 'init', array( '\GV\Entry', 'add_rewrite_endpoint' ) );

		/** Generics */
		require_once $this->plugin->dir( 'future/includes/class-gv-settings.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-collection.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-shortcode.php' );

		/** Shortcodes */
		require_once $this->plugin->dir( 'future/includes/class-gv-shortcode-gravityview.php' );
		// add_action( 'init', array( '\GV\Shortcodes\gravityview', 'add' ) ); // @todo uncomment when original is stubbed

		/** Get the View_Collection ready. */
		require_once $this->plugin->dir( 'future/includes/class-gv-collection-view.php' );

		/** The View_Settings. */
		require_once $this->plugin->dir( 'future/includes/class-gv-settings-view.php' );

		/** Initialize the current request. For now we assume a default WordPress frontent context. */
		require_once $this->plugin->dir( 'future/includes/class-gv-request.php' );
		$this->request = new Frontend_Request();
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
				throw new \RuntimeException( __CLASS__ . '::$views is an immutable reference to ' . __CLASS__ . '::$request::$views.' );
		}
	}
}
