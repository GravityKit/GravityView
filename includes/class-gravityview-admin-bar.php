<?php

/**
 * Handle management of the Admin Bar links
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

	private function add_hooks() {
		add_action( 'add_admin_bar_menus', array( $this, 'remove_links' ), 80 );
		add_action( 'admin_bar_menu', array( $this, 'add_links' ), 85 );
	}

	/**
	 * Add helpful GV links to the menu bar, like Edit Entry on single entry page.
	 *
	 * @return void
	 */
	function add_links() {
		/** @var WP_Admin_Bar $wp_admin_bar */
		global $wp_admin_bar;

		$this->add_edit_entry_link();

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

		if ( GFCommon::current_user_can_any( 'gravityforms_edit_entries' ) && $this->gravityview_view->getSingleEntry() ) {

			$entry = $this->gravityview_view->getEntry();

			$wp_admin_bar->add_menu( array(
				'id' => 'edit-entry',
				'title' => __( 'Edit Entry', 'gravityview' ),
				'href' => esc_url_raw( admin_url( sprintf( 'admin.php?page=gf_entries&amp;screen_mode=edit&amp;view=entry&amp;id=%d&lid=%d', $entry['form_id'], $entry['id'] ) ) ),
			) );

		}

	}

	/**
	 * Remove "Edit Page" or "Edit View" links when on single entry pages
	 * @return void
	 */
	function remove_links() {

		// If we're on the single entry page, we don't want to cause confusion.
		if ( is_admin() || ( $this->gravityview_view->getSingleEntry() && ! $this->gravityview_view->isGravityviewPostType() ) ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
		}
	}
}

new GravityView_Admin_Bar;