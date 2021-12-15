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
	 * Print entry approval notice.
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

		self::maybe_print_reserved_slugs_notice( $gravityview );

		self::maybe_print_configuration_notice( $gravityview );

		self::maybe_print_entry_approval_notice( $gravityview );
	}

	/**
	 * Print notice warning admins that "Show only approved" is enabled
	 *
	 * @since 2.9.5
	 *
	 * @param \GV\Template_Context $gravityview The $gravityview template object.
	 *
	 * @return void
	 */
	private static function maybe_print_entry_approval_notice( $gravityview ) {

		if ( $gravityview->entries && $gravityview->entries->count() ) {
			return;
		}

		if ( $gravityview->request->is_search() ) {
			return;
		}

		// "Show Only Approved" is not enabled.
		if ( ! $gravityview->view->settings->get( 'show_only_approved', 0 ) ) {
			return;
		}

		// If "Show all entries to administrators" is enabled, approval status isn't the issue.
		if ( $gravityview->view->settings->get( 'admin_show_all_statuses', 0 ) ) {
			return;
		}

		// Don't show when no entries are being displayed due to "Hide View data until search is performed".
		if ( $gravityview->view->settings->get( 'hide_until_searched', 0 ) ) {
			return;
		}

		$current_user  = wp_get_current_user();
		$user_meta_key = '_gv_dismissed_entry_approval_notice' . $gravityview->view->ID;

		if ( isset( $_GET['gv-dismiss'] ) && wp_verify_nonce( $_GET['gv-dismiss'], 'dismiss' ) ) {
			add_user_meta( $current_user->ID, $user_meta_key, 1 ); // Prevent user from seeing this again for this View
			return;
		}

		// The user has already dismissed the notice
		if ( get_user_meta( $current_user->ID, $user_meta_key, true ) ) {
			return;
		}

		$form = $gravityview->view->form;

		if ( ! $form ) {
			return;
		}

		$count = \GFAPI::count_entries( $gravityview->view->form->ID, array(
			'status'        => 'active',
			'field_filters' => array(
				array(
					'key'      => 'is_approved',
					'operator' => 'isnot',
					'value'    => \GravityView_Entry_Approval_Status::APPROVED,
				),
			),
		) );

		// There aren't any entries to show!
		if ( empty( $count ) ) {
			return;
		}

		$notice_title = _n(
			esc_html__( 'There is an unapproved entry that is not being shown.', 'gravityview' ),
			esc_html__( 'There are %s unapproved entries that are not being shown.', 'gravityview' ),
			$count
		);

		$float_dir = is_rtl() ? 'left' : 'right';
		$hide_link = sprintf( '<a href="%s" style="float: ' . $float_dir . '; font-size: 1rem" role="button">%s</a>', esc_url( wp_nonce_url( add_query_arg( array( 'notice' => 'no_entries_' . $gravityview->view->ID ) ), 'dismiss', 'gv-dismiss' ) ), esc_html__( 'Hide this notice', 'gravityview' ) );

		$message_strings = array(
			'<h3>' . sprintf( $notice_title, number_format_i18n( $count ) ) . $hide_link . '</h3>',
			esc_html__( 'The "Show only approved entries" setting is enabled, so only entries that have been approved are displayed.', 'gravityview' ),
			sprintf( '<a href="%s">%s</a>', 'https://docs.gravityview.co/article/490-entry-approval-gravity-forms', esc_html__( 'Learn about entry approval.', 'gravityview' ) ),
			"\n\n",
			sprintf( esc_html_x( '%sEdit the View settings%s or %sApprove entries%s', 'Replacements are HTML links', 'gravityview' ), '<a href="' . esc_url( get_edit_post_link( $gravityview->view->ID, false ) ) . '" style="font-weight: bold;">', '</a>', '<a href="' . esc_url( admin_url( 'admin.php?page=gf_entries&id=' . $gravityview->view->form->ID ) ) . '" style="font-weight: bold;">', '</a>' ),
			"\n\n",
			sprintf( '<img alt="%s" src="%s" style="padding: 10px 0; max-width: 550px;" />', esc_html__( 'Show only approved entries', 'gravityview' ), esc_url( plugins_url( 'assets/images/screenshots/entry-approval.png', GRAVITYVIEW_FILE ) ) ),
			"\n\n",
			esc_html__( 'You can only see this message because you are able to edit this View.', 'gravityview' ),
		);

		$notice = wpautop( implode( ' ', $message_strings ) );

		echo \GVCommon::generate_notice( $notice, 'warning', 'edit_gravityview', $gravityview->view->ID );
	}

	/**
	 * Check empty configuration.
	 *
	 * @since 2.10
	 *
	 * @param \GV\Template_Context $gravityview The $gravityview template object.
	 *
	 * @return void
	 */
	private static function maybe_print_configuration_notice( $gravityview ) {

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

		// If the zone has been configured, don't display notice.
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
	 * Print reserved slug warnings, if they exist.
	 *
	 * @since 2.9.5
	 *
	 * @param Template_Context $gravityview The $gravityview template object.
	 *
	 * @return void
	 */
	private static function maybe_print_reserved_slugs_notice( $gravityview ) {
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

		if ( ! in_array( strtolower( $wp->request ), $reserved_slugs, true ) ) {
			return;
		}

		gravityview()->log->error( '{slug} page URL is reserved.', array( 'slug' => $wp->request ) );

		$title   = esc_html__( 'GravityView will not work correctly on this page because of the URL Slug.', 'gravityview' );
		$message = __( 'Please <a href="%s">read this article</a> for more information.', 'gravityview' );
		$message .= ' ' . esc_html__( 'You can only see this message because you are able to edit this View.', 'gravityview' );

		$output = sprintf( '<h3>%s</h3><p>%s</p>', $title, sprintf( $message, 'https://docs.gravityview.co/article/659-reserved-urls' ) );

		echo \GVCommon::generate_notice( $output, 'gv-error error', 'edit_gravityview', $gravityview->view->ID );
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
