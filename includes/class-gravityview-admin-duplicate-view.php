<?php

/**
 * Class GravityView_Admin_Duplicate_View
 *
 * Based on the {@link http://lopo.it/duplicate-post-plugin/ Duplicate Post plugin} by Enrico Battocchi - highly recommended!
 *
 * @package GravityView
 *
 * @since 1.6
 */
class GravityView_Admin_Duplicate_View {


	function __construct() {

		// Only run on Admin
		if ( !is_admin() ) {
			return;
		}

		// If the Duplicate Post plugin is active, don't run.
		if( defined('DUPLICATE_POST_CURRENT_VERSION') ) {
			return;
		}

		add_filter( 'post_row_actions', array( $this, 'make_duplicate_link_row' ), 10, 2 );

		/**
		 * Connect actions to functions
		 */
		add_action( 'admin_action_duplicate_view', array( $this, 'save_as_new_view' ) );
		add_action( 'admin_action_duplicate_view_as_draft', array( $this, 'save_as_new_view_draft' ) );

		// Using our action hooks to copy meta fields
		add_action( 'gv_duplicate_view', array( $this, 'copy_view_meta_info' ), 10, 2 );

		add_filter( 'gravityview_connected_form_links', array( $this, 'connected_form_links' ), 10, 2 );

	}

	/**
	 * Add Duplicate View link to the Data Source metabox
	 *
	 * @param array $links Array of link HTML to display in the Data Source metabox
	 * @param array $form Gravity Forms form array
	 *
	 * @return array If it's the All Views page, return unedited. Otherwise, add a link to create cloned draft of View
	 */
	function connected_form_links( $links = array(), $form = array() ) {
		global $post;

		// We only want to add Clone links to the Edit View metabox
		if( !$this->is_all_views_page() ) {

			$duplicate_links = $this->make_duplicate_link_row( array(), $post );

			$links[] = '<span>'.$duplicate_links['edit_as_new_draft'].'</span>';

		}

		return $links;

	}

	/**
	 * Is the current screen the All Views screen?
	 *
	 * @return bool
	 */
	function is_all_views_page() {
		global $pagenow;

		return $pagenow === 'edit.php';
	}

	/**
	 * Test if the user is allowed to copy Views
	 *
	 * @since 1.6
	 */
	function current_user_can_copy( $post ) {

		if( is_object( $post ) ) {
			$id = $post->ID;
		} else {
			$id = $post;
		}

		// Can't edit this current View
		return current_user_can( 'edit_posts', $id );

	}

	/**
	 * Create a duplicate from a View $post
	 *
	 * @since 1.6
	 */
	function create_duplicate( $post, $status = '' ) {

		// We only want to clone Views
		if ( $post->post_type !== 'gravityview' ) {
			return;
		}

		$new_view_author = wp_get_current_user();

		/**
		 * The default status for a new View. Return empty for the new View to inherit existing View status
		 *
		 * @param string|null If string, the status to set for the new View. If empty, use existing View status.
		 * @param WP_Post $post View being cloned
		 */
		$new_view_status = apply_filters('gravityview/duplicate-view/status', $status, $post );

		$new_view = array(
			'menu_order'     => $post->menu_order,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'post_author'    => $new_view_author->ID,
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_mime_type' => $post->post_mime_type,
			'post_parent'    => $post->post_parent,
			'post_password'  => $post->post_password,
			'post_status'    => ( empty( $new_view_status ) ) ? $post->post_status : $new_view_status,
			'post_title'     => $post->post_title,
			'post_type'      => $post->post_type,
		);

		/**
		 * When copying the View, should the date also be copied?
		 *
		 * @param boolean
		 * @param WP_Post $post View being cloned
		 */
		$copy_date = apply_filters('gravityview/duplicate-view/copy-date', false, $post );

		if ( $copy_date ) {
			$new_view['post_date']     = $new_post_date = $post->post_date;
			$new_view['post_date_gmt'] = get_gmt_from_date( $new_post_date );
		}

		/**
		 * Modify View configuration before creating the duplicated View.
		 *
		 * @param array $new_view Array of settings to be passed to wp_insert_post()
		 * @param WP_Post $post View being cloned
		 */
		$new_view = apply_filters('gravityview/duplicate-view/new-view', $new_view, $post );

		// Magic happens here.
		$new_view_id = wp_insert_post( $new_view );

		// If the copy is published or scheduled, we have to set a proper slug.
		if ( $new_view_status == 'publish' || $new_view_status == 'future' ) {

			$view_name = wp_unique_post_slug( $post->post_name, $new_view_id, $new_view_status, $post->post_type, $post->post_parent );

			$new_view_name_array = array(
				'ID' => $new_view_id,
				'post_name' => $view_name,
			);

			// Update the post into the database
			wp_update_post( $new_view_name_array );
		}

		do_action( 'gv_duplicate_view', $new_view_id, $post );

		delete_post_meta( $new_view_id, '_dp_original' );

		add_post_meta( $new_view_id, '_dp_original', $post->ID, false );

		return $new_view_id;
	}

