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
	 * @since 2.0
	 */
	public $plugin;

	/**
	 * @var \GV\Admin_Request|\GV\Frontend_Request|\GV\Request The global request.
	 *
	 * @api
	 * @since 2.0
	 */
	public $request;

	/**
	 * @var \GV\Logger
	 *
	 * @api
	 * @since 2.0
	 */
	public $log;

	/**
	 * Get the global instance of \GV\Core.
	 *
	 * @return \GV\Core The global instance of GravityView Core.
	 */
	public static function get() {
		if ( ! self::$__instance instanceof self ) {
			self::$__instance = new self();
		}
		return self::$__instance;
	}

	/**
	 * Very early initialization.
	 *
	 * Activation handlers, rewrites, post type registration.
	 */
	public static function bootstrap() {
		require_once __DIR__ . '/class-gv-plugin.php';
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
		/**
		 * Filter the logger instance being used for logging.
		 *
		 * @param \GV\Logger $logger The logger instance.
		 */
		$this->log = apply_filters( 'gravityview/logger', new WP_Action_Logger() );

		/**
		 * Utilities.
		 */
		require_once $this->plugin->dir( 'future/includes/class-gv-utils.php' );

		/** The Settings. */
		require_once $this->plugin->dir( 'future/includes/class-gv-settings.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-settings-view.php' );

		/** Request. */
		require_once $this->plugin->dir( 'future/includes/class-gv-request.php' );

		if ( Request::is_admin() ) {
			$this->request = new Admin_Request();
		} else {
			$this->request = new Frontend_Request();
		}

		/** Require critical legacy core files. @todo Deprecate */
		require_once $this->plugin->dir( 'includes/import-functions.php' );
		require_once $this->plugin->dir( 'includes/helper-functions.php' );
		require_once $this->plugin->dir( 'includes/class-common.php' );
		require_once $this->plugin->dir( 'includes/connector-functions.php' );
		require_once $this->plugin->dir( 'includes/class-gravityview-compatibility.php' );
		require_once $this->plugin->dir( 'includes/class-gravityview-roles-capabilities.php' );
		require_once $this->plugin->dir( 'includes/class-gravityview-admin-notices.php' );
		require_once $this->plugin->dir( 'includes/class-admin.php' );
		require_once $this->plugin->dir( 'includes/class-post-types.php' );
		require_once $this->plugin->dir( 'includes/class-cache.php' );

		/**
		 * GravityView extensions and widgets.
		 */
		require_once $this->plugin->dir( 'future/includes/class-gv-extension.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-grid.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-widget.php' );

		/** More legacy core. @todo Deprecate */
		$this->plugin->include_legacy_core();

		/** Register the gravityview post type upon WordPress core init. */
		require_once $this->plugin->dir( 'future/includes/class-gv-view.php' );
		add_action( 'init', array( '\GV\View', 'register_post_type' ) );
		add_action( 'init', array( '\GV\View', 'add_rewrite_endpoint' ) );
		add_filter( 'map_meta_cap', array( '\GV\View', 'restrict' ), 11, 4 );
		add_action( 'template_redirect', array( '\GV\View', 'template_redirect' ) );
		add_action( 'the_content', array( '\GV\View', 'content' ) );
		/**
		 * Stop all further functionality from loading if the WordPress
		 * plugin is incompatible with the current environment.
		 *
		 * Saves some time and memory.
		 */
		if ( ! $this->plugin->is_compatible() ) {
			$this->log->error( 'GravityView 2.0 is not compatible with this environment. Stopped loading.' );

			return;
		}


		/** Add rewrite endpoint for single-entry URLs. */
		require_once $this->plugin->dir( 'future/includes/class-gv-entry.php' );
		add_action( 'init', array( '\GV\Entry', 'add_rewrite_endpoint' ) );

		/** REST API */
		require_once $this->plugin->dir( 'future/includes/rest/class-gv-rest-core.php' );
		add_action( 'rest_api_init', array( '\GV\REST\Core', 'init' ) );

		/** Generate custom slugs on entry save. @todo Deprecate. */
		add_action( 'gform_entry_created', array( '\GravityView_API', 'entry_create_custom_slug' ), 10, 2 );

		/** Shortcodes */
		require_once $this->plugin->dir( 'future/includes/class-gv-shortcode.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-shortcode-gravityview.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-shortcode-gventry.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-shortcode-gvfield.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-shortcode-gvlogic.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-shortcode-gv-entry-link.php' );
		add_action( 'init', array( '\GV\Shortcodes\gravityview', 'add' ) );
		add_action( 'init', array( '\GV\Shortcodes\gventry', 'add' ) );
		add_action( 'init', array( '\GV\Shortcodes\gvfield', 'add' ) );
		add_action( 'init', array( '\GV\Shortcodes\gvlogic', 'add' ) );
		add_action( 'init', array( '\GV\Shortcodes\gv_entry_link', 'add' ) );

		/** oEmbed */
		require_once $this->plugin->dir( 'future/includes/class-gv-oembed.php' );
		add_action( 'init', array( '\GV\oEmbed', 'init' ), 11 );

		/** Our Source generic and beloved source and form backend implementations. */
		require_once $this->plugin->dir( 'future/includes/class-gv-source.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-source-internal.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-form.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-form-gravityforms.php' );

		/** Joins */
		require_once $this->plugin->dir( 'future/includes/class-gv-form-join.php' );

		/** Our Entry generic and beloved entry backend implementations. */
		require_once $this->plugin->dir( 'future/includes/class-gv-entry-gravityforms.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-entry-multi.php' );

		/** Context is everything. */
		require_once $this->plugin->dir( 'future/includes/class-gv-context.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-context-template.php' );

		/** Our Field generic and implementations. */
		require_once $this->plugin->dir( 'future/includes/class-gv-field.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-field-gravityforms.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-field-internal.php' );

		/** Get the collections ready. */
		require_once $this->plugin->dir( 'future/includes/interface-gv-collection-position-aware.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-collection.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-collection-form.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-collection-field.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-collection-entry.php' );
		require_once $this->plugin->dir( 'future/includes/class-gv-collection-widget.php' );
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
		require_once $this->plugin->dir( 'future/includes/class-gv-template-legacy-override.php' );

		/** Magic. */
		require_once $this->plugin->dir( 'future/includes/class-gv-wrappers.php' );

		/** Gutenberg Blocks. */
		require_once $this->plugin->dir( 'future/includes/gutenberg/class-gv-gutenberg-blocks.php' );

		/** Elementor Widget. */
		require_once $this->plugin->dir( 'future/includes/elementor/class-elementor-integration.php' );

		/** Divi Module. */
		require_once $this->plugin->dir( 'future/includes/divi/class-divi-integration.php' );

		require_once $this->plugin->dir( 'includes/class-gravityview-powered-by.php' );

		/** Cache busting. */
		add_action( 'clean_post_cache', '\GV\View::_flush_cache' );

		/**
		 * The core has been loaded.
		 *
		 * Note: this is a very early load hook, not all of WordPress core has been loaded here.
		 *  `init` hasn't been called yet.
		 */
		do_action( 'gravityview/loaded' );
	}

	public function __clone() { }

	public function __wakeup() { }

	/**
	 * Wrapper magic.
	 *
	 * Making developers happy, since 2017.
	 */
	public function __get( $key ) {
		static $views;

		switch ( $key ) {
			case 'views':
				if ( is_null( $views ) ) {
					$views = new \GV\Wrappers\views();
				}
				return $views;
		}
	}

	public function __set( $key, $value ) {
	}
}
