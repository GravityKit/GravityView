<div class="gv-list-single-container">
	<?php echo gravityview_back_link(); ?>

	<?php foreach( $this->entries as $entry ) : ?>

	<div id="gv_list_<?php echo $entry['id']; ?>" class="">

		<div class="gv-list-view-title">

			<?php if( !empty( $this->fields['single_list-title'] ) ):
				$i = 0;
				foreach( $this->fields['single_list-title'] as $field ) :
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

			<?php if( !empty(  $this->fields['single_list-image'] ) ): ?>
			<div class="gv-list-view-content-image">
			<?php
				foreach( $this->fields['single_list-image'] as $field ) : ?>
					<?php echo gv_value( $entry, $field ); ?>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<?php if( !empty(  $this->fields['single_list-description'] ) ): ?>
				<div class="gv-list-view-content-description">
				<?php
				foreach( $this->fields['single_list-description'] as $field ) : ?>
					<div class="<?php echo gv_class( $field ); ?>"><?php
						echo esc_html( gv_label( $field ) );
						echo gv_value( $entry, $field );
					?>
					</div>
				<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>

		<?php if( !empty(  $this->fields['single_list-footer'] ) ): ?>

		<div class="gv-list-view-footer">

			<ul>
				<?php foreach( $this->fields['single_list-footer'] as $field ) : ?>
					<li class="<?php echo gv_class( $field ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></li>
				<?php endforeach; ?>
			</ul>

		</div>
		<?php endif; ?>

	</div>

	<?php endforeach; ?>

</div>
