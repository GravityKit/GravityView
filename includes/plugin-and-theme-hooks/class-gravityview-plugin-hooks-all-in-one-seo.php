<?php
/**
 * Add support for the All In One SEO plugin
 *
 * @file      class-gravityview-plugin-hooks-all-in-one-seo.php
 * @since     2.10.3
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2021, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

/**
 * @inheritDoc
 * @since 2.10.3
 */
class GravityView_Plugin_Hooks_All_In_One_SEO extends GravityView_Plugin_and_Theme_Hooks {
	/**
	 * @inheritDoc
	 * @since 2.10.3
	 */
	protected $constant_name = 'AIOSEO_FILE';

	protected $style_handles = array(
		'aioseo-app-style',
		'aioseo-common',
		'aioseo-post-settings-metabox',
		'aioseo-vendors',
	);

	/**
	 * @inheritDoc
	 * @since 2.10.3
	 */
	protected $script_handles = array(
		'aioseo-app',
		'aioseo-common',
		'aioseo-link-format',
		'aioseo-post-settings-metabox',
		'aioseo-vendors',
	);

	/**
	 * @inheritDoc
	 * @since 2.17
	 */
	protected function add_hooks() {
		add_filter( 'pre_do_shortcode_tag', array( $this, 'skip_shortcode_processing' ), 10, 4 );
	}

	/**
	 * Prevent AIO SEO from processing GV shortcode
	 *
	 * @since 2.10.3
	 *
	 * @see   do_shortcode_tag();
	 *
	 * @param string       $shortcode_tag Shortcode name
	 * @param false|string $return        Short-circuit return value: false or the value to replace the shortcode with
	 *
	 * @return false|string
	 */
	public function skip_shortcode_processing( $result, $shortcode_tag ) {
		if ( 'gravityview' === $shortcode_tag && preg_match( '/AIOSEO/', json_encode( debug_backtrace() ) ) ) {
			return '';
		}

		return $result;
	}
}

new GravityView_Plugin_Hooks_All_In_One_SEO();
