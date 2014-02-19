	<tbody>
		<?php foreach( $this->entries as $entry ) : ?>
			<tr>
				<?php foreach( $this->fields['table-columns'] as $field ) : ?>
					<td><?php echo gv_value( $entry, $field ); ?></td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
	</tbody>