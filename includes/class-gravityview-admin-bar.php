<?php

/**
 * Handle management of the Admin Bar links
 *
 * @since 1.13
 */
class GravityView_Admin_Bar {

	/**
	 * @var GravityView_frontend|null
	 */
	var $gravityview_view = null;

	function __construct() {

		$this->gravityview_view = GravityView_frontend::getInstance();

		$this->add_hooks();
	}

	/**
	 * @since 1.13
	 */
	private function add_hooks() {
		add_action( 'add_admin_bar_menus', array( $this, 'remove_links' ), 80 );
		add_action( 'admin_bar_menu', array( $this, 'add_links' ), 85 );
		add_action( 'wp_after_admin_bar_render', array( $this, 'add_floaty_icon' ) );
	}

	/**
	 * Add helpful GV links to the menu bar, like Edit Entry on single entry page.
	 *
	 * @since 1.13
	 * @return void
	 */
	function add_links() {
		/** @var WP_Admin_Bar $wp_admin_bar */
		global $wp_admin_bar;

		if ( ! GVCommon::has_cap( array( 'edit_gravityviews', 'gravityview_edit_entry', 'gravityforms_edit_forms' ) ) ) {
			return;
		}

		$view_data = GravityView_View_Data::getInstance()->get_views();

		// Dashboard Views.
		$view = false;
		if ( is_admin() ) {
			$view = gravityview()->request->is_view();
		}

		if ( empty( $view_data ) && empty( $view ) ) {
			return;
		}

		$wp_admin_bar->add_menu(
			array(
				'id'    => 'gravityview',
				'title' => __( 'GravityView', 'gk-gravityview' ),
				'href'  => admin_url( 'edit.php?post_type=gravityview&page=gravityview_settings' ),
			)
		);

		$this->add_edit_view_and_form_link();

		$this->add_edit_entry_link();
	}

	/**
	 * Add the Floaty icon to the toolbar without loading the whole icon font
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	public function add_floaty_icon() {
		?>
		<style>
			#wp-admin-bar-gravityview > .ab-item:before {
				content: '';
				<?php // Base64-encode so that it works in Firefox as well, even though https://css-tricks.com/probably-dont-base64-svg/ ?>
				background: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='20.4 27.05 20 21'%3E%3Cpath fill='none' d='M25.8 6.7c0 .7.1 1.3.4 1.9-.1-.3-.1-.6-.1-1 0-3.1 3.3-6.6 7.8-5.2-.9-.5-1.8-.8-2.9-.8-2.9-.1-5.2 2.2-5.2 5.1z'/%3E%3Cpath fill='%23F0F5FA' d='M36.23 33.77c-1.45-1.48-3.37-2.3-5.44-2.43V30.3c.6-.13 1.1-.7 1.1-1.44 0-.83-.7-1.5-1.5-1.5s-1.5.66-1.5 1.5c0 .7.43 1.3 1.1 1.48v1.03c-2.07.08-4 .92-5.47 2.37-1.57 1.5-2.43 3.57-2.43 5.75 0 .1 0 .3.04.6 0 .1.05.3.07.5.1.6.25 1.1.44 1.7l.2.5c.03.1.06.2.12.3l.2.4c.37.63.8 1.22 1.3 1.72 1.55 1.55 3.6 2.4 5.8 2.4h.1c2.15 0 4.2-.85 5.7-2.36.55-.53 1-1.1 1.36-1.7.1-.16.17-.3.23-.47l.14-.3.2-.5c.2-.57.33-1.15.42-1.74l.08-.5c.04-.2.04-.4.04-.6.02-2.16-.8-4.23-2.38-5.8zM29.2 29.2c0 .08 0 .16.03.28-.06-.17-.1-.34-.1-.53 0-.8.63-1.43 1.44-1.43.3 0 .58.1.8.25-1.25-.4-2.17.56-2.17 1.43zm1.26 2.8c3.6.04 6.6 2.58 7.3 5.98-.94-2.03-3.84-3.5-7.33-3.54-3.46 0-6.4 1.45-7.36 3.46.75-3.38 3.76-5.9 7.4-5.9zM29 43.66c-3.06-.42-5.35-2.18-5.35-4.27 0-2.4 3.04-4.4 6.78-4.3h1.03c-2.18 2.1-2.6 5.4-2.45 8.5zm8.32-1.18c-1.3 2.65-3.96 4.33-6.9 4.33-2.92 0-5.6-1.6-6.9-4.3-.3-.6-.45-1.2-.54-1.9.84 2.16 3.82 3.75 7.42 3.78 3.6 0 6.6-1.57 7.45-3.7-.1.68-.28 1.3-.53 1.88z' opacity='.6'/%3E%3C/svg%3E") 50% 50% no-repeat !important;
				top: 2px;
				width: 20px;
				height: 20px;
				display: inline-block;
			}
		</style>
		<?php
	}

	/**
	 * Add Edit Entry links when on a single entry
	 *
	 * @since 1.13
	 * @return void
	 */
	function add_edit_entry_link() {
		/** @var WP_Admin_Bar $wp_admin_bar */
		global $wp_admin_bar;

		$entry = gravityview()->request->is_entry();

		if ( $entry && GVCommon::has_cap( array( 'gravityforms_edit_entries', 'gravityview_edit_entries' ), $entry->ID ) ) {

			$wp_admin_bar->add_menu(
				array(
					'id'     => 'edit-entry',
					'parent' => 'gravityview',
					'title'  => __( 'Edit Entry', 'gk-gravityview' ),
					'meta'   => array(
						'title' => sprintf( __( 'Edit Entry %s', 'gk-gravityview' ), $entry->get_slug() ),
					),
					'href'   => esc_url_raw( admin_url( sprintf( 'admin.php?page=gf_entries&amp;screen_mode=edit&amp;view=entry&amp;id=%d&lid=%d', $entry['form_id'], $entry['id'] ) ) ),
				)
			);

		}
	}

