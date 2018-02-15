<?php
/**
 * The entry loop for the table output.
 *
 * @global \GV\Template_Context $gravityview
 */
?>
	<tbody>
		<?php
		if ( ! $gravityview->entries->count() ) {
			?>
			<tr>
				<td colspan="<?php echo $gravityview->fields->by_position( 'directory_table-columns' )->by_visible()->count() ? : ''; ?>" class="gv-no-results">
					<?php echo gv_no_results(); ?>
				</td>
			</tr>
		<?php
		} else {
			foreach ( $gravityview->entries->all() as $entry ) {

				// Add `alt` class to alternate rows
				$alt = empty( $alt ) ? 'alt' : '';

				/**
				 * @filter `gravityview_entry_class` Modify the class applied to the entry row
				 * @param string $alt Existing class. Default: if odd row, `alt`, otherwise empty string.
				 * @param array $entry Current entry being displayed
				 * @param object $gravityview Current $gravityview state
				 */
				$class = apply_filters( 'gravityview_entry_class', $alt, $entry, $gravityview );

				$attributes = array(
					'class' => $alt,
				);

				$gravityview->template->the_entry( $entry, $attributes );
			}
		}
		?>
	</tbody>
