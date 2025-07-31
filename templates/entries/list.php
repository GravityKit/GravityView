<?php
/**
 * Display a single entry when using a list template
 *
 * @global \GV\Template_Context $gravityview
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$entry = $gravityview->entry;

\GV\Mocks\Legacy_Context::push( array( 'view' => $gravityview->view ) );

$entry_slug = GravityView_API::get_entry_slug( $entry->ID, $entry->as_entry() );

extract( $gravityview->template->extract_zone_vars( array( 'title', 'subtitle' ) ) );
extract( $gravityview->template->extract_zone_vars( array( 'image', 'description', 'content-attributes' ) ) );
extract( $gravityview->template->extract_zone_vars( array( 'footer-left', 'footer-right' ) ) );

gravityview_before( $gravityview );

?><div class="<?php gv_container_class( 'gv-list-container gv-list-single-container', true, $gravityview ); ?>">

	<?php
	if ( $link = gravityview_back_link( $gravityview ) ) {
		?>
		<p class="gv-back-link"><?php echo $link; ?></p><?php } ?>

	<?php if ( $has_title || $has_subtitle || $has_image || $has_description || $has_content_attributes || $has_footer_left || $has_footer_right ) : ?>
		<div id="gv_list_<?php echo esc_attr( $entry_slug ); ?>" class="gv-list-view">

		<?php gravityview_header( $gravityview ); ?>

		<?php if ( $has_title || $has_subtitle ) { ?>

			<div class="gv-list-view-title">

				<?php
					$did_main = 0;
				foreach ( $title->all() as $i => $field ) {
					// The first field in the title zone is the main
					if ( 0 == $did_main ) {
						$did_main = 1;
						$extras   = array(
							'wpautop' => false,
							'markup'  => '<h3 class="{{ class }}">{{ label }}{{ value }}</h3>',
						);
					} else {
						$extras = array( 'wpautop' => true );
					}

					$extras['zone_id'] = 'single_list-title';
					echo $gravityview->template->the_field( $field, $extras );
				}

				if ( $has_subtitle ) {
					?>
						<div class="gv-list-view-subtitle">
						<?php
						$did_main = 0;
						foreach ( $subtitle->all() as $i => $field ) {
							// The first field in the subtitle zone is the main
							if ( 0 == $did_main ) {
								$did_main = 1;
								$extras   = array( 'markup' => '<h4 id="{{ field_id }}" class="{{ class }}">{{ label }}{{ value }}</h4>' );
							}

							$extras['zone_id'] = 'single_list-subtitle';
							echo $gravityview->template->the_field( $field, $extras );
						}
						?>
						</div>
						<?php
				}
				?>
			</div>
			<?php
		}

		if ( $has_image || $has_description || $has_content_attributes ) {
			?>
			<div class="gv-list-view-content">

				<?php
				if ( $has_image ) {
					?>
						<div class="gv-list-view-content-image gv-grid-col-1-3">
						<?php
						foreach ( $image->all() as $i => $field ) {
							echo $gravityview->template->the_field( $field, array( 'zone_id' => 'single_list-image' ) );
						}
						?>
						</div>
						<?php
				}

				if ( $has_description ) {
					?>
						<div class="gv-list-view-content-description">
						<?php
						$extras = array(
							'label_tag' => 'h4',
							'wpautop'   => true,
						);
						foreach ( $description->all() as $i => $field ) {
							$extras = array(
								'wpautop'      => true,
								'zone_id'      => 'single_list-description',
								'label_markup' => '<h4>{{ label }}</h4>',
							);
							echo $gravityview->template->the_field( $field, $extras );
						}
						?>
						</div>
						<?php
				}

				if ( $has_content_attributes ) {
					?>
						<div class="gv-list-view-content-attributes">
						<?php
						$extras = array(
							'label_tag' => 'h4',
							'wpautop'   => true,
						);
						foreach ( $attributes->all() as $i => $field ) {
							$extras = array(
								'zone_id' => 'single_list-content-attributes',
								'markup'  => '<p id="{{ field_id }}" class="{{ class }}">{{ label }}{{ value }}</p>',
							);
							echo $gravityview->template->the_field( $field, $extras );
						}
						?>
						</div>
						<?php
				}
				?>

			</div>

			<?php
		}

		// Is the footer configured?
		if ( $has_footer_left || $has_footer_right ) {
			?>

			<div class="gv-grid gv-list-view-footer">
				<div class="gv-grid-col-1-2 gv-left">
					<?php
					foreach ( $footer_left->all() as $i => $field ) {
						echo $gravityview->template->the_field( $field, array( 'zone_id' => 'single_list-footer-left' ) );
					}
					?>
				</div>

				<div class="gv-grid-col-1-2 gv-right">
					<?php
					foreach ( $footer_right->all() as $i => $field ) {
						echo $gravityview->template->the_field( $field, array( 'zone_id' => 'directory_list-footer-right' ) );
					}
					?>
				</div>
			</div>

			<?php
		} // End if footer is configured

		?>
		</div>
	<?php endif; ?>
</div>
<?php

gravityview_after( $gravityview );

\GV\Mocks\Legacy_Context::pop();
