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

		self::disable_show_only_approved_entries( $gravityview );

		self::maybe_print_reserved_slugs_notice( $gravityview );

		self::maybe_print_configuration_notice( $gravityview );

		self::maybe_print_entry_approval_notice( $gravityview );
	}


	/**
	 * Disable the "Show only approved entries" setting, hence displaying all entries on the View
	 *
	 * @since 2.14.3
	 *
	 * @param \GV\Template_Context $gravityview The $gravityview template object.
	 *
	 * @return void
	 */
	private static function disable_show_only_approved_entries( $gravityview ) {

		if ( ! isset( $_GET['disable_setting'] ) || ! wp_verify_nonce( $_GET['gv-setting'], 'setting' ) ) {
			return;
		}

		$settings = $gravityview->view->settings->all();

		$settings['show_only_approved'] = 0;

		$updated = update_post_meta( $gravityview->view->ID, '_gravityview_template_settings', $settings );

		if ( ! $updated ) {
			gravityview()->log->error( 'Could not update View settings => Show only approved' );
			return;
		}

		$redirect_url = home_url( remove_query_arg( array( 'disable_setting', 'gv-setting' ) ) );

		if ( wp_safe_redirect( $redirect_url ) ) {
			exit();
		}
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

		$current_user = wp_get_current_user();

		$user_meta_key = '_gv_dismissed_entry_approval_notice_' . $gravityview->view->ID;

		$dismiss_nonce_name   = 'gv-dismiss';
		$dismiss_nonce_action = 'gv-dismiss-no-entries-' . $gravityview->view->ID;

		if ( isset( $_GET[ $dismiss_nonce_name ] ) && wp_verify_nonce( $_GET[ $dismiss_nonce_name ], $dismiss_nonce_action ) ) {
			add_user_meta( $current_user->ID, $user_meta_key, 1 ); // Prevent user from seeing this again for this View
			return;
		}

		// The user has already dismissed the notice
		if ( get_user_meta( $current_user->ID, $user_meta_key, true ) ) {
			return;
		}

		// No form is attached to this View for some reason; there are no entries to display.
		if ( empty( $gravityview->view->form ) ) {
			return;
		}

		$count = \GFAPI::count_entries(
			$gravityview->view->form->ID,
			array(
				'status'        => 'active',
				'field_filters' => array(
					array(
						'key'      => 'is_approved',
						'operator' => 'isnot',
						'value'    => \GravityView_Entry_Approval_Status::APPROVED,
					),
				),
			)
		);

		// There aren't any entries to show!
		if ( empty( $count ) ) {
			return;
		}

		$message_template = <<<EOD
<style>
#{dom_id}-hide-notice {
	float: {float_dir};
	font-size: 1rem;
	font-weight: normal;
}
#{dom_id} div {
	margin: 1em 0;
}
#{dom_id} hr {
	border: none;
	border-bottom: 1px solid #ddd;
	margin: 0 0 10px;
}
#{dom_id} .gv-notice-message {
 margin: 1em 0;
 padding: 0;
 font-size: 1.2rem;
 font-weight: normal;
}
#{dom_id} span.gv-notice-description {
	display: block;
	font-weight: normal;
	font-style: italic;
}
#{dom_id} .gv-notice-admin-message {
	display: block;
	text-align:center;
	clear: both;
}
#{dom_id} img {
	display: block;
	margin: 10px 20px;
	max-width: 550px;
	float: {float_dir};
}
#{dom_id} .dashicons-no-alt {
	font-size: 1.2em;
	height: 1.2em;
	width: 1em;
}
#{dom_id} .dashicons-external {
	font-size: .8em;
	height: .8em;
	width: .8em;
	line-height: .8em;
}
</style>
<div id="{dom_id}">
	<h3>{notice_title}<span id="{dom_id}-hide-notice"><a href="{hide_notice_link}" role="button">{hide_notice} <span class="dashicons dashicons-no-alt"></span></span></a></h3>

	<p class="gv-notice-message">{screenshot} {message} <a href="{learn_more_link}" rel="external" target="_blank">{learn_more} <span class="dashicons dashicons-external" title="{title_new_window}"></span></a></p>

	<hr />

	<div><a href="{disable_setting_link}">{disable_setting}</a> <span class="gv-notice-description">{disable_setting_description}</span></div>
	<div><a href="{approve_entries_link}">{approve_entries}</a> <span class="gv-notice-description">{approve_entries_description}</span></div>

	<p class="gv-notice-admin-message"><em>{admin_message}</em></p>
