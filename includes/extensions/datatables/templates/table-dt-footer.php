	<tfoot>
		<tr>
			<?php
			if( !empty( $this->fields['directory_table-columns'] ) ) {
				foreach( $this->fields['directory_table-columns'] as $field ) {
					echo '<th class="'. gv_class( $field ) .'">' . esc_html( gv_label( $field ) ) . '</th>';
				}
			}
			?>
		</tr>
		<?php gravityview_footer(); ?>
	</tfoot>
</table>
</div>
<?php gravityview_after(); ?>
