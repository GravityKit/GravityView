<?php gravityview_before(); ?>
<?php echo gravityview_back_link(); ?>
<div class="gv-table-single-container">
	<table class="gv-table-view-content">
		<?php if( !empty( $this->fields['single_table-columns'] ) ): ?>
			<thead>
				<?php gravityview_header(); ?>
			</thead>
			<tbody>
				<?php foreach( $this->entries as $entry ) : ?>
					<?php foreach( $this->fields['single_table-columns'] as $field ) : ?>
						<tr>
							<th class="<?php echo gv_class( $field ); ?>" scope="row"><?php echo esc_html( gv_label( $field ) ); ?></th>
							<td><?php echo gv_value( $entry, $field ); ?></td>
						</tr>
						<?php endforeach; ?>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<?php gravityview_footer(); ?>
			</tfoot>
		<?php endif; ?>
	</table>
</div>
<?php gravityview_after(); ?>
