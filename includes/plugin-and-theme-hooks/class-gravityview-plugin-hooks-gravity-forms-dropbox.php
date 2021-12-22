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

		add_filter( 'gravityview/fields/fileupload/extension', array( $this, 'filter_file_path' ) );
		add_filter( 'gravityview/fields/fileupload/file_path', array( $this, 'filter_file_path' ), 10, 3 );
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

		$output = $string;
		$output = str_replace('www.dropbox.com', 'dl.dropboxusercontent.com', $output );
		$output = str_replace( '?dl=0', '', $output );

		return $output;
	}
}

new GravityView_Plugin_Hooks_Gravity_Forms_Dropbox;
