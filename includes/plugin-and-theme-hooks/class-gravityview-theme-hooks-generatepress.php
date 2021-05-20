<?php
/**
 * Add support for GeneratePress theme
 *
 * @file      class-gravityview-theme-hooks-generatepress.php
 * @since     2.10.3
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
 * @link      http://gravityview.co
 * @copyright Copyright 2021, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

/**
 * @inheritDoc
 * @since 2.10.3
 */
class GravityView_Theme_Hooks_GeneratePress extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $content_meta_keys = array(
		'_generate-sidebar-layout-meta',
		'_generate-footer-widget-meta',
	);

	/**
	 * @inheritDoc
	 * @since 2.10.3
	 */
	public function __construct() {
		if ( 'generatepress' !== wp_get_theme()->__get( 'template' ) ) {
			return;
		}

		parent::__construct();

		add_filter( 'render_block', array( $this, 'detect_views_in_block_content' ) );
	}

	/**
	 * Detect GV Views in parsed Gutenberg block content
	 *
	 * @since 2.10.3
	 *
	 * @see   \WP_Block::render()
	 *
	 * @param string $block_content Gutenberg block content
	 *
	 * @return false|string
	 *
	 * @todo Once we stop using the legacy `GravityView_frontend::parse_content()` method to detect Views in post content, this code should either be dropped or promoted to some core class given its applicability to other themes/plugins
	 */
	public function detect_views_in_block_content( $block_content ) {
		if ( ! class_exists( 'GV\View_Collection' ) || ! class_exists( 'GV\View' ) ) {
			return $block_content;
		}

		$gv_view_data = GravityView_View_Data::getInstance();

		$views = \GV\View_Collection::from_content( $block_content );

		foreach ( $views->all() as $view ) {
			if ( ! $gv_view_data->views->contains( $view->ID ) ) {
				$gv_view_data->views->add( $view );
			}
		}

		return $block_content;
	}
}

new GravityView_Theme_Hooks_GeneratePress;