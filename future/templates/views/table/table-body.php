<?php
/**
 * The entry loop for the table output.
 *
 * @global stdClass $gravityview
 *  \GV\View $gravityview::$view
 *  \GV\View_Template $gravityview::$template
 *  \GV\Field_Collection $gravityview::$fields
 *  \GV\Entry_Collection $gravityview::$entries
 */
?>
	<tbody>
		<?php
		if ( ! $gravityview->entries->count() ) {
			?>
			<tr>
				<td colspan="<?php echo $gravityview->fields->count() ? : ''; ?>" class="gv-no-results">
					<?php echo gv_no_results(); ?>
				</td>
			</tr>
		<?php
		} else {
			foreach ( $gravityview->entries->all() as $entry ) {

				// Add `alt` class to alternate rows
				$alt = empty( $alt ) ? 'alt' : '';

				$attributes = array(
					'class' => $alt,
				);

				$gravityview->template->the_entry( $entry, $attributes );
			}
		}
		?>
	</tbody>