</div>
EOD;

		$notice_title = _n(
			esc_html__( 'There is an unapproved entry that is not being shown.', 'gk-gravityview' ),
			sprintf( esc_html__( 'There are %s unapproved entries that are not being shown.', 'gk-gravityview' ), number_format_i18n( $count ) ),
			$count
		);

		$float_dir = is_rtl() ? 'left' : 'right';

		$dismiss_notice_link = wp_nonce_url( add_query_arg( array() ), $dismiss_nonce_action, $dismiss_nonce_name );

		$disable_setting_link = wp_nonce_url(
			add_query_arg(
				array(
					'disable_setting' => 'show_only_approved_' . $gravityview->view->ID,
				)
			),
			'setting',
			'gv-setting'
		);

		$placeholders = array(
			'{dom_id}'                      => sprintf( 'gv-notice-approve-entries-%d', $gravityview->view->ID ),
			'{float_dir}'                   => $float_dir,
			'{notice_title}'                => esc_html( $notice_title ),
			'{title_new_window}'            => esc_attr__( 'This link opens in a new window.', 'gk-gravityview' ),
			'{hide_notice}'                 => esc_html__( 'Hide this notice', 'gk-gravityview' ),
			'{hide_notice_link}'            => esc_url( $dismiss_notice_link ),
			'{message}'                     => esc_html( wptexturize( __( 'The "Show only approved entries" setting is enabled, so only entries that have been approved are displayed.', 'gk-gravityview' ) ) ),
			'{learn_more}'                  => esc_html__( 'Learn about entry approval.', 'gk-gravityview' ),
			'{learn_more_link}'             => 'https://docs.gravitykit.com/article/490-entry-approval-gravity-forms',
			'{disable_setting}'             => esc_html( wptexturize( __( 'Disable the "Show only approved entries" setting for this View', 'gk-gravityview' ) ) ),
			'{disable_setting_description}' => esc_html( wptexturize( __( 'Click to immediately disable the "Show only approved entries" setting. All entry statuses will be shown.', 'gk-gravityview' ) ) ),
			'{disable_setting_link}'        => esc_url( $disable_setting_link ),
			'{approve_entries}'             => esc_html__( 'Manage entry approval', 'gk-gravityview' ),
			'{approve_entries_description}' => esc_html__( 'Go to the Gravity Forms entries screen to moderate entry approval.', 'gk-gravityview' ),
			'{approve_entries_link}'        => esc_url( admin_url( 'admin.php?page=gf_entries&id=' . $gravityview->view->form->ID ) ),
			'{screenshot}'                  => sprintf( '<img alt="%s" src="%s" />', esc_attr__( 'Show only approved entries', 'gk-gravityview' ), esc_url( plugins_url( 'assets/images/screenshots/entry-approval.png', GRAVITYVIEW_FILE ) ) ),
			'{admin_message}'               => sprintf( esc_html__( 'Note: %s', 'gk-gravityview' ), esc_html__( 'You can only see this message because you are able to edit this View.', 'gk-gravityview' ) ),
		);

		$notice = strtr( $message_template, $placeholders );

		// Needed for the external link and close icons
		wp_print_styles( 'dashicons' );

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
				$tab     = esc_html__( 'Edit Entry', 'gk-gravityview' );
				$context = 'edit';
				break;
			case ( $entry = $gravityview->request->is_entry( $gravityview->view->form ? $gravityview->view->form->ID : 0 ) ):
				// When the entry is not found, we're probably inside a shortcode.
				if ( ! $gravityview->entry ) {
					return;
				}

				// Sanity check. Should be the same entry!
				if ( $gravityview->entry->ID !== $entry->ID ) {
					return;
				}

				$tab     = esc_html__( 'Single Entry', 'gk-gravityview' );
				$context = 'single';
				break;
			default:
				$tab     = esc_html__( 'Multiple Entries', 'gk-gravityview' );
				$context = 'directory';
				break;
		}

		$cls  = $gravityview->template;
		$slug = property_exists( $cls, '_configuration_slug' ) ? $cls::$_configuration_slug : $cls::$slug;

		// If the zone has been configured, don't display notice.
		if ( $gravityview->fields->by_position( sprintf( '%s_%s-*', $context, $slug ) )->by_visible( $gravityview->view )->count() ) {
			return;
		}

		/**
		 * Includes a way to disable the configuration notice.
		 *
		 * @since 2.17.8
		 *
		 * @param bool                 $should_display Whether to display the notice. Default: true.
		 * @param \GV\Template_Context $gravityview    The $gravityview template object.
		 * @param string               $context        The context of the notice. Possible values: `directory`, `single`, `edit`.
		 */
		$should_display = apply_filters( 'gk/gravityview/renderer/should-display-configuration-notice', true, $gravityview, $context );

		if ( ! $should_display ) {
			return;
		}

		$title       = sprintf( esc_html_x( 'The %s layout has not been configured.', 'Displayed when a View is not configured. %s is replaced by the tab label', 'gk-gravityview' ), $tab );
		$edit_link   = admin_url( sprintf( 'post.php?post=%d&action=edit#%s-view', $gravityview->view->ID, $context ) );
		$action_text = sprintf( esc_html__( 'Add fields to %s', 'gk-gravityview' ), $tab );
		$message     = esc_html__( 'You can only see this message because you are able to edit this View.', 'gk-gravityview' );

		$image  = sprintf( '<img alt="%s" src="%s" style="margin-top: 10px;" />', $tab, esc_url( plugins_url( sprintf( 'assets/images/tab-%s.png', $context ), GRAVITYVIEW_FILE ) ) );
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

		foreach ( $post_types as $post_type ) {
			$post_type_rewrite = get_post_type_object( $post_type )->rewrite;

			if ( $slug = \GV\Utils::get( $post_type_rewrite, 'slug' ) ) {
				$reserved_slugs[] = $slug;
			}
		}

		unset( $post_types, $post_type_rewrite );

		/**
		 * Modify the reserved embed slugs that trigger a warning.
		 *
		 * @since 2.5
		 *
		 * @param array                $reserved_slugs An array of strings, reserved slugs.
		 * @param \GV\Template_Context $gravityview    The context.
		 */
		$reserved_slugs = apply_filters( 'gravityview/rewrite/reserved_slugs', $reserved_slugs, $gravityview );

		$reserved_slugs = array_map( 'strtolower', $reserved_slugs );

		if ( ! in_array( strtolower( $wp->request ), $reserved_slugs, true ) ) {
			return;
		}

		gravityview()->log->error( '{slug} page URL is reserved.', array( 'slug' => $wp->request ) );

		$title    = esc_html__( 'GravityView will not work correctly on this page because of the URL Slug.', 'gk-gravityview' );
		$message  = __( 'Please <a href="%s">read this article</a> for more information.', 'gk-gravityview' );
		$message .= ' ' . esc_html__( 'You can only see this message because you are able to edit this View.', 'gk-gravityview' );

		$output = sprintf( '<h3>%s</h3><p>%s</p>', $title, sprintf( $message, 'https://docs.gravitykit.com/article/659-reserved-urls' ) );

		echo \GVCommon::generate_notice( $output, 'gv-error error', 'edit_gravityview', $gravityview->view->ID );
	}

	/**
	 * Warn about legacy template being used.
	 *
	 * Generate a callback that shows which legacy template was at fault.
	 * Used in gravityview_before.
	 *
	 * @param \GV\View $view The view we're looking at.
	 * @param string   $path The path of the offending template.
	 *
	 * @return \Callable A closure used in the filter.
	 */
	public function legacy_template_warning( $view, $path ) {
		return function () use ( $view, $path ) {
			// Do not panic for now...
		};
	}
}
