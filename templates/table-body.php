	<tbody>
		<?php foreach( $this->entries as $entry ) : ?>
			<tr>
				<?php foreach( $this->fields['table-columns'] as $field ) {
					$content = empty( $entry[ $field['id'] ] ) ? '' : $entry[ $field['id'] ];
					echo '<td>'. $content .'</td>';
				} ?>
			</tr>
		<?php endforeach; ?>
	</tbody>