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
	 * @var \GV\Request The current request.
	 *
	 * @api
	 * @since future
	 */
	public $request;

	/**
	 * @var \GV\CoreSettings core GravityView settings
	 */
	public $settings;
	
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
		$this->plugin = \GV\Plugin::get();
	}

	private function __clone() { }

	private function __wakeup() { }
}
