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
if ( ! class_exists( 'Gamajo_Template_Loader' ) ) {
	require gravityview()->plugin->dir( 'future/lib/class-gamajo-template-loader.php' );
}

/**
 * The View Template class .
 *
 * Attached to a \GV\View and used by a \GV\View_Renderer.
 */
class View_Template extends Template {

	/**
	 * @var string The template identifier.
	 *
	 * For example, "default_list" or "default_table".
	 */
	public $ID;

	/**
	 * Initializer.
	 *
	 * @param string $ID The ID of this template.
	 */
	public function __construct( $ID ) {
		$this->ID = $ID;
	}
}
