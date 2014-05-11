<?php gravityview_before(); ?>
<table class="">
	<thead>
		<?php gravityview_header(); ?>
		<tr>
			<?php
			if( !empty( $this->fields['directory_table-columns'] ) ) {
				foreach( $this->fields['directory_table-columns'] as $field ) {
					echo '<th class="'. gv_class( $field ) .'">' . esc_html( gv_label( $field ) ) . '</th>';
				}
			}
			?>
		</tr>
	</thead>

