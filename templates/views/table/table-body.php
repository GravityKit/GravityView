<?php
/**
 * The entry loop for the table output.
 *
 * @global \GV\Template_Context $gravityview
 */
?>
	<tbody>
		<?php

		/** @action `gravityview/template/table/body/before` */
		$gravityview->template::body_before( $gravityview );

		if ( ! $gravityview->entries->count() ) {
			?>
			<tr>
				<?php

				/** @action `gravityview/template/table/tr/before` */
				$gravityview->template::tr_before( $gravityview );

                ?>
				<td colspan="<?php echo $gravityview->fields->by_position( 'directory_table-columns' )->by_visible()->count() ? : ''; ?>" class="gv-no-results">
					<?php echo gv_no_results( true, $gravityview ); ?>
				</td>
				<?php

				/** @action `gravityview/template/table/tr/after` */
				$gravityview->template::tr_after( $gravityview );

                ?>
			</tr>
		<?php
		} else {
			foreach ( $gravityview->entries->all() as $entry ) {

				// Add `alt` class to alternate rows
				$alt = empty( $alt ) ? 'alt' : '';

				/** @action `gravityview/template/table/entry/class` */
				$class = $gravityview->template::entry_class( $alt, $entry, $gravityview );

				$attributes = array(
					'class' => $class,
				);

				$gravityview->template->the_entry( $entry, $attributes );
			}
		}

		/** @action `gravityview/template/table/body/after` */
		$gravityview->template::body_after( $gravityview );
		?>
	</tbody>
