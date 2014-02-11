	<tfoot>
		<tr>
			<?php 
			foreach( $this->fields['table-columns'] as $field ) {
				echo '<th>' . esc_html( $field['label'] ) . '</th>';
			}
			?>
		</tr>
	</tfoot>
</table>