<?php
/**
 * @global \WP_Post $post The current post
 * @global string $directory_entries_template GravityView_Template::template_id value for directory entries section.
 * @global string $single_entry_template GravityView_Template::template_id value for single entry section.
 */

use GravityKit\GravityView\Foundation\Helpers\Core;

$templates = array_filter(
	gravityview_get_registered_templates(),
	static function ( array $template ) {
		return 'custom' === rgar( $template, 'type' );
	}
);

function is_active( array $template ): bool {
	$placeholder = ! empty( $template['buy_source'] );
	$is_included = ! empty( $template['included'] );

	return ! $placeholder && ! $is_included;
}

uasort(
	$templates,
	static function ( array $a, array $b ) {
		return is_active( $b ) <=> is_active( $a );
	}
);

/**
 * Returns the HTML for the templates options needed for the view dropdown.
 *
 * @since 2.24
 *
 * @param array       $templates         The templates to render.
 * @param string|null $selected_template The current selected template.
 *
 * @return string The rendered options.
 */
function render_template_options( array $templates, ?string $selected_template ): string {
	$html = sprintf( '<option value="">%s</option>', esc_html__( 'Select a type', 'gk-gravityview' ) );

	foreach ( $templates as $template_id => $template ) {
		$extra = [
			sprintf( 'data-template-id="%s"', esc_attr( $template['template_id'] ?? '' ) ),
		];

		if ( $template_id === $selected_template ) {
			$extra[] = 'selected="selected"';
		}

		if ( ! is_active( $template ) ) {
			$extra[] = 'disabled="disabled"';

			$plugin_data = Core::get_installed_plugin_by_text_domain( $template['textdomain'] ?? '' ) ?: [];
			if ( $plugin_data ) {
				// Plugin containing the template is installed but not active.
				$extra[] = 'data-action="activate"';
				$extra[] = sprintf( 'data-template-text-domain="%s"', esc_attr( $plugin_data['text_domain'] ?? '' ) );
			} elseif ( ! empty( $template['included'] ?? 0 ) ) {
				// Plugin is not installed, but can be downloaded.
				$extra[] = sprintf( 'data-template-text-domain="%s"', esc_attr( $template['textdomain'] ?? '' ) );
				$extra[] = 'data-action="install"';
			} elseif ( $template['buy_source'] ?? false ) {
				$extra[] = sprintf( 'data-buy-source="%s"', esc_attr( $template['buy_source'] ) );
				$extra[] = 'data-action="buy"';
			}
		}

		$html .= sprintf(
			'<option data-icon="%s" data-title="%s" data-description="%s" value="%s"%s>%s</option>',
			esc_attr( rgar( $template, 'icon', rgar( $template, 'logo', '' ) ) ),
			esc_attr( rgar( $template, 'label', '' ) ),
			esc_attr( rgar( $template, 'description', '' ) ),
			esc_attr( $template_id ),
			implode( ' ', $extra ),
			esc_html( rgar( $template, 'label', '' ) )
		);
	}

	return $html;
}

?>
<input name="gv_fields" type="hidden" value="<?php echo esc_attr( http_build_query( array( 'fields' => get_post_meta( $post->ID, '_gravityview_directory_fields', true ) ) ) ); ?>" />

