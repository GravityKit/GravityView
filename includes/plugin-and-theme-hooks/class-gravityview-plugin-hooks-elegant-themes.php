<?php
/**
 * Add Elegant Themes compatibility to GravityView (Divi theme)
 *
 * @file      class-gravityview-theme-hooks-elegant-themes.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2016', Katz Web Services, Inc.
 *
 * @since 1.17.2
 */

/**
 * @inheritDoc
 * @since 1.17.2
 */
class GravityView_Theme_Hooks_Elegant_Themes extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.17.2
	 */
	protected $function_name = 'et_setup_theme';

	function add_hooks() {
		parent::add_hooks();

		add_action( 'admin_init', [ $this, 'add_hooks_admin_init' ], 1 );
		add_filter( 'et_builder_enable_jquery_body', [ $this, 'edit_entry_jquery_compat_fix' ] );
	}

	/**
	 * Prevent Divi from adding their stuff to GV pages
	 */
	public function add_hooks_admin_init() {
		if ( gravityview()->request->is_admin( '', null ) ) {
			// Prevent Divi from adding import/export modal dialog
			remove_action( 'admin_init', 'et_pb_register_builder_portabilities' );

			// Divi theme adds their quicktag shortcode buttons on a View CPT. This causes JS errors.
			remove_action( 'admin_head', 'et_add_simple_buttons' );
		}

		// Prevent Divi from rendering the sidebar with one of our Widgets in Page Builder.
		// See: https://github.com/gravityview/GravityView/issues/914
		add_action( 'et_pb_admin_excluded_shortcodes', array( $this, 'maybe_admin_excluded_shortcodes' ) );
	}

	/**
	 * Maybe prevent Divi (and others) from rendering our Widgets in the Page Builders Sidebar widget.
	 *
	 * Divi (among others) tries to render all the widgets in the sidebar.
	 * Our Widgets are not designed to be rendered in the administration panel.
	 *
	 * Try to find the sidebar it wants to render, see if it contains our Widgets
	 *  and prevent it from being rendered if it does. Allow everything else through.
	 *
	 * @see https://github.com/gravityview/GravityView/issues/914
	 *
	 * @param array $shortcodes The shortcodes that should not be rendered in the Page Builder.
	 *
	 * @return array The shortcodes that should not be rendered in the Page Builder.
	 */
	public function maybe_admin_excluded_shortcodes( $shortcodes ) {
		global $post;

		if ( ! $post || ! $post->post_content ) {
			return $shortcodes;
		}

		/**
		 * Find the et_pb_sidebar shortcode and the area it's assigned to.
		 */
		preg_match( '#\[et_pb_sidebar .*area="(.*?)"#', $post->post_content, $matches );

		if ( 2 != count( $matches ) ) {
			return $shortcodes;
		}

		$sidebars_widgets = wp_get_sidebars_widgets();
		if ( empty( $sidebars_widgets[ $matches[1] ] ) ) {
			return $shortcodes;
		}

		foreach ( $sidebars_widgets[ $matches[1] ] as $widgets ) {
			if (
				/**
				 * Blocklisted widgets.
				 */
				0 === strpos( $widgets, 'gravityview_search' ) ||
				0 === strpos( $widgets, 'gv_recent_entries' )
			) {

					$shortcodes [] = 'et_pb_sidebar';
					break;
			}
		}

		return $shortcodes;
	}

	/**
	 * Prevents Divi from "faking" jQuery on the Edit Entry screen.
	 * This breaks some functionality, such as the Gravity Forms Signature Add-On.
	 *
	 * @used-by `et_builder_enable_jquery_body` filter
	 *
	 * @since TBD
	 *
	 * @param bool $enable_jquery_compat
	 *
	 * @return bool
	 */
	public function edit_entry_jquery_compat_fix( $enable_jquery_compat ) {
		return gravityview()->request->is_edit_entry() ? false : $enable_jquery_compat;
	}
}

new GravityView_Theme_Hooks_Elegant_Themes();
