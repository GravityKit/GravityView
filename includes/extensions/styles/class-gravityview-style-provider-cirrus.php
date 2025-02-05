<?php
/**
 * Adds a View style.
 *
 * @since TODO
 */

/**
 * Registers the style.
 *
 * @internal
 */
class GravityView_Style_Provider_Cirrus extends GravityView_Style_Provider {

	public static $slug = 'cirrus';

	public static $style_slug = 'gravityview-cirrus';

	public static $css_file_name = 'cirrus.min.css';

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		$this->name = __( 'Cirrus', 'gk-gravityview' );
	}

}

GravityView_Style::register( 'GravityView_Style_Provider_Cirrus' );
