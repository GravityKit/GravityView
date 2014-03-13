<?php gravityview_before(); ?>
<?php echo gravityview_back_link(); ?>
<table class="">
	<?php if( !empty( $this->fields['table-columns-single'] ) ): ?>
		<thead>
			<?php gravityview_header(); ?>
			<tr>
				<?php 
				foreach( $this->fields['table-columns-single'] as $field ) {
					echo '<th class="'. esc_attr( gv_class( $field ) ) .'">' . esc_html( gv_label( $field ) ) . '</th>';
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
					echo '<th class="'. esc_attr( gv_class( $field ) ) .'">' . esc_html( gv_label( $field ) ) . '</th>';
				}
				?>
			</tr>
			<?php gravityview_footer(); ?>
		</tfoot>
	<?php endif; ?>
</table>
<?php gravityview_after(); ?>