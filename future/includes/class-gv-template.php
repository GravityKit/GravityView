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
