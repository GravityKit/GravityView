<div id="gv-view-configuration-tabs">

	<ul class="nav-tab-wrapper">
		<li><a href="#directory-view" class="nav-tab"><i class="dashicons dashicons-admin-page"></i> <?php esc_html_e( 'Multiple Entries', 'gravityview' ); ?></a></li>
		<li><a href="#single-view" class="nav-tab"><i class="dashicons dashicons-media-default"></i> <?php esc_html_e( 'Single Entry', 'gravityview' ); ?></a></li>
		<li><a href="#edit-view" class="nav-tab"><i class="dashicons dashicons-welcome-write-blog"></i> <?php esc_html_e( 'Edit Entry', 'gravityview' ); ?></a></li>
	</ul>

	<div id="directory-view">

		<div id="directory-fields" class="gv-section">

			<h4><?php esc_html_e( 'Above Entries Widgets', 'gravityview'); ?> <span><?php esc_html_e( 'These widgets will be shown above entries.', 'gravityview'); ?></span></h4>

			<?php do_action('gravityview_render_widgets_active_areas', $curr_template, 'header', $post->ID ); ?>

			<h4><?php esc_html_e( 'Entries Fields', 'gravityview'); ?> <span><?php esc_html_e( 'These fields will be shown for each entry.', 'gravityview'); ?></span></h4>

			<div id="directory-active-fields" class="gv-grid gv-grid-pad gv-grid-border">
				<?php if(!empty( $curr_template ) ) {
					do_action('gravityview_render_directory_active_areas', $curr_template, 'directory', $post->ID, true );
				} ?>
			</div>

			<h4><?php esc_html_e( 'Below Entries Widgets', 'gravityview'); ?> <span><?php esc_html_e( 'These widgets will be shown below entries.', 'gravityview'); ?></span></h4>

			<?php do_action('gravityview_render_widgets_active_areas', $curr_template, 'footer', $post->ID ); ?>


			<?php // list of available fields to be shown in the popup ?>
			<div id="directory-available-fields" class="hide-if-js gv-tooltip">
				<span class="close"><i class="dashicons dashicons-dismiss"></i></span>
				<?php do_action('gravityview_render_available_fields', $curr_form, 'directory' ); ?>
			</div>

			<?php // list of available widgets to be shown in the popup ?>
			<div id="directory-available-widgets" class="hide-if-js gv-tooltip">
				<span class="close"><i class="dashicons dashicons-dismiss"></i></span>
				<?php do_action('gravityview_render_available_widgets' ); ?>
			</div>

		</div>


	</div><?php //end directory tab ?>



	<?php // Single View Tab ?>

	<div id="single-view">

		<div id="single-fields" class="gv-section">

			<h4><?php esc_html_e( 'These fields will be shown in Single Entry view.', 'gravityview'); ?></h4>

			<div id="single-active-fields" class="gv-grid gv-grid-pad gv-grid-border">
				<?php if(!empty( $curr_template ) ) {
					do_action('gravityview_render_directory_active_areas', $curr_template, 'single', $post->ID, true );
				} ?>
			</div>

			<div id="single-available-fields" class="hide-if-js gv-tooltip">
				<span class="close"><i class="dashicons dashicons-dismiss"></i></span>
				<?php do_action('gravityview_render_available_fields', $curr_form, 'single' ); ?>
			</div>

		</div>

	</div> <?php // end single view tab ?>

	<div id="edit-view">

		<div id="edit-fields" class="gv-section">

			<h4><?php esc_html_e( 'Fields shown when editing an entry.', 'gravityview'); ?> <span><?php esc_html_e('If not configured, all form fields will be displayed.', 'gravityview'); ?></span></h4>

			<div id="edit-active-fields" class="gv-grid gv-grid-pad gv-grid-border">
				<?php
				do_action('gravityview_render_directory_active_areas', apply_filters( 'gravityview/template/edit', 'default_table_edit' ), 'edit', $post->ID, true );
				?>
			</div>

			<div id="edit-available-fields" class="hide-if-js gv-tooltip">
				<span class="close"><i class="dashicons dashicons-dismiss"></i></span>
				<?php do_action('gravityview_render_available_fields', $curr_form, 'edit' ); ?>
			</div>

		</div>

	</div> <?php // end edit view tab ?>

</div> <?php // end tabs ?>