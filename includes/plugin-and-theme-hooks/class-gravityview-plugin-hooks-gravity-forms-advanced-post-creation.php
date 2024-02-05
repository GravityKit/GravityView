<?php

use GV\View;

/**
 * Add Gravity Forms Advanced Post Creation compatibility.
 * @since $ver$
 * @link  https://www.gravityforms.com/add-ons/advanced-post-creation/
 */
final class GravityView_Plugin_Hooks_Gravity_Forms_Advanced_Post_Creation extends GravityView_Plugin_and_Theme_Hooks {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected $class_name = 'GF_Advanced_Post_Creation';

	/**
	 * Updates the connected post.
	 * @since $ver$
	 *
	 * @param array                         $form     Gravity Forms form array.
	 * @param string                        $entry_id Numeric ID of the entry that was updated.
	 * @param GravityView_Edit_Entry_Render $render   The entry renderer.
	 */
	public function update_post_on_entry_edit( array $form, string $entry_id, GravityView_Edit_Entry_Render $render ): void {
		if ( ! $form || ! $entry_id ) {
			return;
		}

		// Todo: disable if setting is not activated in Foudation.

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
	 * Adds a notice if the form contains a feed for Advanced Post Creation.
	 * @since $ver$
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

		$form  = $view->form;
		$apc   = GF_Advanced_Post_Creation::get_instance();
		$feeds = $apc->get_active_feeds( $form->ID );

		if ( ! $feeds ) {
			return null;
		}

		$notification_html = <<<HTML
<div class="gv-grid-col-1-1">
	<div class="notice notice-warning inline">
		<p><strong>%s</strong></p>
		<p>%s</p>
	</div>
</div>
HTML;

		$notification = sprintf(
			$notification_html,
			esc_html__( 'Caution: Advanced Post Creation is active for this form', 'gk-gravityview' ),
			__( 'Editing one of these entries might update a connected post as well. ', 'gk-gravityview' )
		);

		if ( $echo ) {
			echo $notification;
		}

		return $notification;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function add_hooks(): void {
		parent::add_hooks();

		add_action( 'gravityview/edit_entry/after_update', [ $this, 'update_post_on_entry_edit' ], 10, 3 );
		add_action( 'gravityview_render_directory_active_areas', [ $this, 'add_view_notification' ], 5, 4 );
	}
}

new GravityView_Plugin_Hooks_Gravity_Forms_Advanced_Post_Creation;
