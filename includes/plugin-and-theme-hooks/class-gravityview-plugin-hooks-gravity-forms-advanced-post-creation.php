<?php
/**
 * Add Advanced Post Creation customizations.
 *
 * @file      class-gravityview-plugin-hooks-code-snippets.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2024, Katz Web Services, Inc.
 *
 * @since 2.20
 */

use GV\View;

/**
 * Add Gravity Forms Advanced Post Creation compatibility.
 * @since 2.20
 * @link  https://www.gravityforms.com/add-ons/advanced-post-creation/
 */
final class GravityView_Plugin_Hooks_Gravity_Forms_Advanced_Post_Creation extends GravityView_Plugin_and_Theme_Hooks {
	/**
	 * @inheritDoc
	 * @since 2.20
	 */
	protected $class_name = 'GF_Advanced_Post_Creation';

	/**
	 * Updates the connected post.
	 * @since 2.20
	 *
	 * @param array                         $form     Gravity Forms form array.
	 * @param string                        $entry_id Numeric ID of the entry that was updated.
	 * @param GravityView_Edit_Entry_Render $render   The entry renderer.
	 */
	public function update_post_on_entry_edit( array $form, string $entry_id, GravityView_Edit_Entry_Render $render ): void {
		if ( ! $form || ! $entry_id ) {
			return;
		}

		$apc = GF_Advanced_Post_Creation::get_instance();

		$created_posts = gform_get_meta( $entry_id, $apc->get_slug() . '_post_id' );

		if ( ! $created_posts ) {
			return;
		}

		$feeds = $apc->get_active_feeds( rgar( $form, 'id' ) );

		if ( ! $feeds ) {
			return;
		}

		// Map feeds on their id for easy access.
		$feeds = array_column( $feeds, null, 'id' );

		foreach ( $created_posts as $created_post ) {
			$feed_id = rgar( $created_post, 'feed_id' );
			$feed    = rgar( $feeds, $feed_id );
			if ( ! $feed ) {
				continue;
			}

			$apc->update_post( $created_post['post_id'], $feed, $render->entry, $form );
		}
	}

	/**
	 * Populates the entry's post_id from APC meta for Edit Entry permission checks.
	 *
	 * APC stores post IDs in entry meta as an array, not in $entry['post_id'].
	 * This method populates the post_id before Edit Entry renders to fix permission checks.
	 *
	 * @since TBD
	 *
	 * @param array $data {
	 *     @type array           $form  The form array.
	 *     @type array           $entry The entry array.
	 *     @type \GV\View|null   $view  The View object.
	 * }
	 *
	 * @return array The modified data array with entry post_id populated.
	 */
	public function populate_entry_post_id( $data ) {
		if ( ! empty( $data['entry']['post_id'] ) || empty( $data['entry']['id'] ) ) {
			return $data;
		}

		$apc = GF_Advanced_Post_Creation::get_instance();
		$apc_posts = gform_get_meta( $data['entry']['id'], $apc->get_slug() . '_post_id' );

		// If APC created posts, use the first post's ID for permission checks.
		if ( ! empty( $apc_posts ) && is_array( $apc_posts ) ) {
			$first_post = reset( $apc_posts );

			if ( ! empty( $first_post['post_id'] ) ) {
				$data['entry']['post_id'] = $first_post['post_id'];
			}
		}

		return $data;
	}

	/**
	 * Adds a notice if the form contains a feed for Advanced Post Creation.
	 * @since 2.20
	 *
	 * @param mixed      $_       unused template name.
	 * @param string     $context The context.
	 * @param int|string $view_id The view post ID.
	 * @param bool       $echo    Whether to print the HTML directly instead of returning.
	 *
	 * @return string|null The output.
	 */
	public function add_view_notification( $_, string $context, $view_id, bool $echo = false ): ?string {
		if ( 'edit' !== $context ) {
			return null;
		}

		$view = View::by_id( $view_id );
		if ( ! $view ) {
			return null;
		}

		$form = $view->form;
		if ( ! $form ) {
			return null;
		}

		$apc   = GF_Advanced_Post_Creation::get_instance();
		$feeds = $apc->get_active_feeds( $form->ID );

		if ( ! $feeds ) {
			return null;
		}

		$notification_html = <<<HTML
<div class="gv-grid-row">
	<div class="gv-grid-col-1-1">
		<div class="notice notice-warning inline">
			<p><strong>%s</strong></p>
			<p>%s</p>
		</div>
	</div>
</div>
HTML;

		$notification = sprintf(
			$notification_html,
			// translators: Do not translate [link] and [/link]; they are replaced with an anchor tag.
			esc_html__( 'Caution: [link]Advanced Post Creation[/link] is active for this form.', 'gk-gravityview' ),
			esc_html__( 'Editing entries in GravityView may also update a connected post.', 'gk-gravityview' )
		);

		$apc_feed_link = admin_url( sprintf( 'admin.php?page=gf_edit_forms&amp;view=settings&amp;subview=%s&amp;id=%d', $apc->get_slug(), $form->ID ) );

		$notification  = strtr( $notification, [
			'[link]'  => '<a style="font-size: inherit;" href="' . esc_url( $apc_feed_link ) . '" target="_blank">',
			'[/link]' =>  '<span class="screen-reader-text"> ' . esc_html__( '(This link opens in a new window.)', 'gk-gravityview' ) . '</span></a>',
		] );

		if ( $echo ) {
			echo $notification;
		}

		return $notification;
	}

	/**
	 * @inheritDoc
	 * @since 2.20
	 */
	protected function add_hooks(): void {
		parent::add_hooks();

		add_filter( 'gk/gravityview/edit-entry/init/data', [ $this, 'populate_entry_post_id' ], 5 );
		add_action( 'gravityview/edit_entry/after_update', [ $this, 'update_post_on_entry_edit' ], 10, 3 );
		add_action( 'gravityview_render_directory_active_areas', [ $this, 'add_view_notification' ], 5, 4 );
	}
}

new GravityView_Plugin_Hooks_Gravity_Forms_Advanced_Post_Creation;