	/**
	 * Copy the meta information of a post to another View
	 *
	 * @since 1.6
	 */
	function copy_view_meta_info( $new_id, $post ) {

		$post_meta_keys = get_post_custom_keys( $post->ID );

		if ( empty( $post_meta_keys ) ) {
			return;
		}

		foreach ( $post_meta_keys as $meta_key ) {

			$meta_values = get_post_custom_values( $meta_key, $post->ID );

			foreach ( $meta_values as $meta_value ) {

				$meta_value = maybe_unserialize( $meta_value );

				add_post_meta( $new_id, $meta_key, $meta_value );
			}
		}
	}

	/**
	 * Add the link to action list for post_row_actions
	 *
	 * @since 1.6
	 */
	function make_duplicate_link_row( $actions, $post ) {
		global $pagenow;

		// Only process on GravityView Views
		if( get_post_type( $post ) !== 'gravityview' ) {
			return $actions;
		}

		if ( $this->current_user_can_copy( $post ) ) {

			$clone_link = $this->get_clone_view_link( $post->ID, 'display', false );
			$clone_text = __( 'Clone', 'gravityview' );
			$clone_title = __( 'Clone this View', 'gravityview' );

			$actions['clone'] = gravityview_get_link( $clone_link, $clone_text, 'title='.$clone_title );

			$clone_draft_link = $this->get_clone_view_link( $post->ID );
			$clone_draft_text = $this->is_all_views_page() ? __( 'New Draft', 'gravityview' ) : __( 'Clone View', 'gravityview' );
			$clone_draft_title = __( 'Copy as a new draft View', 'gravityview' );

			$actions['edit_as_new_draft'] = gravityview_get_link( $clone_draft_link, $clone_draft_text, 'title='.$clone_draft_title );
		}

		return $actions;
	}

	/**
	 * Retrieve duplicate post link for post.
	 *
	 * @since 1.6
	 *
	 * @param int $id Optional. Post ID.
	 * @param string $context Optional, default to display. How to write the '&', defaults to '&amp;'.
	 * @param string $draft Optional, default to true
	 * @return string
	 */
	function get_clone_view_link( $id = 0, $context = 'display', $draft = true ) {

		// Make sure they have permission
		if ( false === $this->current_user_can_copy( $id ) ) {
			return;
		}

		// Verify the View exists
		if ( !$view = get_post( $id ) ) {
			return;
		}

		$action_name = $draft ? "duplicate_view_as_draft" : "duplicate_view";

		if ( 'display' == $context ) {
			$action = '?action=' . $action_name . '&amp;post=' . $view->ID;
		} else {
			$action = '?action='.$action_name.'&post='.$view->ID;
		}

		//
		$post_type_object = get_post_type_object( $view->post_type );

		if ( !$post_type_object ) {
			return;
		}

		return apply_filters( 'gravityview/duplicate-view/get_clone_view_link', admin_url( "admin.php". $action ), $view->ID, $context );
	}


	/**
	 * This function calls the creation of a new copy of the selected post (as a draft)
	 * then redirects to the edit post screen
	 *
	 * @since 1.6
	 */
	function save_as_new_view_draft() {
		$this->save_as_new_view( 'draft' );
	}

	/**
	 * This function calls the creation of a new copy of the selected post (by default preserving the original publish status)
	 * then redirects to the post list
	 *
	 * @since 1.6
	 */
	function save_as_new_view( $status = '' ) {

		if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) ) ) {
			wp_die( __( 'No post to duplicate has been supplied!', 'gravityview' ) );
		}

		// Get the original post
		$id   = ( isset( $_GET['post'] ) ? $_GET['post'] : $_POST['post'] );
		$post = get_post( $id );

		// Copy the post and insert it
		if ( isset( $post ) && $post != NULL ) {

			$new_id = $this->create_duplicate( $post, $status );

			if ( $status == '' ) {
				// Redirect to the post list screen
				wp_redirect( admin_url( 'edit.php?post_type=' . $post->post_type ) );
			} else {
				// Redirect to the edit screen for the new draft post
				wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
			}
			exit;

		} else {

			wp_die( sprintf( esc_attr__( 'Copy creation failed, could not find original View with ID #%d', 'gravityview' ), $id ) );

		}
	}

}


new GravityView_Admin_Duplicate_View;