	/**
	 * Add Edit View link when in embedded View
	 *
	 * @since 1.13
	 * @return void
	 */
	function add_edit_view_and_form_link() {
		/** @var WP_Admin_Bar $wp_admin_bar */
		global $wp_admin_bar, $post;

		if ( ! GVCommon::has_cap(
			[ 'edit_gravityviews', 'edit_gravityview', 'gravityforms_edit_forms' ],
			$post->ID
		) ) {
			return;
		}

		$view_data = GravityView_View_Data::getInstance();
		$views     = $view_data->get_views();

		// Dashboard Views.
		if( is_admin() ) {
			$view = gravityview()->request->is_view();
			$views[] = $view;
		}

		// If there is a View embed, show Edit View link.
		if ( ! empty( $views ) ) {

			$added_forms = array();
			$added_views = array();

			foreach ( $views as $view ) {
				if ( ! $view ) {
					continue;
				}

				$view    = \GV\View::by_id( $view['id'] );
				$view_id = $view->ID;
				$form_id = $view->form ? $view->form->ID : null;

				$edit_view_title = esc_html__( 'Edit View', 'gk-gravityview' );
				$edit_form_title = esc_html__( 'Edit Form', 'gk-gravityview' );

				if ( sizeof( $views ) > 1 ) {
					$edit_view_title = sprintf( esc_html_x( 'Edit View #%d', 'Edit View with the ID of %d', 'gk-gravityview' ), $view_id );
					$edit_form_title = sprintf( esc_html_x( 'Edit Form #%d', 'Edit Form with the ID of %d', 'gk-gravityview' ), $form_id );
				}

				if ( GVCommon::has_cap( 'edit_gravityview', $view_id ) && ! in_array( $view_id, $added_views ) ) {

					$added_views[] = $view_id;

					$wp_admin_bar->add_menu(
						array(
							'id'     => 'edit-view-' . $view_id,
							'parent' => 'gravityview',
							'title'  => $edit_view_title,
							'href'   => esc_url_raw( admin_url( sprintf( 'post.php?post=%d&action=edit', $view_id ) ) ),
						)
					);
				}

				if ( ! empty( $form_id ) && GVCommon::has_cap( array( 'gravityforms_edit_forms' ), $form_id ) && ! in_array( $form_id, $added_forms ) ) {

					$added_forms[] = $form_id;

					$wp_admin_bar->add_menu(
						array(
							'id'     => 'edit-form-' . $form_id,
							'parent' => 'gravityview',
							'title'  => $edit_form_title,
							'href'   => esc_url_raw( admin_url( sprintf( 'admin.php?page=gf_edit_forms&id=%d', $form_id ) ) ),
						)
					);
				}
			}
		}
	}

	/**
	 * Remove "Edit Page" or "Edit View" links when on single entry.
	 *
	 * @since 1.17 Also remove when on GravityView post type; the new GravityView menu will be the one-stop shop.
	 * @since 1.13
	 *
	 * @return void
	 */
	function remove_links() {

		// If we're on the single entry page, we don't want to cause confusion.
		if ( $this->gravityview_view->getSingleEntry() || $this->gravityview_view->isGravityviewPostType() ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
		}
	}
}

new GravityView_Admin_Bar();
