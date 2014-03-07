	<tfoot>
		<tr>
			<?php 
			if( !empty( $this->fields['table-columns'] ) ) {
				foreach( $this->fields['table-columns'] as $field ) {
					echo '<th>' . esc_html( $field['label'] ) . '</th>';
				}
			}
			?>
		</tr>
		<?php gravityview_footer(); ?>
	</tfoot>
</table>
<?php gravityview_after(); ?>