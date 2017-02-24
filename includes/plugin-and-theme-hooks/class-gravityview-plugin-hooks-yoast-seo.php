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
 * @since 1.15.2
 */
class GravityView_Plugin_Hooks_Yoast_SEO extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $constant_name = 'WPSEO_FILE';

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $style_handles = array(
		'wp-seo-metabox',
		'wpseo-admin-media',
		'yoast-seo-metabox-css',
		'yoast-seo-admin-media',
		'yoast-seo-scoring',
		'yoast-seo-snippet',
		'yoast-seo-select2',
		'yoast-seo-kb-search',
		'metabox-tabs',
		'metabox-classic',
		'metabox-fresh',
	);

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $script_handles = array(
		'wp-seo-metabox',
		'wpseo-admin-media',
		'yoast-seo-metabox',
		'yoast-seo-admin-media',
		'yoast-seo-post-scraper',
		'yoast-seo-replacevar-plugin',
		'yoast-seo-shortcode-plugin',
		'jquery-qtip',
		'jquery-ui-autocomplete',
	);

	/**
	 * @inheritDoc
	 * @copydoc GravityView_Plugin_and_Theme_Hooks::add_hooks()
	 * @since 1.15.2
	 */
	protected function add_hooks() {

		parent::add_hooks();

		if( gravityview_is_admin_page() ) {

				// Make Yoast metabox go down to the bottom please.
			add_filter( 'wpseo_metabox_prio', array( $this, '__return_low' ) );

			// Prevent the SEO from being checked. Eesh.
			add_filter( 'wpseo_use_page_analysis', '__return_false' );

			// WordPress SEO Plugin
			add_filter( 'option_wpseo_titles', array( $this, 'hide_wordpress_seo_metabox' ) );
		}
	}

	/**
	 * Modify the WordPress SEO plugin's metabox behavior
	 *
	 * Only show when the View has been configured.
	 *
	 * @since 1.15.2 Moved from class-gravityview-admin-metaboxes.php
	 *
	 * @param  array       $options WP SEO options array
	 * @return array               Modified array if on post-new.php
	 */
	function hide_wordpress_seo_metabox( $options = array() ) {
		global $pagenow;

		// New View page
		if( $pagenow === 'post-new.php' ) {
			$options['hideeditbox-gravityview'] = true;
		}

		return $options;
	}

	/**
	 * Return 'low' as the status for metabox priority when on a GravityView post type admin screen
	 *
	 * @since 1.15.2 Moved from class-gravityview-admin-metaboxes.php
	 * @since 1.15.2 Added check for GravityView post type
	 *
	 * @param string $existing Existing priority. Default: `high`
	 * @return string Returns 'low'
	 */
	function __return_low( $existing = 'high' ) {
		return 'low';
	}
}

new GravityView_Plugin_Hooks_Yoast_SEO;