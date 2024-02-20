<?php
/**
 * @global string $directory_entries_template GravityView_Template::template_id value for directory entries section.
 * @global string $single_entry_template GravityView_Template::template_id value for single entry section.
 */

$templates = array_filter(
	gravityview_get_registered_templates(),
	static function ( array $template ) {
		$placeholder = ! empty( $template['buy_source'] );
		$is_included = ! empty( $template['included'] );

		// TOdo: activate
		return /*'custom' === rgar( $template, 'type' ) && */! $placeholder && ! $is_included;
	}
);

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
			<div class="view-template-select">
				<select id="gravityview_directory_template" name="gravityview_directory_template" class="view-dropdown" data-section="directory" data-scope="Multiple Entries" data-label="View type">
					<option value="">Select a type</option>
					<?php
					foreach ( $templates as $template_id => $template ) {
						printf(
							'<option data-icon="%s" data-title="%s" data-description="%s" value="%s"%s>%s</option>',
							esc_attr( rgar( $template, 'logo', '' ) ), //'data:image/svg+xml;base64, PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAzMiAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwXzE0MzRfMTI4MSkiPgo8cmVjdCB3aWR0aD0iMzIiIGhlaWdodD0iMjQiIHJ4PSIyIiBmaWxsPSJ3aGl0ZSIvPgo8cmVjdCB4PSIxIiB5PSIwLjUiIHdpZHRoPSIzMCIgaGVpZ2h0PSI3IiBmaWxsPSIjRjNGNEY1Ii8+CjxyZWN0IHk9IjciIHdpZHRoPSIzMiIgaGVpZ2h0PSIxIiBmaWxsPSIjMUQyMzI3Ii8+CjxyZWN0IHk9IjEyIiB3aWR0aD0iMzIiIGhlaWdodD0iMSIgZmlsbD0iIzFEMjMyNyIvPgo8cmVjdCB5PSIxNyIgd2lkdGg9IjMyIiBoZWlnaHQ9IjEiIGZpbGw9IiMxRDIzMjciLz4KPHJlY3QgeD0iMTMiIHk9IjEiIHdpZHRoPSIxIiBoZWlnaHQ9IjIzIiBmaWxsPSIjMUQyMzI3Ii8+CjxyZWN0IHg9IjE5IiB5PSIxIiB3aWR0aD0iMSIgaGVpZ2h0PSIyMyIgZmlsbD0iIzFEMjMyNyIvPgo8cmVjdCB4PSIyNSIgeT0iMSIgd2lkdGg9IjEiIGhlaWdodD0iMjMiIGZpbGw9IiMxRDIzMjciLz4KPHJlY3QgeD0iMTUiIHk9IjkuNSIgd2lkdGg9IjMiIGhlaWdodD0iMSIgZmlsbD0iIzFEMjMyNyIvPgo8cmVjdCB4PSIxNSIgeT0iMTQuNSIgd2lkdGg9IjMiIGhlaWdodD0iMSIgZmlsbD0iI0NDRDBENCIvPgo8cmVjdCB4PSIxNSIgeT0iMTkuNSIgd2lkdGg9IjMiIGhlaWdodD0iMSIgZmlsbD0iI0NDRDBENCIvPgo8cmVjdCB4PSIyIiB5PSI5LjUiIHdpZHRoPSIxMCIgaGVpZ2h0PSIxIiBmaWxsPSIjMUQyMzI3Ii8+CjxyZWN0IHg9IjIiIHk9IjE0LjUiIHdpZHRoPSIxMCIgaGVpZ2h0PSIxIiBmaWxsPSIjQ0NEMEQ0Ii8+CjxyZWN0IHg9IjIiIHk9IjE5LjUiIHdpZHRoPSIxMCIgaGVpZ2h0PSIxIiBmaWxsPSIjQ0NEMEQ0Ii8+CjwvZz4KPHJlY3QgeD0iMC41IiB5PSIwLjUiIHdpZHRoPSIzMSIgaGVpZ2h0PSIyMyIgcng9IjEuNSIgc3Ryb2tlPSIjMUQyMzI3Ii8+CjxkZWZzPgo8Y2xpcFBhdGggaWQ9ImNsaXAwXzE0MzRfMTI4MSI+CjxyZWN0IHdpZHRoPSIzMiIgaGVpZ2h0PSIyNCIgcng9IjIiIGZpbGw9IndoaXRlIi8+CjwvY2xpcFBhdGg+CjwvZGVmcz4KPC9zdmc+Cg==',
							esc_attr( rgar( $template, 'label', '' ) ),
							esc_attr( rgar( $template, 'description', '' ) ),
							esc_attr( $template_id ),
							$template_id === $directory_entries_template ? 'selected="selected"' : '',
							esc_html( rgar( $template, 'label', '' ) )
						);
					}
					?>
				</select>
			</div>

			<h4><?php esc_html_e( 'Top Widgets', 'gk-gravityview' ); ?> <span><?php esc_html_e( 'These widgets will be shown above entries.', 'gk-gravityview' ); ?></span></h4>

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

			<div class="view-template-select">
				<select id="gravityview_single_template" name="gravityview_single_template" class="view-dropdown" data-section="single" data-scope="Single Entry" data-label="View type">
					<option value="">Select a type</option>
					<?php
						foreach ( $templates as $template_id => $template ) {
							printf(
								'<option data-icon="%s" data-title="%s" data-description="%s" value="%s"%s>%s</option>',
								esc_attr( rgar( $template, 'logo', '' ) ), //'data:image/svg+xml;base64, PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAzMiAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwXzE0MzRfMTI4MSkiPgo8cmVjdCB3aWR0aD0iMzIiIGhlaWdodD0iMjQiIHJ4PSIyIiBmaWxsPSJ3aGl0ZSIvPgo8cmVjdCB4PSIxIiB5PSIwLjUiIHdpZHRoPSIzMCIgaGVpZ2h0PSI3IiBmaWxsPSIjRjNGNEY1Ii8+CjxyZWN0IHk9IjciIHdpZHRoPSIzMiIgaGVpZ2h0PSIxIiBmaWxsPSIjMUQyMzI3Ii8+CjxyZWN0IHk9IjEyIiB3aWR0aD0iMzIiIGhlaWdodD0iMSIgZmlsbD0iIzFEMjMyNyIvPgo8cmVjdCB5PSIxNyIgd2lkdGg9IjMyIiBoZWlnaHQ9IjEiIGZpbGw9IiMxRDIzMjciLz4KPHJlY3QgeD0iMTMiIHk9IjEiIHdpZHRoPSIxIiBoZWlnaHQ9IjIzIiBmaWxsPSIjMUQyMzI3Ii8+CjxyZWN0IHg9IjE5IiB5PSIxIiB3aWR0aD0iMSIgaGVpZ2h0PSIyMyIgZmlsbD0iIzFEMjMyNyIvPgo8cmVjdCB4PSIyNSIgeT0iMSIgd2lkdGg9IjEiIGhlaWdodD0iMjMiIGZpbGw9IiMxRDIzMjciLz4KPHJlY3QgeD0iMTUiIHk9IjkuNSIgd2lkdGg9IjMiIGhlaWdodD0iMSIgZmlsbD0iIzFEMjMyNyIvPgo8cmVjdCB4PSIxNSIgeT0iMTQuNSIgd2lkdGg9IjMiIGhlaWdodD0iMSIgZmlsbD0iI0NDRDBENCIvPgo8cmVjdCB4PSIxNSIgeT0iMTkuNSIgd2lkdGg9IjMiIGhlaWdodD0iMSIgZmlsbD0iI0NDRDBENCIvPgo8cmVjdCB4PSIyIiB5PSI5LjUiIHdpZHRoPSIxMCIgaGVpZ2h0PSIxIiBmaWxsPSIjMUQyMzI3Ii8+CjxyZWN0IHg9IjIiIHk9IjE0LjUiIHdpZHRoPSIxMCIgaGVpZ2h0PSIxIiBmaWxsPSIjQ0NEMEQ0Ii8+CjxyZWN0IHg9IjIiIHk9IjE5LjUiIHdpZHRoPSIxMCIgaGVpZ2h0PSIxIiBmaWxsPSIjQ0NEMEQ0Ii8+CjwvZz4KPHJlY3QgeD0iMC41IiB5PSIwLjUiIHdpZHRoPSIzMSIgaGVpZ2h0PSIyMyIgcng9IjEuNSIgc3Ryb2tlPSIjMUQyMzI3Ii8+CjxkZWZzPgo8Y2xpcFBhdGggaWQ9ImNsaXAwXzE0MzRfMTI4MSI+CjxyZWN0IHdpZHRoPSIzMiIgaGVpZ2h0PSIyNCIgcng9IjIiIGZpbGw9IndoaXRlIi8+CjwvY2xpcFBhdGg+CjwvZGVmcz4KPC9zdmc+Cg==',
								esc_attr( rgar( $template, 'label', '' ) ),
								esc_attr( rgar( $template, 'description', '' ) ),
								esc_attr( $template_id ),
								$template_id === $single_entry_template ? 'selected="selected"' : '',
								esc_html( rgar( $template, 'label', '' ) )
							);
						}
					?>
				</select>
			</div>

			<div class="notice notice-warning notice-no-link inline is-dismissible">
				<h3><?php printf( esc_html__( 'Note: %s', 'gk-gravityview' ), sprintf( esc_html__( 'No fields link to the %s layout.', 'gk-gravityview' ), esc_html__( 'Single Entry', 'gk-gravityview' ) ) ); ?></h3>
				<p><a data-beacon-article-modal="54c67bbae4b0512429885516" href="https://docs.gravitykit.com/article/70-linking-to-a-single-entry"><?php printf( esc_html__( 'Learn how to link to %s', 'gk-gravityview' ), esc_html__( 'Single Entry', 'gk-gravityview' ) ); ?></a></p>
			</div>

			<h4><?php esc_html_e( 'These fields will be shown in Single Entry layout.', 'gk-gravityview' ); ?></h4>

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
</div> <?php // end tabs ?>

<input type="hidden" name="gv_fields_done" value="1" />
