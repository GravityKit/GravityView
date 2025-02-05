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
class GravityView_Style_Provider_Marx extends GravityView_Style_Provider {

	public static $slug = 'marx';

	public static $style_slug = 'gravityview-marx';

	public static $css_file_name = 'marx.min.css';

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		$this->name = __( 'Marx', 'gravityview' );
	}

}

GravityView_Style::register( 'GravityView_Style_Provider_Marx' );
