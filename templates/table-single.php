<?php gravityview_before(); ?>
<table class="">
	<thead>
		<?php gravityview_header(); ?>
		<tr>
			<?php 
			foreach( $this->fields['table-columns-single'] as $field ) {
				echo '<th>' . esc_html( $field['label'] ) . '</th>';
			}
			?>
		</tr>
	</thead>
	<tbody>
		<?php foreach( $this->entries as $entry ) : ?>
			<tr>
				<?php foreach( $this->fields['table-columns-single'] as $field ) : ?>
					<td><?php echo gv_value( $entry, $field ); ?></td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<?php 
			foreach( $this->fields['table-columns-single'] as $field ) {
				echo '<th>' . esc_html( $field['label'] ) . '</th>';
			}
			?>
		</tr>
		<?php gravityview_footer(); ?>
	</tfoot>
</table>
<?php gravityview_after(); ?>