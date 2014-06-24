<?php gravityview_before(); ?>
<div class="gv-datatables-wrapper">
<table class="gv-datatables <?php echo esc_attr( apply_filters('gravityview_datatables_table_class', 'display dataTable') ); ?>">
	<thead>
		<?php gravityview_header(); ?>
		<tr>
			<?php
			if( !empty( $this->fields['directory_table-columns'] ) ) {
				foreach( $this->fields['directory_table-columns'] as $field ) {
					echo '<th class="'. gv_class( $field ) .'" scope="col">' . esc_html( gv_label( $field ) ) . '</th>';
				}
			}
			?>
		</tr>
	</thead>

