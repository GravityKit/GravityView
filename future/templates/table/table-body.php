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

			foreach ( $gravityview->entries->all() as $entry ) :

				// Add `alt` class to alternate rows
				$alt = empty( $alt ) ? 'alt' : '';

				/**
				 * @filter `gravityview_entry_class` Modify the class applied to the entry row
				 * @param string $alt Existing class. Default: if odd row, `alt`, otherwise empty string.
				 * @param array $entry Current entry being displayed
				 * @param GravityView_View $this Current GravityView_View object
				 */
				$class = apply_filters( 'gravityview_entry_class', $alt, $entry, null );
		?>
				<tr<?php echo ' class="'.esc_attr( $class ).'"'; ?>>
		<?php
			echo sprintf( "<td colspan=\"%s\">{$entry->ID}</td>", $gravityview->fields->count() ? : '' );
		?>
				</tr>
			<?php
			endforeach;

		}
	?>
	</tbody>
