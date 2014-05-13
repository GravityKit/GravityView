	<tbody>
		<?php
		if((int)$this->__get('total_entries') === 0) {
			?>
			<tr>
				<td colspan="<?php echo sizeof($this->fields['directory_table-columns']); ?>" class="gv-no-results">
					<?php echo gv_no_results(); ?>
				</td>
			</tr>
		<?php
		} else {
			foreach( $this->entries as $entry ) : ?>
				<tr>
					<?php if( !empty(  $this->fields['directory_table-columns'] ) ):
						foreach( $this->fields['directory_table-columns'] as $field ) : ?>
							<td><?php echo gv_value( $entry, $field ); ?></td>
						<?php endforeach; ?>
					<?php endif; ?>
				</tr>
			<?php
			endforeach;

		}
	?>
	</tbody>
