<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\Renderer class.
 *
 * The base for all renderers.
 */
class Renderer {
	/**
	 * Initialization.
	 */
	public function __construct() {
		if ( ! has_action( 'gravityview/template/before', array( __CLASS__, 'maybe_print_notices' ) ) ) {
			add_action( 'gravityview/template/before', array( __CLASS__, 'maybe_print_notices' ) );
		}
	}

	/**
	 * Print unconfigured notices to admins.
	 * Print reserved slug warnings.
	 *
	 * @param \GV\Template_Context $gravityview The $gravityview template object.
	 *
	 * @return void
	 */
	public static function maybe_print_notices( $gravityview = null ) {
		if ( ! $gravityview instanceof \GV\Template_Context ) {
			/** Call the legacy code. */
			\GravityView_frontend::getInstance()->context_not_configured_warning( gravityview_get_view_id() );
			return;
		}

		/**
		 * Check reserved slugs.
		 */
		global $wp;
		global $wp_rewrite;

		$reserved_slugs = array(
			$wp_rewrite->search_base,
			apply_filters( 'gravityview_directory_endpoint', 'entry' ),
		);

		$post_types = get_post_types();

		foreach( $post_types as $post_type ) {
			$post_type_rewrite = get_post_type_object( $post_type )->rewrite;

			if ( $slug = \GV\Utils::get( $post_type_rewrite, 'slug' ) ) {
				$reserved_slugs[] = $slug;
			}
		}

		unset( $post_types, $post_type_rewrite );

		/**
		 * @filter `gravityview/rewrite/reserved_slugs` Modify the reserved embed slugs that trigger a warning.
		 * @since 2.5
		 * @param[in,out] array $reserved_slugs An array of strings, reserved slugs.
		 * @param \GV\Template_Context $gravityview The context.
		 */
		$reserved_slugs = apply_filters( 'gravityview/rewrite/reserved_slugs', $reserved_slugs, $gravityview );

		$reserved_slugs = array_map( 'strtolower', $reserved_slugs );

		if ( in_array( strtolower( $wp->request ), $reserved_slugs, true ) ) {
			gravityview()->log->error( '{slug} page URL is reserved.', array( 'slug' => $wp->request ) );

			$title   = esc_html__( 'GravityView will not work correctly on this page because of the URL Slug.', 'gravityview' );
			$message = __( 'Please <a href="%s">read this article</a> for more information.', 'gravityview' );
			$message .= ' ' . esc_html__( 'You can only see this message because you are able to edit this View.', 'gravityview' );

			$output = sprintf( '<h3>%s</h3><p>%s</p>', $title, sprintf( $message, 'https://docs.gravityview.co/article/659-reserved-urls' ) );

			echo \GVCommon::generate_notice( $output, 'gv-error error', 'edit_gravityview', $gravityview->view->ID );
		}

		/**
		 * Check empty configuration.
		 */
		switch ( true ) {
			case ( $gravityview->request->is_edit_entry() ):
				$tab = __( 'Edit Entry', 'gravityview' );
				$context = 'edit';
				break;
			case ( $gravityview->request->is_entry( $gravityview->view->form ? $gravityview->view->form->ID : 0 ) ):
				$tab = __( 'Single Entry', 'gravityview' );
				$context = 'single';
				break;
			default:
				$tab = __( 'Multiple Entries', 'gravityview' );
				$context = 'directory';
				break;
		}

		$cls = $gravityview->template;
		$slug = property_exists( $cls, '_configuration_slug' ) ? $cls::$_configuration_slug : $cls::$slug;
		if ( $gravityview->fields->by_position( sprintf( '%s_%s-*', $context, $slug ) )->by_visible( $gravityview->view )->count() ) {
			return;
		}

		$title = sprintf( esc_html_x( 'The %s layout has not been configured.', 'Displayed when a View is not configured. %s is replaced by the tab label', 'gravityview' ), $tab );
		$edit_link = admin_url( sprintf( 'post.php?post=%d&action=edit#%s-view', $gravityview->view->ID, $context ) );
		$action_text = sprintf( esc_html__( 'Add fields to %s', 'gravityview' ), $tab );
		$message = esc_html__( 'You can only see this message because you are able to edit this View.', 'gravityview' );

		$image =  sprintf( '<img alt="%s" src="%s" style="margin-top: 10px;" />', $tab, esc_url( plugins_url( sprintf( 'assets/images/tab-%s.png', $context ), GRAVITYVIEW_FILE ) ) );
		$output = sprintf( '<h3>%s <strong><a href="%s">%s</a></strong></h3><p>%s</p>', $title, esc_url( $edit_link ), $action_text, $message );

		echo \GVCommon::generate_notice( $output . $image, 'gv-warning warning', 'edit_gravityview', $gravityview->view->ID );
	}

	/**
	 * Warn about legacy template being used.
	 *
	 * Generate a callback that shows which legacy template was at fault.
	 * Used in gravityview_before.
	 *
	 * @param \GV\View $view The view we're looking at.
	 * @param string $path The path of the offending template.
	 *
	 * @return \Callable A closure used in the filter.
	 */
	public function legacy_template_warning( $view, $path ) {
		return function() use ( $view, $path ) {
			// Do not panic for now...
		};
	}
}
