<?php
/**
 * Add WPML compatibility to GravityView, including registering scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-theme-hooks-wpml.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.19.2
 */

/**
 * @inheritDoc
 */
class GravityView_Theme_Hooks_WPML extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.19.2
	 */
	protected $script_handles = array(
		'wpml-cpi-scripts',
		'sitepress-scripts',
		'sitepress-post-edit',
		'sitepress-post-list-quickedit',
		'sitepress-languages',
		'sitepress-troubleshooting',
	);

	/**
	 * @inheritDoc
	 * @since 1.19.2
	 */
	protected $style_handles = array(
		'wpml-select-2',
		'wpml-tm-styles',
		'wpml-tm-queue',
		'wpml-dialog',
		'wpml-tm-editor-css',
	);

	/**
	 * @inheritDoc
	 * @since 1.19.2
	 */
	protected $constant_name = 'ICL_SITEPRESS_VERSION';

	/**
	 * Add filters for WPML links
	 *
	 * @since 1.19.4
	 */
	public function add_hooks() {

		parent::add_hooks();

		add_filter( 'icl_ls_languages', array( $this, 'wpml_ls_filter' ) );

		add_filter( 'gravityview_directory_link', array( $this, 'filter_gravityview_back_link') );
	}

	/**
	 * Add WPML filters to GravityView directory link
	 *
	 * This also modifies all the Edit Entry, Cancel Edit, Go Back links
	 *
	 * @since 1.19.4
	 *
	 * @see GravityView_API::directory_link
	 * @uses WPML_URL_Filters::permalink_filter
	 *
	 * @param string $link Permalink to the GravityView directory, without language params
	 *
	 * @return string $link, with language params added by WPML
	 */
	function filter_gravityview_back_link( $link ) {
		global $wpml_url_filters;

		$link = $wpml_url_filters->permalink_filter( $link, GravityView_frontend::getInstance()->getPostId() );

		return $link;
	}

	/**
	 * Remove WPML permalink filters
	 *
	 * @since 1.19.4
	 *
	 * @return void
	 */
	private function remove_url_hooks() {
		global $wpml_url_filters;

		$wpml_url_filters->remove_global_hooks();

		if ( $wpml_url_filters->frontend_uses_root() === true ) {
			remove_filter( 'page_link', array( $wpml_url_filters, 'page_link_filter_root' ), 1 );
		} else {
			remove_filter( 'page_link', array( $wpml_url_filters, 'page_link_filter' ), 1 );
		}
	}

	/**
	 * Add the WPML permalink filters back in
	 *
	 * @since 1.19.4
	 *
	 * @return void
	 */
	private function add_url_hooks() {
		global $wpml_url_filters;

		$wpml_url_filters->add_global_hooks();

		if ( $wpml_url_filters->frontend_uses_root() === true ) {
			add_filter( 'page_link', array( $wpml_url_filters, 'page_link_filter_root' ), 1, 2 );
		} else {
			add_filter( 'page_link', array( $wpml_url_filters, 'page_link_filter' ), 1, 2 );
		}
	}

	/**
	 * Modify the language links to fix /entry/ var from being stripped
	 *
	 * @since 1.19.4
	 *
	 * @param array $languages Array of active languages with their details
	 *
	 * @return array If currently a single entry screen, re-generate URL after removing WPML filters
	 */
	public function wpml_ls_filter( $languages ) {
		global $sitepress, $post;

		if ( $entry_slug = GravityView_frontend::getInstance()->getSingleEntry() ) {

			$trid         = $sitepress->get_element_trid( $post->ID );
			$translations = $sitepress->get_element_translations( $trid );

			$this->remove_url_hooks();

			if( $translations ) {
				foreach ( $languages as $lang_code => $language ) {

					$lang_post_id = $translations[ $lang_code ]->element_id;

					$entry_link = GravityView_API::entry_link( $entry_slug, $lang_post_id );

					if ( ! empty( $translations[ $lang_code ]->original ) ) {

						// The original doesn't need a language parameter
						$languages[ $lang_code ]['url'] = remove_query_arg( 'lang', $entry_link );

					} elseif ( $entry_link ) {

						// Every other language does
						$languages[ $lang_code ]['url'] = add_query_arg( array( 'lang' => $lang_code ), $entry_link );

					}
				}
			}

			$this->add_url_hooks();
		}

		return $languages;
	}

}

new GravityView_Theme_Hooks_WPML;