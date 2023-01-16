<?php
/**
 * Add Gravity Forms Dropbox compatibility
 *
 * @file      class-gravityview-plugin-hooks-gravity-forms-dropbox.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
 * @link      https://gravityview.co
 * @copyright Copyright 2017, Katz Web Services, Inc.
 *
 * @since 1.22.3
 */

/**
 * @since 1.22.3
 */
class GravityView_Plugin_Hooks_Gravity_Forms_Dropbox extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @var string gf_dropbox() wrapper function only exists in Version 2.x; don't want to support 1.x
	 * @since 1.22.3
	 */
	protected $function_name = 'gf_dropbox';

	/**
	 * @since 1.22.3
	 */
	protected function add_hooks() {
		parent::add_hooks();

		add_filter( 'gravityview/fields/fileupload/extension', array( $this, 'filter_file_extension' ) );
		add_filter( 'gravityview/fields/fileupload/file_path', array( $this, 'filter_file_path' ) );
		add_filter( 'gravityview/fields/fileupload/image_atts', array( $this, 'filter_image_atts' ) );
	}

	/**
	 * When the image file source includes `?raw=1`, don't validate the source, since we know it's Dropbox or similar.
	 *
	 * @since 2.14.3
	 *
	 * @param array{src:string,class:string,alt:string,width:string} $image_atts
	 *
	 * @return array Image attributes array, possibly with `validate_src` disabled.
	 */
	function filter_image_atts( $image_atts = array() ) {

		$image_source = rgar( $image_atts, 'src', '' );

		if ( false === strpos( $image_source, '?raw=1' ) ) {
			return $image_atts;
		}

		// The image source has ?raw=1; don't check for valid image extensions.
		$image_atts['validate_src'] = false;

		return $image_atts;
	}

	/**
	 * Convert links to view content on Dropbox.com to direct-access files.
	 *
	 * @since 1.22.3
	 *
	 * @param string $string Original string (file path or extension).
	 *
	 * @return string File path or extension, with Dropbox URLs modified.
	 */
	function filter_file_extension( $string = '' ) {

		$extension = str_replace( '?dl=0', '', $string );

		if ( in_array( $extension, GravityView_Image::get_image_extensions(), true ) ) {
			return $extension;
		}

		return $string;
	}

	/**
	 * Convert links to view content on Dropbox.com to direct-access files
	 *
	 * @since 1.22.3
	 *
	 * @param string $string Original string (file path or extension)
	 *
	 * @return string File path or extension, with Dropbox URLs modified
	 */
	function filter_file_path( $string = '' ) {
		return str_replace( '?dl=0', '?raw=1', $string );
	}
}

new GravityView_Plugin_Hooks_Gravity_Forms_Dropbox;
