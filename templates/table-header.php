<?php gravityview_before(); ?>
<table class="">
	<thead>
		<?php gravityview_header(); ?>
		<tr>
			<?php 
			if( !empty( $this->fields['table-columns'] ) ) {
				foreach( $this->fields['table-columns'] as $field ) {
					echo '<th>' . esc_html( $field['label'] ) . '</th>';
				}
			}
			?>
		</tr>
	</thead>


