<?php
/**
 * Add SiteOrigin plugin theme compatibility to GravityView
 *
 * @file      class-gravityview-theme-hooks-siteorigin.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 2.0.7
 */

/**
 * @inheritDoc
 * @since 2.0.7
 */
class GravityView_Theme_Hooks_SiteOrigin extends GravityView_Plugin_and_Theme_Hooks {

	protected $constant_name = 'SITEORIGIN_PANELS_VERSION';

	protected $class_name = 'SiteOrigin_Panels';

	protected $content_meta_keys = array(
		'panels_data'
	);

	/**
	 * Add support for SiteOrigin storage of widget information
	 *
	 * @since 2.0.7
	 *
	 * @param array $meta_keys
	 * @param null $post
	 * @param \GV\View_Collection $views
	 *
	 * @return array
	 */
	function merge_content_meta_keys( $meta_keys = array(), $post = null, & $views = null ) {

		if( empty( $post->panels_data ) || empty( $post->panels_data['widgets'] ) ) {
			return $meta_keys;
		}

		foreach ( (array) $post->panels_data['widgets'] as $widget ) {

			if ( empty( $widget['text'] ) ) {
				continue;
			}

			$views->merge( \GV\View_Collection::from_content( $widget['text'] ) );
		}

		return $meta_keys;
	}

}

new GravityView_Theme_Hooks_SiteOrigin;