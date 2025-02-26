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
class GravityView_Style_Provider_PicoCSS extends GravityView_Style_Provider {

	public static $slug = 'picocss';

	public static $style_slug = 'gravityview-picocss';

	public static $css_file_name = 'pico.min.css';

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		$this->name = __( 'PicoCSS', 'gk-gravityview' );
	}
}

GravityView_Style::register( 'GravityView_Style_Provider_PicoCSS' );
