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
class GravityView_Style_Provider_MVP extends GravityView_Style_Provider {

	public static $slug = 'mvp';

	public static $style_slug = 'gravityview-mvp';

	public static $css_file_name = 'pure.min.css';

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		$this->name = __( 'MVP', 'gravityview' );
	}
}

GravityView_Style::register( 'GravityView_Style_Provider_MVP' );
