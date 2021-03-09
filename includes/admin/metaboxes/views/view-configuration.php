<?php
/** @global string $curr_template GravityView_Template::template_id value. Empty string if not. */
?>
<input name="gv_fields" type="hidden" value="<?php echo esc_attr( http_build_query( array( 'fields' => get_post_meta( $post->ID, '_gravityview_directory_fields', true ) ) ) ); ?>" />

<div id="gv-view-configuration-tabs">

	<ul class="nav-tab-wrapper">
		<li><a href="#directory-view" class="nav-tab"><i class="dashicons dashicons-admin-page tab-icon"></i> <?php echo sprintf( esc_html__( '%s Layout', 'gravityview' ), esc_html__( 'Multiple Entries', 'gravityview' ) . '<span class="gv-responsive-label--collapse">' ); ?></span></a></li>
		<li><a href="#single-view" class="nav-tab"><i class="dashicons dashicons-media-default tab-icon"></i> <?php echo sprintf( esc_html__( '%s Layout', 'gravityview' ), esc_html__( 'Single Entry', 'gravityview' ) . '<span class="gv-responsive-label--collapse">' ); ?></span></a></li>
		<li><a href="#edit-view" class="nav-tab"><i class="dashicons dashicons-welcome-write-blog tab-icon"></i> <?php echo sprintf( esc_html__( '%s Layout', 'gravityview' ), esc_html__( 'Edit Entry', 'gravityview' ) . '<span class="gv-responsive-label--collapse">' ); ?></span></a></li>
	</ul>

	<div id="directory-view">

		<div id="directory-fields" class="gv-section">

			<h4><?php esc_html_e( 'Top Widgets', 'gravityview'); ?> <span><?php esc_html_e( 'These widgets will be shown above entries.', 'gravityview'); ?></span></h4>

			<?php do_action('gravityview_render_widgets_active_areas', $curr_template, 'header', $post->ID ); ?>

			<h4><?php esc_html_e( 'Entries Fields', 'gravityview'); ?> <span><?php esc_html_e( 'These fields will be shown for each entry.', 'gravityview'); ?></span></h4>

			<div id="directory-active-fields" class="gv-grid">
				<?php if(!empty( $curr_template ) ) {
					do_action('gravityview_render_directory_active_areas', $curr_template, 'directory', $post->ID, true );
				} ?>
			</div>

			<h4><?php esc_html_e( 'Bottom Widgets', 'gravityview'); ?> <i class="gf_tooltip gv_tooltip" title="<?php esc_attr_e( 'These widgets will be shown below entries.', 'gravityview'); ?>"></i></h4>

			<?php

                do_action('gravityview_render_widgets_active_areas', $curr_template, 'footer', $post->ID );

    			do_action('gravityview_render_field_pickers', 'directory' );

            ?>

			<?php // list of available widgets to be shown in the popup ?>
            <div id="directory-available-widgets" class="hide-if-js gv-tooltip">
				<div aria-live="polite" role="listbox" class="gv-items-picker-container gv-widget-picker-container" data-layout="grid" data-cols="2">
                	<button class="close" role="button" aria-label="<?php esc_html_e( 'Close', 'gravityview' ); ?>"><i class="dashicons dashicons-dismiss"></i></button>
					<?php do_action('gravityview_render_available_widgets' ); ?>
				</div>
            </div>

		</div>


	</div><?php //end directory tab ?>



	<?php // Single View Tab ?>

	<div id="single-view">

		<div id="single-fields" class="gv-section">

			<div class="notice notice-warning inline is-dismissible">
				<h3><?php printf( esc_html__( 'Note: %s', 'gravityview' ), sprintf( esc_html__( 'No fields link to the %s layout.', 'gravityview'), esc_html__( 'Single Entry', 'gravityview' ) ) ); ?></h3>
				<p><a data-beacon-article-modal="54c67bbae4b0512429885516" href="https://docs.gravityview.co/article/70-linking-to-a-single-entry"><?php printf( esc_html__( 'Learn how to link to %s', 'gravityview' ), esc_html__( 'Single Entry', 'gravityview' ) ); ?></a></p>
			</div>

			<h4><?php esc_html_e( 'These fields will be shown in Single Entry layout.', 'gravityview'); ?></h4>

			<div id="single-active-fields" class="gv-grid">
				<?php
                if(!empty( $curr_template ) ) {
				    do_action('gravityview_render_directory_active_areas', $curr_template, 'single', $post->ID, true );
                }
			    ?>
			</div>
            <?php
                do_action('gravityview_render_field_pickers', 'single' );
			?>
		</div>

	</div> <?php // end single view tab ?>

	<div id="edit-view">

		<div id="edit-fields" class="gv-section">

			<div class="notice notice-warning inline is-dismissible">
				<h3><?php printf( esc_html__( 'Note: %s', 'gravityview' ), sprintf( esc_html__( 'No fields link to the %s layout.', 'gravityview'), esc_html__( 'Edit Entry', 'gravityview' ) ) ); ?></h3>
				<p><a data-beacon-article-modal="54c67bb9e4b0512429885513" href="https://docs.gravityview.co/article/67-configuring-the-edit-entry-screen"><?php printf( esc_html__( 'Learn how to link to %s', 'gravityview' ), esc_html__( 'Edit Entry', 'gravityview' ) ); ?></a></p>
			</div>

			<h4><?php esc_html_e( 'Fields shown when editing an entry.', 'gravityview'); ?> <span><?php esc_html_e('If not configured, all form fields will be displayed.', 'gravityview'); ?></span></h4>

			<div id="edit-active-fields" class="gv-grid">
				<?php
				do_action('gravityview_render_directory_active_areas', apply_filters( 'gravityview/template/edit', 'default_table_edit' ), 'edit', $post->ID, true );
				?>
			</div>

			<?php
			    do_action('gravityview_render_field_pickers', 'edit' );
			?>

		</div>

	</div> <?php // end edit view tab ?>
</div> <?php // end tabs ?>

<input type="hidden" name="gv_fields_done" value="1" />
