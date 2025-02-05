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
class GravityView_Style_Provider_Orbital extends GravityView_Style_Provider {

	public static $slug = 'orbital';

	public static $style_slug = 'gravityview-orbital';

	public static $css_file_name = 'orbital.css';

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		$this->name = __( 'Orbital', 'gravityview' );
	}
}

GravityView_Style::register( 'GravityView_Style_Provider_Orbital' );
