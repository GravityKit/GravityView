<?php
/**
 * @file templates/list-body.php
 *
 * Display the entries loop when using a list layout
 *
 * @package GravityView
 * @subpackage GravityView/templates
 *
 * @global GravityView_View $this
 */

/**
 * Tap in before the entry loop has been displayed.
 *
 * @param \GravityView_View $this The GravityView_View instance
 */
do_action( 'gravityview_list_body_before', $this );

// There are no entries.
if ( ! $this->getTotalEntries() ) {

	?>
	<div class="gv-list-view gv-no-results">
		<div class="gv-list-view-title">
			<h3><?php echo gv_no_results(); ?></h3>
		</div>
	</div>
	<?php

} elseif ( $this->getContextFields() ) {

	// There are entries. Loop through them.
	foreach ( $this->getEntries() as $entry ) {

		$this->setCurrentEntry( $entry );

		$entry_slug = GravityView_API::get_entry_slug( $entry['id'], $entry );
		?>

		<div id="gv_list_<?php echo esc_attr( $entry_slug ); ?>" class="<?php echo esc_attr( apply_filters( 'gravityview_entry_class', 'gv-list-view', $entry, $this ) ); ?>">

		<?php

		/**
		 * Tap in before the the entry is displayed, inside the entry container.
		 *
		 * @param array $entry Gravity Forms Entry array
		 * @param \GravityView_View $this The GravityView_View instance
		 */
		do_action( 'gravityview_entry_before', $entry, $this );

		?>

		<?php if ( $this->getField( 'directory_list-title' ) || $this->getField( 'directory_list-subtitle' ) ) { ?>

			<?php

			/**
			 * Tap in before the the entry title is displayed.
			 *
			 * @param array $entry Gravity Forms Entry array
			 * @param \GravityView_View $this The GravityView_View instance
			 */
			do_action( 'gravityview_entry_title_before', $entry, $this );

			?>
			<div class="gv-list-view-title">

				<?php
				if ( $this->getField( 'directory_list-title' ) ) {
					$i          = 0;
					$title_args = array(
						'entry'      => $entry,
						'form'       => $this->getForm(),
						'hide_empty' => $this->getAtts( 'hide_empty' ),
					);

					foreach ( $this->getField( 'directory_list-title' ) as $field ) {
						$title_args['field'] = $field;

						// The first field in the title zone is the main
						if ( 0 == $i ) {
							$title_args['markup'] = '<h3 class="{{class}}">{{label}}{{value}}</h3>';
							echo gravityview_field_output( $title_args );
							unset( $title_args['markup'] );
						} else {
							$title_args['wpautop'] = true;
							echo gravityview_field_output( $title_args );
						}

						++$i;
					}
				}

				$this->renderZone(
					'subtitle',
					array(
						'markup'        => '<h4 id="{{ field_id }}" class="{{class}}">{{label}}{{value}}</h4>',
						'wrapper_class' => 'gv-list-view-subtitle',
					)
				);
				?>
			</div>

			<?php

			/**
			 * Tap in after the title block.
			 *
			 * @param array $entry Gravity Forms Entry array
			 * @param \GravityView_View $this The GravityView_View instance
			 */
			do_action( 'gravityview_entry_title_after', $entry, $this );

			?>

			<?php
		}

		if (
			( $this->getFields( 'directory_list-image' ) || $this->getFields( 'directory_list-description' ) || $this->getFields( 'directory_list-content-attributes' ) )
			|| has_action( 'gravityview_entry_content_before' ) || has_action( 'gravityview_entry_content_after' )
		) {
			?>

			<div class="gv-grid gv-list-view-content">

				<?php

				/**
				 * Tap in inside the View Content wrapper div.
				 *
				 * @param array $entry Gravity Forms Entry array
				 * @param \GravityView_View $this The GravityView_View instance
				 */
				do_action( 'gravityview_entry_content_before', $entry, $this );

				$this->renderZone( 'image', 'wrapper_class="gv-grid-col-1-3 gv-list-view-content-image"' );

				$this->renderZone(
					'description',
					array(
						'wrapper_class' => 'gv-grid-col-2-3 gv-list-view-content-description',
						'label_markup'  => '<h4>{{label}}</h4>',
						'wpautop'       => true,
					)
				);

				$this->renderZone(
					'content-attributes',
					array(
						'wrapper_class' => 'gv-list-view-content-attributes',
						'markup'        => '<p id="{{ field_id }}" class="{{class}}">{{label}}{{value}}</p>',
					)
				);

				/**
				 * Tap in at the end of the View Content wrapper div.
				 *
				 * @param array $entry Gravity Forms Entry array
				 * @param \GravityView_View $this The GravityView_View instance
				 */
				do_action( 'gravityview_entry_content_after', $entry, $this );

				?>

			</div>

			<?php
		}

		// Is the footer configured?
		if ( $this->getField( 'directory_list-footer-left' ) || $this->getField( 'directory_list-footer-right' ) ) {

			/**
			 * Tap in before the footer wrapper.
			 *
			 * @param array $entry Gravity Forms Entry array
			 * @param \GravityView_View $this The GravityView_View instance
			 */
			do_action( 'gravityview_entry_footer_before', $entry, $this );

			?>

			<div class="gv-grid gv-list-view-footer">
				<div class="gv-grid-col-1-2 gv-left">
					<?php $this->renderZone( 'footer-left' ); ?>
				</div>

				<div class="gv-grid-col-1-2 gv-right">
					<?php $this->renderZone( 'footer-right' ); ?>
				</div>
			</div>

			<?php

			/**
			 * Tap in after the footer wrapper.
			 *
			 * @param array $entry Gravity Forms Entry array
			 * @param \GravityView_View $this The GravityView_View instance
			 */
			do_action( 'gravityview_entry_footer_after', $entry, $this );

		} // End if footer is configured


		/**
		 * Tap in after the entry has been displayed, but before the container is closed.
		 *
		 * @param array $entry Gravity Forms Entry array
		 * @param \GravityView_View $this The GravityView_View instance
		 */
		do_action( 'gravityview_entry_after', $entry, $this );

		?>

		</div>

		<?php
	}
} // End if has entries

/**
 * Tap in after the entry loop has been displayed.
 *
 * @param \GravityView_View $this The GravityView_View instance
 */
do_action( 'gravityview_list_body_after', $this );
