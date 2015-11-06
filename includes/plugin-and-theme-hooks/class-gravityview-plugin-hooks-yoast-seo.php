<?php
/**
 * Add Yoast SEO scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-plugin-hooks-yoast-seo.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15.2
 */

/**
 * @inheritDoc
 */
class GravityView_Plugin_Hooks_Yoast_SEO extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 */
	protected $constant_name = 'WPSEO_FILE';

	/**
	 * @inheritDoc
	 */
	protected $style_handles = array(
		'wp-seo-metabox',
		'wpseo-admin-media',
		'metabox-tabs',
		'metabox-classic',
		'metabox-fresh',
	);

	/**
	 * @inheritDoc
	 */
	protected $script_handles = array(
		'wp-seo-metabox',
		'wpseo-admin-media',
		'jquery-qtip',
		'jquery-ui-autocomplete',
	);

	/**
	 * @inheritDoc
	 * @copydoc GravityView_Plugin_and_Theme_Hooks::add_hooks()
	 */
	protected function add_hooks() {

		parent::add_hooks();

		// Make Yoast metabox go down to the bottom please.
		add_filter( 'wpseo_metabox_prio', array( $this, '__return_low') );
	}

	/**
	 * Return 'low' as the status for metabox priority when on a GravityView post type admin screen
	 *
	 * @since 1.15.2 Moved from class-gravityview-admin-metaboxes.php
	 * @since 1.15.2 Added check for GravityView post type
	 *
	 * @param string $existing Existing priority. Default: `high`
	 * @return string 'low' when a GravityView screen; $existing otherwise
	 */
	function __return_low( $existing = 'high' ) {

		$return = $existing;

		// No reason Version 3.1 *would* be running, but might as well prevent WSOD
		if( function_exists( 'get_current_screen' ) ) {

			$screen = get_current_screen();

			// isset() check is for < 3.3, which is again, just in case.
			if ( isset( $screen->id ) && 'gravityview' === $screen->id ) {
				$return = 'low';
			}

		}

		return $return;
	}
}

new GravityView_Plugin_Hooks_Yoast_SEO;