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
	 * Adds post fields to available fields when Advanced Post Creation feeds are active.
	 * @since 2.20
	 *
	 * @param array        $fields The available fields.
	 * @param string|array $form   Form ID or form array.
	 * @param string       $zone   Either 'single', 'directory', 'header', 'footer'.
	 *
	 * @return array Modified fields array.
	 */
	public function add_post_fields_to_available_fields( array $fields, $form, string $zone ): array {
		$form_id = is_array( $form ) ? $form['id'] : $form;

		if ( ! $form_id ) {
			return $fields;
		}

		$apc   = GF_Advanced_Post_Creation::get_instance();
		$feeds = $apc->get_active_feeds( $form_id );

		if ( ! $feeds ) {
			return $fields;
		}

		// Only add fields for directory and single contexts.
		if ( ! in_array( $zone, [ 'directory', 'single' ], true ) ) {
			return $fields;
		}

		// Get all post fields that support dynamic data.
		$post_field_types = self::get_post_field_types();

		foreach ( $post_field_types as $field_type ) {
			$gv_field = GravityView_Fields::get_instance( $field_type );

			if ( ! $gv_field ) {
				continue;
			}

			// Add field if it doesn't already exist.
			if ( ! isset( $fields[ $field_type ] ) ) {
				$field_array = $gv_field->as_array();
				$fields[ $field_type ] = reset( $field_array );
			}
		}

		return $fields;
	}

	/**
	 * Returns a list of post field types that support dynamic data.
	 *
	 * @since TODO
	 *
	 * @return array List of field types that support dynamic data.
	 */
	static private function get_post_field_types(): array {
		return [
			'post_title',
			'post_content',
			'post_excerpt',
			'post_image',
			'post_category',
			'post_tags',
			'post_id',
		];
	}

	/**
	 * Adds a field setting to select which APC feed to use for dynamic data.
	 * @since 2.20
	 *
	 * @param array  $field_options Existing field options.
	 * @param string $template_id   Table slug.
	 * @param float  $field_id      GF Field ID.
	 * @param string $context       Context: 'single' or 'directory'.
	 * @param string $input_type    Field type.
	 * @param int    $form_id       The form ID.
	 *
	 * @return array Modified field options.
	 */
	public function add_feed_selector_to_post_fields( array $field_options, string $template_id, $field_id, string $context, string $input_type, int $form_id ): array {
		$post_field_types = self::get_post_field_types();

		if ( ! in_array( $input_type, $post_field_types, true ) ) {
			return $field_options;
		}

		// Skip if dynamic_data option is not available.
		if ( ! isset( $field_options['dynamic_data'] ) ) {
			return $field_options;
		}

		if ( ! $form_id ) {
			return $field_options;
		}

		$apc   = GF_Advanced_Post_Creation::get_instance();
		$feeds = $apc->get_active_feeds( $form_id );

		if ( ! $feeds ) {
			return $field_options;
		}



		// Build choices array for the dropdown.
		$choices = [];

		// Add "Any" option as default.
		$choices[''] = __( 'Any', 'gk-gravityview' );

		foreach ( $feeds as $feed ) {

			// translators: %s is the feed name, %d is the feed ID.
			$label = sprintf( esc_html__( '%s (#%d)', 'gk-gravityview' ), $feed['meta']['feedName'], $feed['id'] );

			$choices[ $feed['id'] ] = esc_html( $label );
		}

		// Add feed selector field option.
		$field_options['apc_feed_id'] = [
			'type'     => 'select',
			'label'    => __( 'Post Creation Feed', 'gk-gravityview' ),
			'desc'     => __( 'Select which Advanced Post Creation feed to use for displaying post data.', 'gk-gravityview' ),
			'options'  => $choices,
			'value'    => '',
			'priority' => 1101, // Place right after dynamic_data option.
			'group'    => 'display',
			'requires' => 'dynamic_data', // Only show when dynamic data is enabled.
		];

		return $field_options;
	}

	/**
	 * Filters the post ID to use the one from a specific APC feed if configured.
	 * @since 2.20
	 *
	 * @param int|null $post_id  The post ID found by default method.
	 * @param array    $entry    The entry array.
	 * @param int|null $feed_id  The feed ID requested (currently unused by core).
	 *
	 * @return int|null Modified post ID.
	 */
	public function filter_post_id_for_apc_feed( $post_id, array $entry, $feed_id = null ): ?int {

		// If we already have a post ID from standard GF post fields, don't override.
		if ( ! empty( $post_id ) ) {
			return $post_id;
		}

		if ( empty( $entry['id'] ) ) {
			return $post_id;
		}

		// Get APC posts for this entry.
		$apc_posts = gform_get_meta( $entry['id'], 'gravityformsadvancedpostcreation_post_id' );

		if ( empty( $apc_posts ) ) {
			return $post_id;
		}

		if ( $feed_id ) {
			$apc_posts = array_filter( $apc_posts, function( $post ) use ( $feed_id ) {
				return (int) $post['feed_id'] === (int) $feed_id;
			} );
		}

		$post_id = reset( $apc_posts )['post_id'];

		return $post_id;
	}

	/**
	 * @inheritDoc
	 * @since 2.20
	 */
	protected function add_hooks(): void {
		parent::add_hooks();

		add_action( 'gravityview/edit_entry/after_update', [ $this, 'update_post_on_entry_edit' ], 10, 3 );
		add_action( 'gravityview_render_directory_active_areas', [ $this, 'add_view_notification' ], 5, 4 );
		add_filter( 'gravityview/admin/available_fields', [ $this, 'add_post_fields_to_available_fields' ], 10, 3 );
		add_filter( 'gk/gravityview/common/get-post-id-from-entry', [ $this, 'filter_post_id_for_apc_feed' ], 10, 3 );

		// Add feed selector to all post field types.
		$post_field_types = self::get_post_field_types();

		foreach ( $post_field_types as $field_type ) {
			add_filter( sprintf( 'gravityview_template_%s_options', $field_type ), [ $this, 'add_feed_selector_to_post_fields' ], 10, 6 );
		}
	}
}

new GravityView_Plugin_Hooks_Gravity_Forms_Advanced_Post_Creation;
