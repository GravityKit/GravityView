<?php
/**
 * Display multiple entries as a list
 *
 * @package GravityView
 */

if( empty( $this->total_entries ) ) {

	echo '<div class="gv-list-view gv-no-results"><div class="gv-list-view-title"><h3>'.gv_no_results().'</h3></div></div>';

	return;
}

foreach( $this->entries as $entry ) : ?>

	<div id="gv_list_<?php echo $entry['id']; ?>" class="gv-list-view">

		<?php if( !empty(  $this->fields['directory_list-title'] ) || !empty(  $this->fields['directory_list-subtitle'] ) ): ?>
			<div class="gv-list-view-title">

				<?php if( !empty(  $this->fields['directory_list-title'] ) ):
					$i = 0;
					$title_args = array(
						'entry' => $entry,
						'form' => $this->form,
						'hide_empty' => $this->atts['hide_empty'],
					);

					foreach( $this->fields['directory_list-title'] as $field ) :
						$title_args['field'] = $field;

						// The first field in the title zone is the main
						if( $i == 0 ) {
							$title_args['markup'] = '<h3 class="{{class}}">{{label}}{{value}}</h3>';
							echo gravityview_field_output( $title_args );
						} else {
							$title_args['wpautop'] = true;
							echo gravityview_field_output( $title_args );
						}

						$i++;
					endforeach;
				endif;

				if( !empty(  $this->fields['directory_list-subtitle'] ) ): ?>
					<div class="gv-list-view-subtitle">
						<?php foreach( $this->fields['directory_list-subtitle'] as $field ) :

							echo gravityview_field_output( array(
								'entry' => $entry,
								'field' => $field,
								'form' => $this->form,
								'hide_empty' => $this->atts['hide_empty'],
								'markup' => '<h4 class="{{class}}">{{label}}{{value}}</h4>',
							) );

						endforeach; ?>
					</div>
				<?php endif; ?>

			</div>
		<?php endif; ?>

		<div class="gv-list-view-content">

			<?php if( !empty(  $this->fields['directory_list-image'] ) ): ?>
				<div class="gv-list-view-content-image">
					<?php foreach( $this->fields['directory_list-image'] as $field ) :

						echo gravityview_field_output( array(
							'entry' => $entry,
							'field' => $field,
							'form' => $this->form,
							'hide_empty' => $this->atts['hide_empty'],
						) );

					endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if( !empty(  $this->fields['directory_list-description'] ) ): ?>
				<div class="gv-list-view-content-description">
					<?php foreach( $this->fields['directory_list-description'] as $field ) :

						echo gravityview_field_output( array(
							'entry' => $entry,
							'field' => $field,
							'form' => $this->form,
							'hide_empty' => $this->atts['hide_empty'],
							'label_markup' => '<h4>{{label}}</h4>',
							'wpautop' => true
						) );

					 endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if( !empty(  $this->fields['directory_list-content-attributes'] ) ): ?>
				<div class="gv-list-view-content-attributes">
					<?php foreach( $this->fields['directory_list-content-attributes'] as $field ) :

						echo gravityview_field_output( array(
							'entry' => $entry,
							'field' => $field,
							'form' => $this->form,
							'hide_empty' => $this->atts['hide_empty'],
							'markup' => '<p class="{{class}}">{{label}}{{value}}</p>'
						) );

					endforeach; ?>
				</div>
			<?php endif; ?>

		</div>

		<?php if( !empty(  $this->fields['directory_list-footer-left'] ) || !empty(  $this->fields['directory_list-footer-right'] ) ): ?>
			<div class="gv-grid gv-list-view-footer">
				<div class="gv-grid-col-1-2 gv-left">
					<?php if( !empty(  $this->fields['directory_list-footer-left'] ) ): ?>
						<?php foreach( $this->fields['directory_list-footer-left'] as $field ) :

							echo gravityview_field_output( array(
								'entry' => $entry,
								'field' => $field,
								'form' => $this->form,
								'hide_empty' => $this->atts['hide_empty'],
							) );

						endforeach; ?>
					<?php endif; ?>
				</div>

				<div class="gv-grid-col-1-2 gv-right">
					<?php if( !empty(  $this->fields['directory_list-footer-right'] ) ): ?>
						<?php foreach( $this->fields['directory_list-footer-right'] as $field ) :

							echo gravityview_field_output( array(
								'entry' => $entry,
								'field' => $field,
								'form' => $this->form,
								'hide_empty' => $this->atts['hide_empty'],
							) );

						endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

	</div>

<?php endforeach; ?>
