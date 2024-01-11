<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Load up the Gamajo Template Loader.
 *
 * @see https://github.com/GaryJones/Gamajo-Template-Loader
 */
if ( ! class_exists( '\GV\Gamajo_Template_Loader' ) ) {
	require gravityview()->plugin->dir( 'future/lib/class-gamajo-template-loader.php' );
}

/**
 * The Template abstract class.
 *
 * Stores information on where a template to render an object is,
 *  and other metadata.
 */
abstract class Template extends \GV\Gamajo_Template_Loader {

	/**
	 * @var array The template data stack.
	 */
	private static $data_stack = array();

	/**
	 * @var string The located template.
	 */
	public $located_template = '';

	/**
	 * General template initialization.
	 *
	 * Sets the $plugin_directory field.
	 */
	public function __construct() {
		/** Set plugin directory. */
		$this->plugin_directory = gravityview()->plugin->dir();
	}

	/**
	 * Disallow any cleanup for fear of loss of global data.
	 *
	 * The destructor in Gamajo 1.3.0 destroys all of `$wp_query`.
	 * This has the chance of inappropriately destroying valid
	 *  data that's been stored under the same key.
	 *
	 * Disallow this.
	 */
	public function __destruct() {
	}

	/**
	 * Get a directory part and a full slug+name (file) components.
	 *
	 * @param string $slug The slug, template base.
	 * @param string $name The name, template part. Default: null
	 *
	 * @return array containing slug directory and slug+name.
	 */
	public static function split_slug( $slug, $name = null ) {

		$dir_name  = ( '.' != dirname( $slug ) ) ? trailingslashit( dirname( $slug ) ) : '';
		$slug_name = basename( $slug ) . ( $name ? "-$name" : '' );

		return array( $dir_name, $slug_name );
	}

	/**
	 * Push the current template data down the stack and set.
	 *
	 * This allows us to use the same variable in the template scope
	 *  without destroying data under the same variable in a nested
	 *  or parallel template.
	 *
	 * @param mixed  $data The data to set.
	 * @param string $var_name The data variable identifier (Default: "data")
	 *
	 * @see \Gamajo_Template_Loader::set_template_data
	 * @see \GV\Template::pop_template_data
	 *
	 * @return \GV\Gamajo_Template_Loader The current instance.
	 */
	public function push_template_data( $data, $var_name = 'data' ) {
		if ( ! isset( self::$data_stack[ $var_name ] ) ) {
			self::$data_stack[ $var_name ] = array();
		}

		global $wp_query;

		if ( isset( $wp_query->query_vars[ $var_name ] ) ) {
			array_push( self::$data_stack[ $var_name ], $wp_query->query_vars[ $var_name ] );
		}

		$this->set_template_data( $data, $var_name );

		return $this;
	}

	/**
	 * Restore the template data from the stack.
	 *
	 * @param string $var_name The data variable identifier (Default: "data")
	 *
	 * @see \Gamajo_Template_Loader::set_template_data
	 * @see \GV\Template::pop_template_data
	 *
	 * @return $this;
	 */
	public function pop_template_data( $var_name = 'data' ) {
		if ( ! empty( self::$data_stack[ $var_name ] ) ) {
			$this->set_template_data( array_pop( self::$data_stack[ $var_name ] ), $var_name );
		}

		return $this;
	}
}
