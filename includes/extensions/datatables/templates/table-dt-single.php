<?php gravityview_before(); ?>
<?php echo gravityview_back_link(); ?>
<div class="gv-table-view gv-table-single-container">
	<table class="gv-table-view-content">
		<?php if( !empty( $this->fields['single_table-columns'] ) ): ?>
			<thead>
				<?php gravityview_header(); ?>
			</thead>
			<tbody>
				<?php foreach( $this->entries as $entry ) : ?>
					<?php foreach( $this->fields['single_table-columns'] as $field ) :

						$value = gv_value( $entry, $field );
						var_dump($this->hide_empty_fields);
						if( $value === '' && $this->hide_empty_fields ) { continue; }
					?>
						<tr class="<?php echo gv_class( $field ); ?>">
							<th scope="row"><?php echo esc_html( gv_label( $field ) ); ?></th>
							<td><?php echo $value; ?></td>
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
