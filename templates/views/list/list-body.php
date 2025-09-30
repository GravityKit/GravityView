<?php
/**
 * The entry loop for the list output.
 *
 * @global \GV\Template_Context $gravityview
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$template = $gravityview->template;

/** @action `gravityview/template/list/body/before` */
$template::body_before( $gravityview );

// There are no entries.
if ( ! $gravityview->entries->count() ) {

	$no_results_css_class = 'gv-no-results gv-no-results-text';

	if ( 1 === (int) $gravityview->view->settings->get( 'no_entries_options', '0' ) ) {
		$no_results_css_class = 'gv-no-results gv-no-results-form';
	}

	?>
	<div class="gv-list-view <?php echo esc_attr( $no_results_css_class ); ?>">
		<div class="gv-list-view-title">
		<?php
			$output = gv_no_results( true, $gravityview );

			if ( strpos( $output, '<form' ) !== false ) {
				echo $output;
			} else {
				// Maintain backwards compatibility with prior output.
				echo '<h3>' . $output . '</h3>';
			}
		?>
		</div>
	</div>
	<?php
} else {
	// There are entries. Loop through them.
	foreach ( $gravityview->entries->all() as $entry ) {

		$entry_slug = GravityView_API::get_entry_slug( $entry->ID, $entry->as_entry() );

		/** @filter `gravityview/template/list/entry/class` */
		$entry_class = $template::entry_class( 'gv-list-view', $entry, $gravityview );

		?>
		<div id="gv_list_<?php echo esc_attr( $entry_slug ); ?>" class="<?php echo gravityview_sanitize_html_class( $entry_class ); ?>">

		<?php

		/** @action `gravityview/template/list/entry/before` */
		$template::entry_before( $entry, $gravityview );

		/**
		 * @var bool $has_title
		 * @var bool $has_subtitle
		 * @var \GV\Field_Collection $title
		 * @var \GV\Field_Collection $subtitle
		 */
		extract( $template->extract_zone_vars( array( 'title', 'subtitle' ) ) );

		if ( $has_title || $has_subtitle ) {

			/** @action `gravityview/template/list/entry/title/before` */
			$template::entry_before( $entry, $gravityview, 'title' );

			?>

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

					$extras['zone_id'] = 'directory_list-title';
					echo $gravityview->template->the_field( $field, $entry, $extras );
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

							$extras['zone_id'] = 'directory_list-subtitle';
							echo $gravityview->template->the_field( $field, $entry, $extras );
						}
						?>
						</div>
						<?php
				}
				?>
			</div>
			<?php

			/** @action `gravityview/template/list/entry/title/after` */
			$template::entry_after( $entry, $gravityview, 'title' );

		}

		/**
		 * @var bool $has_image
		 * @var bool $has_description
		 * @var bool $has_content_attributes
		 * @var \GV\Field_Collection $image
		 * @var \GV\Field_Collection $description
		 * @var \GV\Field_Collection $attributes
		 */
		extract( $template->extract_zone_vars( array( 'image', 'description', 'content-attributes' ) ) );

		$has_content_before_action = has_action( 'gravityview/template/list/entry/content/before' );
		$has_content_after_action  = has_action( 'gravityview/template/list/entry/content/after' );

		if ( $has_image || $has_description || $has_content_attributes || $has_content_before_action || $has_content_after_action ) {
			?>
			<div class="gv-grid gv-list-view-content">

				<?php

					/** @action `gravityview/template/list/entry/content/before` */
					$template::entry_before( $entry, $gravityview, 'content' );

				if ( $has_image ) {
					?>
						<div class="gv-grid-col-1-3 gv-list-view-content-image">
						<?php
						foreach ( $image->all() as $i => $field ) {
							echo $gravityview->template->the_field( $field, $entry, array( 'zone_id' => 'directory_list-image' ) );
						}
						?>
						</div>
						<?php
				}

				if ( $has_description ) {
					?>
						<div class="gv-grid-col-2-3 gv-list-view-content-description">
						<?php
						foreach ( $description->all() as $i => $field ) {
							$extras = array(
								'wpautop'      => true,
								'zone_id'      => 'directory_list-description',
								'label_markup' => '<h4>{{ label }}</h4>',
							);
							echo $gravityview->template->the_field( $field, $entry, $extras );
						}
						?>
						</div>
						<?php
				}

				if ( $has_content_attributes ) {
					?>
						<div class="gv-grid-col-3-3 gv-list-view-content-attributes">
						<?php
						foreach ( $attributes->all() as $i => $field ) {
							$extras = array(
								'zone_id' => 'directory_list-content-attributes',
								'markup'  => '<p id="{{ field_id }}" class="{{ class }}">{{ label }}{{ value }}</p>',
							);
							echo $gravityview->template->the_field( $field, $entry, $extras );
						}
						?>
						</div>
						<?php
				}

					/** @action `gravityview/template/list/entry/content/after` */
					$template::entry_after( $entry, $gravityview, 'content' );
				?>

			</div>

			<?php
		}

		/**
		 * @var bool $has_footer_left
		 * @var bool $has_footer_right
		 * @var \GV\Field_Collection $footer_left
		 * @var \GV\Field_Collection $footer_right
		 */
		extract( $template->extract_zone_vars( array( 'footer-left', 'footer-right' ) ) );

		// Is the footer configured?
		if ( $has_footer_left || $has_footer_right ) {
			/** @action `gravityview/template/list/entry/footer/before` */
			$template::entry_before( $entry, $gravityview, 'footer' );
			?>

			<div class="gv-grid gv-list-view-footer">
				<div class="gv-grid-col-1-2 gv-left">
					<?php
					foreach ( $footer_left->all() as $i => $field ) {
						echo $gravityview->template->the_field( $field, $entry, array( 'zone_id' => 'directory_list-footer-left' ) );
					}
					?>
				</div>

				<div class="gv-grid-col-1-2 gv-right">
					<?php
					foreach ( $footer_right->all() as $i => $field ) {
						echo $gravityview->template->the_field( $field, $entry, array( 'zone_id' => 'directory_list-footer-right' ) );
					}
					?>
				</div>
			</div>

			<?php

			/** @action `gravityview/template/list/entry/footer/after` */
			$template::entry_after( $entry, $gravityview, 'footer' );

		} // End if footer is configured

		/** @action `gravityview/template/list/entry/after` */
		$template::entry_after( $entry, $gravityview );

		?>

		</div>

		<?php
	}
}

/** @action `gravityview/template/list/body/after` */
$template::body_after( $gravityview );
