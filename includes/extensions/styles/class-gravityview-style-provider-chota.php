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
class GravityView_Style_Provider_Chota extends GravityView_Style_Provider {

	public static $slug = 'chota';

	public static $style_slug = 'gravityview-chota';

	public static $css_file_name = 'chota.min.css';

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		$this->name = __( 'Chota', 'gk-gravityview' );
	}


}

GravityView_Style::register( 'GravityView_Style_Provider_Chota' );
