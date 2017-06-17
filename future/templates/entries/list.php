<?php
/**
 * Display a single entry when using a list template
 *
 * @global array $gravityview
 */

$entry = $gravityview->entry;

\GV\Mocks\Legacy_Context::push( array( 'view' => $gravityview->view ) );

$entry_slug = GravityView_API::get_entry_slug( $entry->ID, $entry->as_entry() );

extract( $gravityview->template->extract_zone_vars( array( 'title', 'subtitle' ) ) );

?>

<?php gravityview_before(); ?>

<div class="gv-container gv-list-single-container gv-list-container">

	<p class="gv-back-link"><?php echo gravityview_back_link(); ?></p>

	<div id="gv_list_<?php echo esc_attr( $entry_slug ); ?>" class="gv-list-view">

	<?php if ( $has_title || $has_title ) { ?>

		<div class="gv-list-view-title">

			<?php
				foreach ( $title->all() as $i => $field ) {
					// The first field in the title zone is the main
					if ( $i == 0 ) {
						$wrap = array( 'h3' => array() );
					} else {
						$wrap = array( 'p' => array() );
					}
					$gravityview->template->the_field( $field, $entry, $wrap );
				}

				foreach ( $subtitle->all() as $i => $field ) {
					// The first field in the subtitle zone is the main
					if ( $i == 0 ) {
						$wrap = array( 'h4' => array( 'class' => 'gv-list-view-subtitle' ) );
					} else {
						$wrap = array( 'p' => array( 'class' => 'gv-list-view-subtitle' ) );
					}
					$gravityview->template->the_field( $field, $entry, $wrap );
				}
			?>
		</div>
	<?php }

	extract( $gravityview->template->extract_zone_vars( array( 'image', 'description', 'content-attributes' ) ) );

	if ( $has_image || $has_description || $has_content_attributes ) {
		?>
		<div class="gv-grid gv-list-view-content">

			<?php
				foreach ( $image->all() as $i => $field ) {
					$gravityview->template->the_field( $field, $entry, array( 'div' => array( 'class' => 'gv-grid-col-1-3 gv-list-view-content-image' ) ) );
				}

				foreach ( $description->all() as $i => $field ) {
					$gravityview->template->the_field( $field, $entry, array( 'h4' => array( 'class' => 'gv-grid-col-2-3 gv-list-view-content-description' ) ) );
				}

				foreach ( $content_attributes->all() as $i => $field ) {
					$gravityview->template->the_field( $field, $entry, array( 'p' => array( 'class' => 'gv-list-view-content-attributes' ) ) );
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
						$gravityview->template->the_field( $field, $entry );
					}
				?>
			</div>

			<div class="gv-grid-col-1-2 gv-right">
				<?php
					foreach ( $footer_right->all() as $i => $field ) {
						$gravityview->template->the_field( $field, $entry );
					}
				?>
			</div>
		</div>

		<?php
	} // End if footer is configured

?>
	</div>
</div>

<?php

gravityview_after();

\GV\Mocks\Legacy_Context::pop();
