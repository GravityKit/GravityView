<?php
/**
 * The entry loop for the table output.
 *
 * @global \GV\Template_Context $gravityview
 */
?>
	<tbody>
		<?php

		/**
		 * @action `gravityview_table_body_before` Inside the `tbody`, before any rows are rendered. Can be used to insert additional rows.
		 * @since 1.0.7
         * @since 2.0 Updated to pass \GV\Template_Context instead of \GravityView_View
		 * @param GravityView_View $this Current GravityView_View object
		 */
		do_action('gravityview_table_body_before', $gravityview );

		if ( ! $gravityview->entries->count() ) {
			?>
			<tr>
				<?php

				/**
				 * @action `gravityview_table_cells_before` Inside the `tr` while rendering each entry in the loop. Can be used to insert additional table cells.
				 * @since 1.0.7
				 * @since 2.0 Updated to pass \GV\Template_Context instead of \GravityView_View
				 * @param \GV\Template_Context $context Current $gravityview state
				 */
                do_action('gravityview_table_tr_before', $gravityview );

                ?>
				<td colspan="<?php echo $gravityview->fields->by_position( 'directory_table-columns' )->by_visible()->count() ? : ''; ?>" class="gv-no-results">
					<?php echo gv_no_results(); ?>
				</td>
				<?php

				/**
				 * @action `gravityview_table_cells_after` Inside the `tr` while rendering each entry in the loop. Can be used to insert additional table cells.
				 * @since 1.0.7
				 * @since 2.0 Updated to pass \GV\Template_Context instead of \GravityView_View
				 * @param \GV\Template_Context $context Current $gravityview state
				 */
                do_action('gravityview_table_tr_after', $gravityview );

                ?>
			</tr>
		<?php
		} else {
			foreach ( $gravityview->entries->all() as $entry ) {

				// Add `alt` class to alternate rows
				$alt = empty( $alt ) ? 'alt' : '';

				/**
				 * @filter `gravityview_entry_class` Modify the class applied to the entry row
                 * @since 2.0 Updated third parameter to pass \GV\Template_Context instead of \GravityView_View
				 * @param string $alt Existing class. Default: if odd row, `alt`, otherwise empty string.
				 * @param array $entry Current entry being displayed
				 * @param \GV\Template_Context $gravityview Current $gravityview state
				 */
				$class = apply_filters( 'gravityview_entry_class', $alt, $entry, $gravityview );

				$attributes = array(
					'class' => $alt,
				);

				$gravityview->template->the_entry( $entry, $attributes );
			}
		}

		/**
		 * @action `gravityview_table_body_after` Inside the `tbody`, after all rows are rendered. Can be used to insert additional rows.
		 * @since 1.0.7
         * @since 2.0 Updated to pass \GV\Template_Context instead of \GravityView_View
		 */
		do_action('gravityview_table_body_after', $gravityview );
		?>
	</tbody>
