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
class GravityView_Style_Provider_Sakura extends GravityView_Style_Provider {

	public static $slug = 'sakura';

	public static $style_slug = 'gravityview-sakura';

	public static $css_file_name = 'sakura.min.css';

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		$this->name = __( 'Sakura', 'gravityview' );
	}
}

GravityView_Style::register( 'GravityView_Style_Provider_Sakura' );
