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
if ( ! class_exists( '\Gamajo_Template_Loader' ) ) {
	require gravityview()->plugin->dir( 'future/lib/class-gamajo-template-loader.php' );
}

/**
 * The Template abstract class.
 *
 * Stores information on where a template to render an object is,
 *  and other metadata.
 */
abstract class Template extends \Gamajo_Template_Loader {
	/**
	 * Prefix for filter names.
	 * @var string
	 */
	protected $filter_prefix = 'gravityview/future';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 * @var string
	 */
	protected $theme_template_directory = 'gravityview/future';

	/**
	 * Reference to the root directory path of this plugin.
	 * @var string
	 */
	protected $plugin_directory;

	public function __construct() {
		$this->plugin_directory = gravityview()->plugin->dir();

		add_filter( $this->filter_prefix . '_template_paths', array( $this, 'add_future_template_paths' ) );
	}

	/**
	 * Add the future/templates directory path.
	 *
	 * @since future
	 * @internal
	 *
	 * @param array $file_paths The default file paths.
	 * @return array The modified file paths.
	 */
	public function add_future_template_paths( $file_paths ) {
		$file_paths[100] = gravityview()->plugin->dir( 'future/templates/' );
		return $file_paths;
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

		$dir_name = ( dirname( $slug ) != '.' ) ? trailingslashit( dirname( $slug ) ) : '';
		$slug_name = basename( $slug ) . ( $name ? "-$name" : '' );

		return array( $dir_name, $slug_name );
	}
}
