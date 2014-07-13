<div class="gv-list-single-container">

	<p class="gv-back-link"><?php echo gravityview_back_link(); ?></p>

	<?php foreach( $this->entries as $entry ) : ?>

		<div id="gv_list_<?php echo $entry['id']; ?>" class="gv-list-view">

			<?php if( !empty(  $this->fields['single_list-title'] ) || !empty(  $this->fields['single_list-subtitle'] ) ): ?>
			<div class="gv-list-view-title">

				<?php if( !empty(  $this->fields['single_list-title'] ) ):
					$i = 0;
					foreach( $this->fields['single_list-title'] as $field ) :
						if( $i == 0 ): ?>
							<h3 class="<?php echo gv_class( $field, $this->form, $entry ); ?>"><?php echo gv_value( $entry, $field ); ?></h3>
						<?php else: ?>
							<div class="<?php echo gv_class( $field, $this->form, $entry ); ?>"><?php echo wpautop(gv_value( $entry, $field )); ?></div>
						<?php endif;
						$i++; ?>
					<?php endforeach; ?>
				<?php endif;

				if( !empty(  $this->fields['single_list-subtitle'] ) ):

				?>
				<div class="gv-list-view-subtitle"> <?php
					foreach( $this->fields['single_list-subtitle'] as $field ) :
				?>
					<h4 class="<?php echo gv_class( $field, $this->form, $entry ); ?>"><?php echo gv_value( $entry, $field ); ?></h4>
				<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<div class="gv-list-view-content">

				<?php if( !empty(  $this->fields['single_list-image'] ) ): ?>
				<div class="gv-list-view-content-image">
				<?php
					foreach( $this->fields['single_list-image'] as $field ) : ?>
						<div class="<?php echo gv_class( $field, $this->form, $entry ); ?>">
						<?php echo gv_value( $entry, $field ); ?>
						</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<?php if( !empty(  $this->fields['single_list-description'] ) ): ?>
				<div class="gv-list-view-content-description">
				<?php
					foreach( $this->fields['single_list-description'] as $field ) :
						$value = gv_value( $entry, $field );

						if( $value === '' && $this->hide_empty_fields ) { continue; }
					?>
						<div class="<?php echo gv_class( $field, $this->form, $entry ); ?>"><?php

							$label = gv_label( $field, $entry );
							if(!empty($label)) { echo '<h4>'.esc_html( $label ).'</h4>'; }

							echo wpautop( $value );
						?>
						</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<?php if( !empty(  $this->fields['single_list-content-attributes'] ) ): ?>
				<div class="gv-list-view-content-attributes">
				<?php
					foreach( $this->fields['single_list-content-attributes'] as $field ) :

						$value = gv_value( $entry, $field );

						if( $value === '' && $this->hide_empty_fields ) { continue; }
				?>
						<p class="<?php echo gv_class( $field, $this->form, $entry ); ?>"><?php echo esc_html( gv_label( $field, $entry ) ); ?><?php echo gv_value( $entry, $field ); ?></p>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

			</div>

			<?php if( !empty(  $this->fields['single_list-footer-left'] ) || !empty(  $this->fields['single_list-footer-right'] ) ): ?>
				<div class="gv-grid gv-list-view-footer">
					<div class="gv-grid-col-1-2 gv-left">
					<?php if( !empty(  $this->fields['single_list-footer-left'] ) ): ?>
						<?php foreach( $this->fields['single_list-footer-left'] as $field ) :
							$value = gv_value( $entry, $field );

							if( $value === '' && $this->hide_empty_fields ) { continue; }
						?>
							<div class="<?php echo gv_class( $field, $this->form, $entry ); ?>"><?php echo esc_html( gv_label( $field, $entry ) ); ?><?php echo gv_value( $entry, $field ); ?></div>
						<?php endforeach; ?>
					<?php endif; ?>
					</div>

					<div class="gv-grid-col-1-2 gv-right">
					<?php if( !empty(  $this->fields['single_list-footer-right'] ) ): ?>
						<?php foreach( $this->fields['single_list-footer-right'] as $field ) :

							$value = gv_value( $entry, $field );

							if( $value === '' && $this->hide_empty_fields ) { continue; }
						?>
							<div class="<?php echo gv_class( $field, $this->form, $entry ); ?>"><?php echo esc_html( gv_label( $field, $entry ) ); ?><?php echo gv_value( $entry, $field ); ?></div>
						<?php endforeach; ?>
					<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

		</div>


	<?php endforeach; ?>

</div>
