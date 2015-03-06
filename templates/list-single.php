<?php
/**
 * Display a single entry when using a list template
 *
 * @package GravityView
 * @subpackage GravityView/templates
 *
 * @global GravityView_View $this
 */
?>
<?php gravityview_before(); ?>

<div class="gv-container gv-list-single-container gv-list-container">

	<p class="gv-back-link"><?php echo gravityview_back_link(); ?></p>

	<?php foreach( $this->getEntries() as $entry ) :

		$this->setCurrentEntry( $entry );
	?>

		<div id="gv_list_<?php echo $entry['id']; ?>" class="gv-list-view">

			<?php if( !empty(  $this->fields['single_list-title'] ) || !empty(  $this->fields['single_list-subtitle'] ) ): ?>
				<div class="gv-list-view-title">

					<?php if( !empty(  $this->fields['single_list-title'] ) ):
						$i = 0;
						$title_args = array(
							'entry' => $entry,
							'form' => $this->form,
							'hide_empty' => $this->atts['hide_empty'],
						);
						foreach( $this->fields['single_list-title'] as $field ) :
							$title_args['field'] = $field;
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

					$this->renderZone('subtitle', array(
						'wrapper_class' => 'gv-list-view-subtitle',
						'markup'     => '<h4 class="{{class}}">{{label}}{{value}}</h4>'
					));

					?>
				</div>
			<?php endif; ?>

			<div class="gv-list-view-content">
				<?php

					$this->renderZone('image', array(
						'wrapper_class' => 'gv-list-view-content-image',
						'markup'     => '<h4 class="{{class}}">{{label}}{{value}}</h4>'
					));

					$this->renderZone('description', array(
						'wrapper_class' => 'gv-list-view-content-description',
						'label_markup' => '<h4>{{label}}</h4>',
						'wpautop' => true
					));

					$this->renderZone('content-attributes', array(
						'wrapper_class' => 'gv-list-view-content-attributes',
						'markup' => '<p class="{{class}}">{{label}}{{value}}</p>'
					));

				?>
			</div>

			<?php if( !empty(  $this->fields['single_list-footer-left'] ) || !empty(  $this->fields['single_list-footer-right'] ) ): ?>

				<div class="gv-grid gv-list-view-footer">
					<div class="gv-grid-col-1-2 gv-left">
						<?php $this->renderZone('footer-left'); ?>
					</div>

					<div class="gv-grid-col-1-2 gv-right">
						<?php $this->renderZone('footer-right'); ?>
					</div>
				</div>

			<?php endif; ?>

		</div>

	<?php endforeach; ?>

</div>

<?php gravityview_after(); ?>