<div id="gv-view-configuration-tabs">

	<a href="#gravityview_settings" class="alignright" id="gk-settings-link" title="<?php esc_html_e( 'Scroll to View Settings', 'gk-gravityview' ); ?>"><i class='dashicons dashicons-admin-generic'></i><span class="screen-reader-text"><?php esc_html_e( 'Settings', 'gk-gravityview' ); ?></span></a>

	<ul class="nav-tab-wrapper">
		<li><a href="#directory-view" class="nav-tab"><i class="dashicons dashicons-admin-page tab-icon"></i> <?php printf( esc_html__( '%s Layout', 'gk-gravityview' ), esc_html__( 'Multiple Entries', 'gk-gravityview' ) . '<span class="gv-responsive-label--collapse">' ); ?></span></a></li>
		<li><a href="#single-view" class="nav-tab"><i class="dashicons dashicons-media-default tab-icon"></i> <?php printf( esc_html__( '%s Layout', 'gk-gravityview' ), esc_html__( 'Single Entry', 'gk-gravityview' ) . '<span class="gv-responsive-label--collapse">' ); ?></span></a></li>
		<li><a href="#edit-view" class="nav-tab"><i class="dashicons dashicons-welcome-write-blog tab-icon"></i> <?php printf( esc_html__( '%s Layout', 'gk-gravityview' ), esc_html__( 'Edit Entry', 'gk-gravityview' ) . '<span class="gv-responsive-label--collapse">' ); ?></span></a></li>
	</ul>

	<div id="directory-view">

		<div id="directory-fields" class="gv-section">
			<div class="gv-section-header">
				<h4><?php esc_html_e( 'Top Widgets', 'gk-gravityview' ); ?> <span><?php esc_html_e( 'These widgets will be shown above entries.', 'gk-gravityview' ); ?></span></h4>
				<div class="view-template-select">
					<select
						data-view-dropdown
						id="gravityview_directory_template"
						name="gravityview_directory_template"
						data-label-install="<?php esc_attr_e( 'Install now', 'gk-gravityview' ); ?>"
						data-label-activate="<?php esc_attr_e( 'Activate', 'gk-gravityview' ); ?>"
						data-label-buy="<?php esc_attr_e( 'Buy Now', 'gk-gravityview' ); ?>"
						data-label-available="<?php esc_attr_e( 'Available in PRO', 'gk-gravityview' ); ?>"
						data-label-upgrade="<?php esc_attr_e( 'Upgrade', 'gk-gravityview' ); ?>"
						data-label-learn-more="<?php esc_attr_e( 'Learn more about View types & layouts', 'gk-gravityview' ); ?>"
						data-section="directory"
						data-scope="<?php esc_attr_e( 'Multiple Entries', 'gk-gravityview' ); ?>"
						data-label="<?php esc_attr_e( 'View type', 'gk-gravityview' ); ?>"
					>
						<?php echo render_template_options( $templates, $directory_entries_template ); ?>
					</select>
				</div>
			</div>

			<?php do_action( 'gravityview_render_widgets_active_areas', $directory_entries_template, 'header', $post->ID ); ?>

			<h4><?php esc_html_e( 'Entries Fields', 'gk-gravityview' ); ?> <span><?php esc_html_e( 'These fields will be shown for each entry.', 'gk-gravityview' ); ?></span></h4>

			<div id="directory-active-fields" class="gv-grid">
				<?php
				if ( ! empty( $directory_entries_template ) ) {
					do_action( 'gravityview_render_directory_active_areas', $directory_entries_template, 'directory', $post->ID, true );
				}
				?>
			</div>

			<h4><?php esc_html_e( 'Bottom Widgets', 'gk-gravityview' ); ?> <i class="gf_tooltip gv_tooltip" title="<?php esc_attr_e( 'These widgets will be shown below entries.', 'gk-gravityview' ); ?>"></i></h4>

			<?php

				do_action( 'gravityview_render_widgets_active_areas', $directory_entries_template, 'footer', $post->ID );

				do_action( 'gravityview_render_field_pickers', 'directory' );

			?>

			<?php // list of available widgets to be shown in the popup ?>
			<div id="directory-available-widgets" class="hide-if-js gv-tooltip">
				<div aria-live="polite" role="listbox" class="gv-items-picker-container gv-widget-picker-container" data-layout="grid" data-cols="2">
					<button class="close" role="button" aria-label="<?php esc_html_e( 'Close', 'gk-gravityview' ); ?>"><i class="dashicons dashicons-dismiss"></i></button>
					<?php do_action( 'gravityview_render_available_widgets' ); ?>
				</div>
			</div>

		</div>


	</div><?php // end directory tab ?>



	<?php // Single View Tab ?>

	<div id="single-view">

		<div id="single-fields" class="gv-section">

			<div class="notice notice-warning notice-no-link inline is-dismissible">
				<h3><?php printf( esc_html__( 'Note: %s', 'gk-gravityview' ), sprintf( esc_html__( 'No fields link to the %s layout.', 'gk-gravityview' ), esc_html__( 'Single Entry', 'gk-gravityview' ) ) ); ?></h3>
				<p><a data-beacon-article-modal="54c67bbae4b0512429885516" href="https://docs.gravitykit.com/article/70-linking-to-a-single-entry"><?php printf( esc_html__( 'Learn how to link to %s', 'gk-gravityview' ), esc_html__( 'Single Entry', 'gk-gravityview' ) ); ?></a></p>
			</div>

			<div class="gv-section-header">
				<h4><?php esc_html_e( 'These fields will be shown in Single Entry layout.', 'gk-gravityview' ); ?></h4>

				<div class="view-template-select">
					<select
						data-view-dropdown
						id="gravityview_single_template"
						name="gravityview_single_template"
						data-label-install="<?php esc_attr_e( 'Install', 'gk-gravityview' ); ?>"
						data-label-activate="<?php esc_attr_e( 'Activate now', 'gk-gravityview' ); ?>"
						data-label-buy="<?php esc_attr_e( 'Buy Now', 'gk-gravityview' ); ?>"
						data-label-available="<?php esc_attr_e( 'Available in PRO', 'gk-gravityview' ); ?>"
						data-label-upgrade="<?php esc_attr_e( 'Upgrade', 'gk-gravityview' ); ?>"
						data-label-learn-more="<?php esc_attr_e( 'Learn more about View types & layouts', 'gk-gravityview' ); ?>"
						data-section="single"
						data-scope="<?php esc_attr_e( 'Single Entry', 'gk-gravityview' ); ?>"
						data-label="<?php esc_attr_e( 'View type', 'gk-gravityview' ); ?>"
					>
						<?php echo render_template_options( $templates, $single_entry_template ); ?>
					</select>
				</div>
			</div>

			<div id="single-active-fields" class="gv-grid">
				<?php
				if ( ! empty( $single_entry_template ) ) {
					do_action( 'gravityview_render_directory_active_areas', $single_entry_template, 'single', $post->ID, true );
				}
				?>
			</div>
			<?php
				do_action( 'gravityview_render_field_pickers', 'single' );
			?>
		</div>

	</div> <?php // end single view tab ?>

	<div id="edit-view">

		<div id="edit-fields" class="gv-section">

			<div class="notice notice-warning notice-no-link inline is-dismissible">
				<h3><?php printf( esc_html__( 'Note: %s', 'gk-gravityview' ), sprintf( esc_html__( 'No fields link to the %s layout.', 'gk-gravityview' ), esc_html__( 'Edit Entry', 'gk-gravityview' ) ) ); ?></h3>
				<p><a data-beacon-article-modal="54c67bb9e4b0512429885513" href="https://docs.gravitykit.com/article/67-configuring-the-edit-entry-screen"><?php printf( esc_html__( 'Learn how to link to %s', 'gk-gravityview' ), esc_html__( 'Edit Entry', 'gk-gravityview' ) ); ?></a></p>
			</div>

			<h4><?php esc_html_e( 'Fields shown when editing an entry.', 'gk-gravityview' ); ?> <span><?php esc_html_e( 'If not configured, all form fields will be displayed.', 'gk-gravityview' ); ?></span></h4>

			<div id="edit-active-fields" class="gv-grid">
				<?php
				do_action( 'gravityview_render_directory_active_areas', apply_filters( 'gravityview/template/edit', 'default_table_edit' ), 'edit', $post->ID, true );
				?>
			</div>

			<?php
				do_action( 'gravityview_render_field_pickers', 'edit' );
			?>

		</div>

	</div> <?php // end edit view tab ?>

	<div id='search-view'>

		<div id='search-fields' class='gv-section'>

			<h4><?php esc_html_e( 'Search fields shown.', 'gk-gravityview' ); ?>
				<span><?php esc_html_e( 'If any Advanced Search fields exist, a link will show to toggle them.', 'gk-gravityview' ); ?></span>
			</h4>

			<div id="edit-active-fields" class="gv-grid">
				<?php
				do_action( 'gravityview_render_directory_active_areas', apply_filters( 'gravityview/template/search', 'search' ), 'search', $post->ID, true );
				?>
			</div>

			<?php
			do_action( 'gravityview_render_field_pickers', 'search' );
			?>

		</div>

	</div>
</div> <?php // end tabs ?>

<input type="hidden" name="gv_fields_done" value="1" />
