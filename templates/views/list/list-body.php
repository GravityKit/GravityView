<?php
/**
 * The entry loop for the list output.
 *
 * @global stdClass $gravityview
 *  \GV\View $gravityview::$view
 *  \GV\View_Template $gravityview::$template
 *  \GV\Field_Collection $gravityview::$fields
 *  \GV\Entry_Collection $gravityview::$entries
 */

// There are no entries.
if ( ! $gravityview->entries->count() ) {
	?>
	<div class="gv-list-view gv-no-results">
		<div class="gv-list-view-title">
			<h3><?php echo gv_no_results(); ?></h3>
		</div>
	</div>
	<?php
} else {
	// There are entries. Loop through them.
	foreach ( $gravityview->entries->all() as $entry ) {

		$entry_slug = GravityView_API::get_entry_slug( $entry->ID, $entry->as_entry() );

		extract( $gravityview->template->extract_zone_vars( array( 'title', 'subtitle' ) ) );
	?>
		<div id="gv_list_<?php echo esc_attr( $entry_slug ); ?>" class="gv-list-view">

		<?php if ( $has_title || $has_subtitle ) { ?>

			<div class="gv-list-view-title">
				<?php
					$did_main = 0;
					foreach ( $title->all() as $i => $field ) {
						// The first field in the title zone is the main
						if ( $did_main == 0 ) {
							$extras = array();
							$wrap = array( 'h3' => $gravityview->template->the_field_attributes( $field, array( 'id' => '' ) ) );
						} else {
							$wrap = array( 'div' => $gravityview->template->the_field_attributes( $field ) );
							$extras = array( 'wpautop' => true );
						}

						if ( $output = $gravityview->template->the_field( $field, $entry, $extras ) ) {
							$did_main = 1;
							echo $gravityview->template->wrap( $output, $wrap );
						}
					}

					if ( $has_subtitle ) {
						?><div class="gv-list-view-subtitle"><?php
							$did_main = 0;
							foreach ( $subtitle->all() as $i => $field ) {
								// The first field in the subtitle zone is the main
								if ( $did_main == 0 ) {
									$wrap = array( 'h4' => $gravityview->template->the_field_attributes( $field ) );
								} else {
									$wrap = array( 'p' => $gravityview->template->the_field_attributes( $field ) );
								}

								if ( $output = $gravityview->template->the_field( $field, $entry, $wrap, $extras ) ) {
									$did_main = 1;
									echo $gravityview->template->wrap( $output, $wrap );
								}
							}
						?></div><?php
					}
				?>
			</div>
		<?php }

		extract( $gravityview->template->extract_zone_vars( array( 'image', 'description', 'content-attributes' ) ) );

		if ( $has_image || $has_description || $has_content_attributes ) {
			?>
            <div class="gv-grid gv-list-view-content">

				<?php
					if ( $has_image ) {
						?><div class="gv-grid-col-1-3 gv-list-view-content-image"><?php
						foreach ( $image->all() as $i => $field ) {
							if ( $output = $gravityview->template->the_field( $field, $entry ) ) {
								echo $gravityview->template->wrap( $output, array( 'div' => $gravityview->template->the_field_attributes( $field ) ) );
							}
						}
						?></div><?php
					}

					if ( $has_description ) {
						?><div class="gv-grid-col-2-3 gv-list-view-content-description"><?php
						$extras = array( 'label_tag' => 'h4', 'wpautop' => true );
						foreach ( $description->all() as $i => $field ) {
							if ( $output = $gravityview->template->the_field( $field, $entry, $extras ) ) {
								echo $gravityview->template->wrap( $output, array( 'div' => $gravityview->template->the_field_attributes( $field ) ) );
							}
						}
						?></div><?php
					}

					if ( $has_content_attributes ) {
						?><div class="gv-grid-col-3-3 gv-list-view-content-attributes"><?php
						$extras = array( 'label_tag' => 'h4', 'wpautop' => true );
						foreach ( $attributes->all() as $i => $field ) {
							if ( $output = $gravityview->template->the_field( $field, $entry, $extras ) ) {
								echo $gravityview->template->wrap( $output, array( 'div' => $gravityview->template->the_field_attributes( $field ) ) );
							}
						}
						?></div><?php
					}
			?>

            </div>

			<?php
		}

		extract( $gravityview->template->extract_zone_vars( array( 'footer-left', 'footer-right' ) ) );

		// Is the footer configured?
		if ( $has_footer_left || $has_footer_right ) {
			?>

			<div class="gv-grid gv-list-view-footer">
				<div class="gv-grid-col-1-2 gv-left">
					<?php
						foreach ( $footer_left->all() as $i => $field ) {
							if ( $output = $gravityview->template->the_field( $field, $entry ) ) {
								echo $gravityview->template->wrap( $output, array( 'div' => $gravityview->template->the_field_attributes( $field ) ) );
							}
						}
					?>
				</div>

				<div class="gv-grid-col-1-2 gv-right">
					<?php
						foreach ( $footer_right->all() as $i => $field ) {
							if ( $output = $gravityview->template->the_field( $field, $entry ) ) {
								echo $gravityview->template->wrap( $output, array( 'div' => $gravityview->template->the_field_attributes( $field ) ) );
							}
						}
					?>
				</div>
			</div>

			<?php
		} // End if footer is configured

		?>

		</div>

	<?php }
}
