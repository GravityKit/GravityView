<div id="">

	<?php foreach( $this->entries as $entry ) : ?>
	
		<div id="gv_list_<?php echo $entry['id']; ?>" class="">
		
			<div class="list-row-title">
			
				<?php foreach( $this->fields['list-title'] as $field ) : ?>
					<h3 class="<?php echo esc_attr( gv_class( $field ) ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></h3>
				<?php endforeach; ?>
				
			</div>
			
			<div class="list-row-content">
			
				<?php foreach( $this->fields['list-content'] as $field ) : ?>
					<p class="<?php echo esc_attr( gv_class( $field ) ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></p>
				<?php endforeach; ?>
				
			</div>
			
			<div class="list-row-footer">
			
				<?php foreach( $this->fields['list-footer'] as $field ) : ?>
					<p class="<?php echo esc_attr( gv_class( $field ) ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></p>
				<?php endforeach; ?>
				
			</div>
	
		</div>
		
	<?php endforeach; ?>
	
</div>