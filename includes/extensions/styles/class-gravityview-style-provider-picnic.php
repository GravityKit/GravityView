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
class GravityView_Style_Provider_Picnic extends GravityView_Style_Provider {

	public static $slug = 'picnic';

	public static $style_slug = 'gravityview-picnic';

	public static $css_file_name = 'picnic.min.css';

}

GravityView_Style::register( 'GravityView_Style_Provider_Picnic' );
