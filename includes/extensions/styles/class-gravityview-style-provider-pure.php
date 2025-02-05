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
class GravityView_Style_Provider_Pure extends GravityView_Style_Provider {

	public static $slug = 'pure';

	public static $style_slug = 'gravityview-pure';

	public static $css_file_name = 'pure.min.css';

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		$this->name = __( 'Pure', 'gravityview' );
	}
}

GravityView_Style::register( 'GravityView_Style_Provider_Pure' );
