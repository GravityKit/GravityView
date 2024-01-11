<?php
/**
 * Add Yoast SEO scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-plugin-hooks-yoast-seo.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
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

	protected $style_handles = array(
		'wp-seo-metabox',
		'wpseo-admin-media',
		'yoast-seo-admin-media',
		'yoast-seo-snippet',
		'yoast-seo-kb-search',
		'metabox-tabs',
		'metabox-classic',
		'metabox-fresh',
		// Yoast 14.7
		'yoast-seo-admin-css',
		'yoast-seo-admin-global',
		'yoast-seo-adminbar',
		'yoast-seo-alert',
		'yoast-seo-dismissible',
		'yoast-seo-edit-page',
		'yoast-seo-extensions',
		'yoast-seo-featured-image',
		'yoast-seo-filter-explanation',
		'yoast-seo-metabox-css',
		'yoast-seo-monorepo',
		'yoast-seo-notifications',
		'yoast-seo-primary-category',
		'yoast-seo-scoring',
		'yoast-seo-search-appearance',
		'yoast-seo-select2',
		'yoast-seo-structured-data-blocks',
		'yoast-seo-toggle-switch',
		'yoast-seo-wp-dashboard',
		'yoast-seo-yoast-components',
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
		'yoast-seo-admin-media',
		'yoast-seo-admin-script',
		// Yoast 14.7
		'yoast-seo-admin-global-script',
		'yoast-seo-analysis',
		'yoast-seo-api',
		'yoast-seo-bulk-editor',
		'yoast-seo-commons',
		'yoast-seo-components',
		'yoast-seo-configuration-wizard',
		'yoast-seo-dashboard-widget',
		'yoast-seo-draft-js',
		'yoast-seo-edit-page-script',
		'yoast-seo-filter-explanation',
		'yoast-seo-help-scout-beacon',
		'yoast-seo-indexation',
		'yoast-seo-jed',
		'yoast-seo-network-admin-script',
		'yoast-seo-post-edit',
		'yoast-seo-quick-edit-handler',
		'yoast-seo-recalculate',
		'yoast-seo-redux',
		'yoast-seo-reindex-links',
		'yoast-seo-search-appearance',
		'yoast-seo-select2',
		'yoast-seo-select2-translations',
		'yoast-seo-settings',
		'yoast-seo-structured-data-blocks',
		'yoast-seo-styled-components',
		'yoast-seo-term-edit',
		'yoast-seo-yoast-modal',
		// Yoast 14.8
		'yoast-seo-post-edit-classic',
	);

	/**
	 * @inheritDoc
	 * @copydoc GravityView_Plugin_and_Theme_Hooks::add_hooks()
	 * @since 1.15.2
	 */
	protected function add_hooks() {

		parent::add_hooks();

		if ( gravityview()->request->is_admin( '', null ) ) {

				// Make Yoast metabox go down to the bottom please.
			add_filter( 'wpseo_metabox_prio', array( $this, 'return_low' ) );

			// Prevent the SEO from being checked. Eesh.
			add_filter( 'wpseo_use_page_analysis', '__return_false' );

			add_filter( 'option_wpseo', array( $this, 'disable_content_analysis' ) );

			// WordPress SEO Plugin
			add_filter( 'option_wpseo_titles', array( $this, 'hide_wordpress_seo_metabox' ) );
		}
	}

	/**
	 * Don't try to analyze content for Views
	 *
	 * @since  1.22.4
	 * @param  array $options Existing WPSEO options array
	 *
	 * @return array
	 */
	public function disable_content_analysis( $options ) {

		$options['keyword_analysis_active'] = false;
		$options['content_analysis_active'] = false;

		return $options;
	}

	/**
	 * Modify the WordPress SEO plugin's metabox behavior
	 *
	 * Only show when the View has been configured.
	 *
	 * @since 1.15.2 Moved from class-gravityview-admin-metaboxes.php
	 *
	 * @param  array $options WP SEO options array
	 * @return array               Modified array if on post-new.php
	 */
	public function hide_wordpress_seo_metabox( $options = array() ) {
		global $pagenow;

		// New View page
		if ( 'post-new.php' === $pagenow ) {
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
	function return_low( $existing = 'high' ) {
		return 'low';
	}
}

new GravityView_Plugin_Hooks_Yoast_SEO();
