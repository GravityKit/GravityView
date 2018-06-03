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

<div class="<?php gv_container_class( 'gv-list-container gv-list-single-container' ); ?>">
	<p class="gv-back-link"><?php echo gravityview_back_link(); ?></p>
	<?php
    if( $this->getContextFields() ) {
    foreach ( $this->getEntries() as $entry ) {
		$this->setCurrentEntry( $entry );

		$entry_slug = GravityView_API::get_entry_slug( $entry['id'], $entry );
	?>

		<div id="gv_list_<?php echo esc_attr( $entry_slug ); ?>" class="gv-list-view">
	<?php

	if ( $this->getFields( 'single_list-title' ) || $this->getFields( 'single_list-subtitle' ) ) { ?>
		<div class="gv-list-view-title">
		<?php
		if ( $fields = $this->getFields( 'single_list-title' ) ) {
			$i = 0;
			$title_args = array(
				'entry' => $entry,
				'form' => $this->form,
				'hide_empty' => $this->atts['hide_empty'],
			);
			foreach ( $fields as $field ) {
				$title_args['field'] = $field;
				if ( 0 === $i ) {
					$title_args['markup'] = '<h3 id="{{ field_id }}" class="{{class}}">{{label}}{{value}}</h3>';
					echo gravityview_field_output( $title_args );
				} else {
					$title_args['wpautop'] = true;
					echo gravityview_field_output( $title_args );
				}
				$i++;
			}
		}

		$this->renderZone('subtitle', array(
			'wrapper_class' => 'gv-list-view-subtitle',
			'markup'     => '<h4 id="{{ field_id }}" class="{{class}}">{{label}}{{value}}</h4>',
		));

		?>
		</div>
	<?php
	}

	if ( $this->getFields( 'single_list-image' ) || $this->getFields( 'single_list-description' ) || $this->getFields( 'single_list-content-attributes' ) ) { ?>
		<div class="gv-list-view-content">
			<?php

				$this->renderZone('image', array(
					'wrapper_class' => 'gv-list-view-content-image gv-grid-col-1-3',
					'markup'     => '<h4 id="{{ field_id }}" class="{{class}}">{{label}}{{value}}</h4>',
				));

				$this->renderZone('description', array(
					'wrapper_class' => 'gv-list-view-content-description',
					'label_markup' => '<h4>{{label}}</h4>',
					'wpautop' => true,
				));

				$this->renderZone('content-attributes', array(
					'wrapper_class' => 'gv-list-view-content-attributes',
					'markup' => '<p id="{{ field_id }}" class="{{class}}">{{label}}{{value}}</p>',
				));

			?>
		</div>
    <?php }

    if ( $this->getFields( 'single_list-footer-left' ) || $this->getFields( 'single_list-footer-right' ) ) { ?>
			<div class="gv-grid gv-list-view-footer">
				<div class="gv-grid-col-1-2 gv-left">
					<?php $this->renderZone( 'footer-left' ); ?>
				</div>

				<div class="gv-grid-col-1-2 gv-right">
					<?php $this->renderZone( 'footer-right' ); ?>
				</div>
			</div>
		<?php } ?>

	</div>

	<?php } } // End foreach $this->getEntries() and $this->getContextFields() ?>

</div>

<?php gravityview_after(); ?>
