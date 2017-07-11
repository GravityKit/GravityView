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
 * Requires WPML 3.6.2 or newer
 *
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
		'sitepress-style',
		'wpml-select-2',
		'wpml-tm-styles',
		'wpml-tm-queue',
		'wpml-dialog',
		'wpml-tm-editor-css',
		'otgs-dialogs',
		'otgs-ico',
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

		if( $wpml_url_filters ) {
			$link = $wpml_url_filters->permalink_filter( $link, GravityView_frontend::getInstance()->getPostId() );
		}

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

		if( ! $wpml_url_filters ) {
			return;
		}

		// WPML 3.6.1 and lower does not have this method, avoid a fatal error.
		if ( method_exists( $wpml_url_filters, 'remove_global_hooks' ) ) {
			$wpml_url_filters->remove_global_hooks();
		} else {
			do_action( 'gravityview_log_error', '[GravityView_Theme_Hooks_WPML::remove_url_hooks] WPML missing remove_global_hooks method. Needs version 3.6.2+.' );
		}

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

		if( ! $wpml_url_filters ) {
			return;
		}

		// WPML 3.6.1 and lower does not have this method, avoid a fatal error.
		if ( method_exists( $wpml_url_filters, 'add_global_hooks' ) ) {
			$wpml_url_filters->add_global_hooks();
		} else {
			do_action( 'gravityview_log_error', '[GravityView_Theme_Hooks_WPML::add_url_hooks] WPML missing add_global_hooks method. Needs version 3.6.2+.' );
		}

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

		/**
		 * @global SitePress $sitepress
		 * @global WP_Post $post
		 * @global WPML_URL_Converter $wpml_url_converter
		 */
		global $sitepress, $post, $wpml_url_converter;

		if ( $entry_slug = GravityView_frontend::getInstance()->getSingleEntry() ) {

			if ( ! method_exists( $sitepress, 'get_setting' ) ) {
				do_action( 'gravityview_log_error', __METHOD__ . ': This version of WPML is outdated and does not include the required method get_setting().' );
				return $languages;
			}

			$trid         = $sitepress->get_element_trid( $post->ID );
			$translations = $sitepress->get_element_translations( $trid );
			$language_url_setting = $sitepress->get_setting( 'language_negotiation_type' );

			$this->remove_url_hooks();

			if( $translations ) {
				foreach ( $languages as $lang_code => $language ) {

					$lang_post_id = $translations[ $lang_code ]->element_id;

					$entry_link = GravityView_API::entry_link( $entry_slug, $lang_post_id );

					// How is WPML handling the language?
					switch ( intval( $language_url_setting ) ) {

						// Subdomains or directories
						case 1:
						case 2:
							// For sites using directories or sub-domains for languages, rewrite base URL
							$entry_link = $wpml_url_converter->convert_url( $entry_link, $lang_code );
							break;

						// URL Parameters
						case 3:
						default:
							if ( ! empty( $translations[ $lang_code ]->original ) ) {

								// The original language doesn't need a language parameter
								$entry_link = remove_query_arg( 'lang', $entry_link );

							} elseif ( $entry_link ) {

								// Every other language does
								$entry_link = add_query_arg( array( 'lang' => $lang_code ), $entry_link );
							}
							break;
					}

					$languages[ $lang_code ]['url'] = $entry_link;
				}
			}

			$this->add_url_hooks();
		}

		return $languages;
	}

}

new GravityView_Theme_Hooks_WPML;