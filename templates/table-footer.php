	<tfoot>
		<tr>
			<?php 
			if( !empty( $this->fields['table-columns'] ) ) {
				foreach( $this->fields['table-columns'] as $field ) {
					echo '<th class="'. esc_attr( gv_class( $field ) ) .'">' . esc_html( gv_label( $field ) ) . '</th>';
				}
			}
			?>
		</tr>
		<?php gravityview_footer(); ?>
	</tfoot>
</table>
<?php gravityview_after(); ?>