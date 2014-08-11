	<tbody>
		<?php

		do_action('gravityview_table_body_before', $this );

		if((int)$this->total_entries === 0) {
			?>
			<tr>
				<?php do_action('gravityview_table_tr_before', $this ); ?>
				<td colspan="<?php echo sizeof($this->fields['directory_table-columns']); ?>" class="gv-no-results">
					<?php echo gv_no_results(); ?>
				</td>
				<?php do_action('gravityview_table_tr_after', $this ); ?>
			</tr>
		<?php
		} else {
			$class = true;
			foreach( $this->entries as $entry ) :
				$class = !$class ? ' class="alt"' : NULL;
		?>
				<tr<?php echo $class; ?>>
		<?php
					do_action('gravityview_table_cells_before', $this );

					if( !empty(  $this->fields['directory_table-columns'] ) ) {

						$fields = apply_filters('gravityview_table_cells', $this->fields['directory_table-columns'], $this );

						foreach( $fields as $field ) {
							echo '<td class="' . gv_class( $field, $this->form, $entry ) .'">'.gv_value( $entry, $field ).'</td>';
						}
					}

					do_action('gravityview_table_cells_after', $this ); ?>
				</tr>
			<?php
			endforeach;

		}

		do_action('gravityview_table_body_after', $this );
	?>
	</tbody>
