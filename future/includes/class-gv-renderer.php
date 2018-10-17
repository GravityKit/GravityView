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
		if ( ! has_action( 'gravityview/template/after', array( __CLASS__, 'maybe_print_notices' ) ) ) {
			add_action( 'gravityview/template/after', array( __CLASS__, 'maybe_print_notices' ) );
		}
	}

	/**
	 * Print unconfigured notices to admins.
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
		if ( $gravityview->fields->by_position( sprintf( '%s_%s-*', $context, $slug ) )->by_visible()->count() ) {
			return;
		}
		
		$title = sprintf( esc_html_x( 'The %s layout has not been configured.', 'Displayed when a View is not configured. %s is replaced by the tab label', 'gravityview' ), $tab );
		$edit_link = admin_url( sprintf( 'post.php?post=%d&action=edit#%s-view', $gravityview->view->ID, $context ) );
		$action_text = sprintf( esc_html__( 'Add fields to %s', 'gravityview' ), $tab );
		$message = esc_html__( 'You can only see this message because you are able to edit this View.', 'gravityview' );

		$image =  sprintf( '<img alt="%s" src="%s" style="margin-top: 10px;" />', $tab, esc_url( plugins_url( sprintf( 'assets/images/tab-%s.png', $context ), GRAVITYVIEW_FILE ) ) );
		$output = sprintf( '<h3>%s <strong><a href="%s">%s</a></strong></h3><p>%s</p>', $title, esc_url( $edit_link ), $action_text, $message );

		echo \GVCommon::generate_notice( $output . $image, 'gv-error error', 'edit_gravityview', $gravityview->view->ID );
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
