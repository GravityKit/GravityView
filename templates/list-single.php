<div id="gv-list-single-container">
	<?php echo gravityview_back_link(); ?>

	<?php foreach( $this->entries as $entry ) : ?>

		<div id="gv_list_<?php echo $entry['id']; ?>" class="">

			<div class="gv-list-view-title">

				<?php if( !empty( $this->fields['single-list-title'] ) ):
					$i = 0;
					foreach( $this->fields['single-list-title'] as $field ) :
						if( $i == 0 ): ?>
							<h3 class="<?php echo gv_class( $field ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></h3>
						<?php else: ?>
							<p class="<?php echo gv_class( $field ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></p>
						<?php endif;
						$i++; ?>
					<?php endforeach; ?>
				<?php endif; ?>

			</div>

		<div class="gv-list-view-content">

			<div class="gv-list-view-content-description">
				<?php if( !empty(  $this->fields['single-list-description'] ) ):
					foreach( $this->fields['single-list-description'] as $field ) : ?>
						<p class="<?php echo gv_class( $field ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></p>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<div class="gv-list-view-content-image">
				<?php if( !empty(  $this->fields['single-list-image'] ) ):
					foreach( $this->fields['single-list-image'] as $field ) : ?>
						<?php echo gv_value( $entry, $field ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<div class="gv-list-view-content-attributes">
				<?php if( !empty(  $this->fields['single-list-attributes'] ) ):
					foreach( $this->fields['single-list-attributes'] as $field ) : ?>
						<p class="<?php echo gv_class( $field ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></p>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

		</div>

		<div class="gv-list-view-footer">

			<?php if( !empty(  $this->fields['single-list-footer'] ) ): ?>
				<ul>
					<?php foreach( $this->fields['single-list-footer'] as $field ) : ?>
						<li class="<?php echo gv_class( $field ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

		</div>

		</div>

	<?php endforeach; ?>

</div>