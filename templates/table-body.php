	<tbody>
		<?php foreach( $this->entries as $entry ) : ?>
			<tr>
				<?php if( !empty(  $this->fields['table-columns'] ) ):
					foreach( $this->fields['table-columns'] as $field ) : ?>
						<td><?php echo gv_value( $entry, $field ); ?></td>
					<?php endforeach; ?>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
	</tbody>