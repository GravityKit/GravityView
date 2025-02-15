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
class GravityView_Style_Provider_Simple extends GravityView_Style_Provider {

	public static $slug = 'simple';

	public static $style_slug = 'gravityview-simple';

	public static $css_file_name = 'simple.min.css';

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		$this->name = __( 'Simple', 'gk-gravityview' );
	}
}

GravityView_Style::register( 'GravityView_Style_Provider_Simple' );
