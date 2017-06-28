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

		/**
		 * Enable this for more aggressive mocking and destruction of the old core.
		 * Do not define, if you're not ready to get your mind blown!
		 */
		define( 'GRAVITYVIEW_FUTURE_CORE_ALPHA_ENABLED', true );

		/** Register the gravityview post type upon WordPress core init. */
		require_once $this->plugin->dir( 'future/includes/class-gv-view.php' );
		add_action( 'init', array( '\GV\View', 'register_post_type' ) );

		/** The Settings. */
		require_once $this->plugin->dir( 'future/includes/class-gv-settings.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-settings-view.php' );

		/** Add rewrite endpoint for single-entry URLs. */
		require_once $this->plugin->dir( 'future/includes/class-gv-entry.php' );
		add_action( 'init', array( '\GV\Entry', 'add_rewrite_endpoint' ) );

		/** Shortcodes */
		require_once $this->plugin->dir( 'future/includes/class-gv-shortcode.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-shortcode-gravityview.php' );
		if ( defined( 'GRAVITYVIEW_FUTURE_CORE_ALPHA_ENABLED' ) ) {
			remove_shortcode( 'gravityview' );
			add_action( 'init', array( '\GV\Shortcodes\gravityview', 'add' ) );
		}

		/** Our Source generic and beloved source and form backend implementations. */
		require_once $this->plugin->dir( 'future/includes/class-gv-source.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-source-internal.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-form.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-form-gravityforms.php' );

		/** Our Entry generic and beloved entry backend implementations. */
		require_once $this->plugin->dir( 'future/includes/class-gv-entry.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-entry-gravityforms.php' );

		/** Our Field generic and implementations. */
		require_once $this->plugin->dir( 'future/includes/class-gv-field.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-field-gravityforms.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-field-internal.php' );

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

		/** The Renderers. */
		require_once $this->plugin->dir( 'future/includes/class-gv-renderer.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-renderer-view.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-renderer-entry.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-renderer-entry-edit.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-renderer-field.php' );

		/** Templating. */
		require_once $this->plugin->dir( 'future/includes/class-gv-template.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-template-view.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-template-entry.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-template-field.php' );

		require_once $this->plugin->dir( 'future/includes/class-gv-request.php' );

		/** The main frontend request. */
		$this->request = new Frontend_Request();
		/** For now it is the only request type we have. */

		define( 'GRAVITYVIEW_FUTURE_CORE_LOADED', true );
	}

	private function __clone() { }

	private function __wakeup() { }

	public function __get( $key ) {
		switch ( $key ) {
			case 'request':
				return new Frontend_Request();
		}
	}

	public function __set( $key, $value ) {
	}
}
