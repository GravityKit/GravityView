<?php
/**
 * Abstract class that makes it easy for plugins and themes to register no-conflict scripts and styles, as well as
 * add post meta keys for GravityView to parse when checking for the existence of shortcodes in content.
 *
 * @file      abstract-gravityview-plugin-and-theme-hooks.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15.2
 */

// Make sure the permalink override trait is loaded.
require_once plugin_dir_path( __FILE__ ) . 'trait-gravityview-permalink-override.php';

/**
 * Abstract class that makes it easy for plugins and themes to register no-conflict scripts and styles, as well as
 * add post meta keys for GravityView to parse when checking for the existence of shortcodes in content.
 *
 * @since 1.15.2
 */
abstract class GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @type string Optional. Class that should be exist in a plugin or theme. Used to check whether plugin is active.
	 * @since 1.15.2
	 */
	protected $class_name = false;

	/**
	 * @type string Optional. Function that should be exist in a plugin or theme. Used to check whether plugin is active.
	 * @since 1.15.2
	 */
	protected $function_name = false;

	/**
	 * @type string Optional. Constant that should be defined by plugin or theme. Used to check whether plugin is active.
	 * @since 1.15.2
	 */
	protected $constant_name = false;

	/**
	 * Define the keys to be parsed by the `gravityview/view_collection/from_post/meta_keys` hook
	 *
	 * @see View_Collection::from_post
	 * @since 2.0
	 * @type array
	 */
	protected $content_meta_keys = array();

	/**
	 * Define the keys to be parsed by the `gravityview/data/parse/meta_keys` hook
	 *
	 * @see GravityView_View_Data::parse_post_meta
	 * @deprecated 2.0
	 * @since 1.15.2
	 * @type array
	 */
	protected $meta_keys = array();

	/**
	 * Define script handles used by the theme or plugin to be added to allowed no-conflict scripts
	 *
	 * @see GravityView_Admin::remove_conflicts
	 * @since 1.15.2
	 * @type array
	 */
	protected $script_handles = array();

	/**
	 * Define style handles used by the theme or plugin to be added to allowed no-conflict styles
	 *
	 * @see GravityView_Admin::remove_conflicts
	 * @since 1.15.2
	 * @type array
	 */
	protected $style_handles = array();

	/**
	 * Define features in the admin editor used by the theme or plugin to be used when registering the GravityView post type
	 *
	 * @see \GV\Entry::get_endpoint_name
	 * @since 1.15.2
	 * @type array
	 */
	protected $post_type_support = array();

	/**
	 * GravityView_Theme_Support constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( $this, '_wp_loaded' ) );
	}

	/**
	 * Fired when all themes and plugins have been loaded.
	 * This makes sure we can reliably detect functions and classes.
	 *
	 * @internal
	 * @return void
	 */
	public function _wp_loaded() {
		$this->maybe_add_hooks();
	}

	/**
	 * Returns whether the plugin/theme exists based on the class, function, or constant name.
	 *
	 * @since 2.18.7
	 *
	 * @return bool Whether the plugin or theme is active.
	 */
	protected function is_active() {
		$class_exists     = $this->class_name && class_exists( $this->class_name );
		$function_exists  = $this->function_name && function_exists( $this->function_name );
		$constant_defined = $this->constant_name && defined( "{$this->constant_name}" );

		return ( $class_exists || $function_exists || $constant_defined );
	}

	/**
	 * Check whether plugin or theme exists. If so, add hooks.
	 * This is to reduce load time, since `apply_filters()` isn't free.
	 * If the class name or function name or constant exists for a plugin or theme, add hooks
	 * If the class/function/definition aren't specified, return.
	 *
	 * @since 1.15.2
	 * @return void
	 */
	private function maybe_add_hooks() {

		if ( ! $this->is_active() ) {

			$this->add_inactive_hooks();

			return;
		}

		$this->add_hooks();
	}

	/**
	 * Add hooks when the plugin / theme is active.
	 *
	 * @since 2.26
	 */
	protected function add_inactive_hooks(): void {
	}

	/**
	 * Add filters for meta key and script/style handles, if defined.
	 *
	 * @since 1.15.2
	 * @return void
	 */
	protected function add_hooks() {

		if ( $this->meta_keys ) {
			add_filter( 'gravityview/data/parse/meta_keys', array( $this, 'merge_meta_keys' ), 10, 2 );
		}

		if ( $this->content_meta_keys ) {
			add_filter( 'gravityview/view_collection/from_post/meta_keys', array( $this, 'merge_content_meta_keys' ), 10, 3 );
		}

		if ( $this->script_handles ) {
			add_filter( 'gravityview_noconflict_scripts', array( $this, 'merge_noconflict_scripts' ) );
		}

		if ( $this->style_handles ) {
			add_filter( 'gravityview_noconflict_styles', array( $this, 'merge_noconflict_styles' ) );
		}

		if ( $this->post_type_support ) {
			add_filter( 'gravityview_post_type_support', array( $this, 'merge_post_type_support' ), 10, 2 );
		}

		// Automatically set up permalink overrides if the class uses the trait
		if ( $this->uses_permalink_override_trait() ) {
			add_action( 'template_redirect', array( $this, 'on_template_redirect' ) );
		}
	}

	/**
	 * Check if the current class uses the permalink override trait.
	 *
	 * @since TODO
	 *
	 * @return bool Whether the class uses the GravityView_Permalink_Override_Trait.
	 */
	private function uses_permalink_override_trait() {
		return in_array( GravityView_Permalink_Override_Trait::class, class_uses( $this ), true );
	}

	/**
	 * Merge plugin or theme post type support definitions with existing support values
	 *
	 * @since 1.15.2
	 *
	 * @param array   $supports Array of features associated with a functional area of the edit screen.
	 * @param boolean $is_hierarchical Do Views support parent/child relationships? See `gravityview_is_hierarchical` filter.
	 *
	 * @return array Array of features associated with a functional area of the edit screen, merged with existing values
	 */
	public function merge_post_type_support( $supports = array(), $is_hierarchical = false ) {
		return array_merge( $this->post_type_support, $supports );
	}

	/**
	 * Merge plugin or theme styles with existing no-conflict styles
	 *
	 * @since 1.15.2
	 *
	 * @param array $handles Array of style handles, as registered with WordPress
	 *
	 * @return array Handles, merged with existing styles
	 */
	public function merge_noconflict_styles( $handles ) {
		$handles = array_merge( $this->style_handles, $handles );
		return $handles;
	}

	/**
	 * Merge plugin or theme scripts with existing no-conflict scripts
	 *
	 * @since 1.15.2
	 *
	 * @param array $handles Array of script handles, as registered with WordPress
	 *
	 * @return array Handles, merged with existing scripts
	 */
	public function merge_noconflict_scripts( $handles ) {
		$handles = array_merge( $this->script_handles, $handles );
		return $handles;
	}

	/**
	 * Merge plugin or theme meta keys that store shortcode data with existing keys to check
	 *
	 * @since 1.15.2
	 *
	 * @deprecated 2.0.7
	 *
	 * @param array $handles Array of meta keys to check for existence of shortcodes
	 * @param int   $post_id The ID being checked by GravityView
	 *
	 * @return array Meta key array, merged with existing meta keys
	 */
	public function merge_meta_keys( $meta_keys = array(), $post_id = 0 ) {
		return array_merge( $this->meta_keys, $meta_keys );
	}

	/**
	 * Merge plugin or theme meta keys that store shortcode data with existing keys to check
	 *
	 * @since 2.0.7
	 *
	 * @param array    $handles Array of meta keys to check for existence of shortcodes
	 * @param \WP_Post $post The ID being checked by GravityView
	 *
	 * @return array Meta key array, merged with existing meta keys
	 */
	public function merge_content_meta_keys( $meta_keys = array(), $post = null, &$views = null ) {
		return array_merge( $this->content_meta_keys, $meta_keys );
	}
}
